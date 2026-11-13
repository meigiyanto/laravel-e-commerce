<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use App\Http\Controllers\Admin\Concerns\AuthorizesCatalogManagement;
use App\Models\Product;

class ProductGalleryController extends Controller
{
    use AuthorizesCatalogManagement;
    
    private const DISK = 'public';
    private const DIRECTORY = 'products';
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];
    private const MAX_IMAGE_KB = 5120;

    private function scopedProductIds()
    {
        return Product::query()->select('id');
    }

    private function pathRepresentations(string $path): array
    {
        $normalized = $this->normalizeStoredPath($path);
    
        return array_values(array_unique([
            $normalized,
            '/' . $normalized,
            'storage/' . $normalized,
            '/storage/' . $normalized,
        ]));
    }
    
    private function isPathUsedInCurrentTenant(string $path): bool
    {
        $representations = $this->pathRepresentations($path);
    
        $productImageUsed = Product::query()
            ->whereIn('image', $representations)
            ->exists();
    
        $galleryUsed = DB::table('product_images')
            ->whereIn('product_id', $this->scopedProductIds())
            ->whereIn('path', $representations)
            ->exists();
    
        return $productImageUsed || $galleryUsed;
    }
    
    /**
     * Pemeriksaan global hanya untuk keamanan file fisik.
     * Jangan gunakan hasilnya untuk menampilkan nama produk tenant lain.
     */
    private function isPathUsedAnywhere(string $path): bool
    {
        $representations = $this->pathRepresentations($path);
    
        return DB::table('products')
            ->whereIn('image', $representations)
            ->exists()
            || DB::table('product_images')
                ->whereIn('path', $representations)
                ->exists();
    }
    
    private function pathFromFilename(string $filename): string
    {
        abort_if($filename === '' || basename($filename) !== $filename || Str::contains($filename, ['/', '\\', '..']), 404);
        abort_unless(in_array(strtolower(pathinfo($filename, PATHINFO_EXTENSION)), self::ALLOWED_EXTENSIONS, true), 404);

        return self::DIRECTORY . '/' . $filename;
    }
    
    private function normalizeStoredPath(?string $path): string
    {
        $path = ltrim((string) $path, '/');
        if (str_starts_with($path, 'storage/')) {
            $path = substr($path, strlen('storage/'));
        }

        return $path;
    }

    /*
    private function isPathUsed(string $path): bool
    {
        $normalized = $this->normalizeStoredPath($path);

        $productImageUsed = DB::table('products')->whereNotNull('image')->get(['image'])
            ->contains(fn ($product) => $this->normalizeStoredPath($product->image) === $normalized);

        return $productImageUsed || DB::table('product_images')->get(['path'])
            ->contains(fn ($image) => $this->normalizeStoredPath($image->path) === $normalized);
    }
    */

    public function index(Request $request)
    {
        $this->authorizeCatalogView();
        $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:all,unused,used'],
        ]);

        $search = trim($request->input('search', ''));
        $status = $request->input('status', 'all');

        $productNamesByPath = collect();
        
        DB::table('product_images')
            ->join('products', 'products.id', '=', 'product_images.product_id')
            ->whereIn('products.id', $this->scopedProductIds())
            ->whereNotNull('product_images.path')
            ->select('product_images.path', 'products.name')
            ->get()
            ->each(function ($row) use ($productNamesByPath) {
                $path = $this->normalizeStoredPath($row->path);
                $productNamesByPath->put(
                    $path,
                    collect($productNamesByPath->get($path, []))
                        ->push($row->name)->filter()->unique()->values()->all()
                );
            });
        
        DB::table('products')
            ->whereIn('id', $this->scopedProductIds())
            ->whereNotNull('image')
            ->where('image', '!=', '')
            ->select('image', 'name')
            ->get()
            ->each(function ($row) use ($productNamesByPath) {
                $path = $this->normalizeStoredPath($row->image);
                $productNamesByPath->put(
                    $path,
                    collect($productNamesByPath->get($path, []))
                        ->push($row->name)->filter()->unique()->values()->all()
                );
            });        
        
        $images = collect(Storage::disk(self::DISK)->files(self::DIRECTORY))
            ->filter(fn ($path) => in_array(strtolower(pathinfo($path, PATHINFO_EXTENSION)), self::ALLOWED_EXTENSIONS, true))
            ->map(function ($path) use ($productNamesByPath) {
                $names = $productNamesByPath->get($path, []);

                return [
                    'path' => $path,
                    'filename' => basename($path),
                    'url' => Storage::disk(self::DISK)->url($path),
                    'is_used' => $this->isPathUsedInCurrentTenant($path),
                    'product_names' => $names,
                ];
            })
            ->filter(function ($image) use ($search, $status) {
                if ($search !== '' && stripos($image['filename'], $search) === false) {
                    return false;
                }

                if ($status === 'unused' && $image['is_used']) {
                    return false;
                }

                if ($status === 'used' && ! $image['is_used']) {
                    return false;
                }

                return true;
            })
            ->sortBy('filename')->values();

        $page = max(1, (int) $request->input('page', 1));
        $perPage = 12;
        $gallery = new \Illuminate\Pagination\LengthAwarePaginator(
            $images->forPage($page, $perPage)->values(),
            $images->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return view('admin.product-gallery.index', compact('gallery', 'search', 'status'));
    }

    public function upload(Request $request)
    {
        $this->authorizeCatalogView();
        $validated = $request->validate([
            'images' => ['required', 'array', 'max:20'],
            'images.*' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:' . self::MAX_IMAGE_KB],
        ]);

        foreach ($validated['images'] as $image) {
            $image->store(self::DIRECTORY, self::DISK);
        }

        return redirect()->route('admin.product-gallery.index', ['status' => 'unused'])
            ->with('success', count($validated['images']) . ' gambar berhasil diunggah.');
    }

    /** Replace a gallery file and atomically repoint every database reference to it. */
    public function replace(Request $request, string $filename)
    {
        $this->authorizeCatalogView();
        $oldPath = $this->pathFromFilename($filename);
        abort_unless(Storage::disk(self::DISK)->exists($oldPath), 404);

        $validated = $request->validate([
            'image' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:' . self::MAX_IMAGE_KB],
        ]);

        $newPath = $validated['image']->store(self::DIRECTORY, self::DISK);
        $normalizedOldPath = $this->normalizeStoredPath($oldPath);
        $normalizedNewPath = $this->normalizeStoredPath($newPath);

        try {
            DB::transaction(function () use ($oldPath, $normalizedOldPath, $normalizedNewPath) {
                $oldRepresentations = $this->pathRepresentations($oldPath);
        
                DB::table('product_images')
                    ->whereIn('product_id', $this->scopedProductIds())
                    ->whereIn('path', $oldRepresentations)
                    ->update([
                        'path' => $normalizedNewPath,
                        'updated_at' => now(),
                    ]);
        
                $products = DB::table('products')
                    ->whereIn('id', $this->scopedProductIds());
        
                $products->where('image', $oldPath)
                    ->update(['image' => $normalizedNewPath]);
        
                DB::table('products')
                    ->whereIn('id', $this->scopedProductIds())
                    ->where('image', '/' . $normalizedOldPath)
                    ->update(['image' => '/' . $normalizedNewPath]);
        
                DB::table('products')
                    ->whereIn('id', $this->scopedProductIds())
                    ->where('image', 'storage/' . $normalizedOldPath)
                    ->update(['image' => 'storage/' . $normalizedNewPath]);
        
                DB::table('products')
                    ->whereIn('id', $this->scopedProductIds())
                    ->where('image', '/storage/' . $normalizedOldPath)
                    ->update(['image' => '/storage/' . $normalizedNewPath]);
            });
        } catch (\Throwable $exception) {
            Storage::disk(self::DISK)->delete($newPath);
            throw $exception;
        }

        if (! $this->isPathUsedAnywhere($oldPath)) {
            Storage::disk(self::DISK)->delete($oldPath);
        }

        return redirect()->route('admin.product-gallery.index')->with('success', 'Gambar berhasil diganti dan seluruh referensinya diperbarui.');
    }

    /** Delete only files that are not referenced by products or product variants. */
    public function destroy(string $filename)
    {
        $this->authorizeCatalogView();
        $path = $this->pathFromFilename($filename);
        abort_unless(Storage::disk(self::DISK)->exists($path), 404);

        if ($this->isPathUsedAnywhere($path)) {
            return redirect()->route('admin.product-gallery.index')
                ->with('error', 'Gambar tidak dapat dihapus karena masih digunakan oleh produk atau varian.');
        }

        Storage::disk(self::DISK)->delete($path);

        return redirect()->route('admin.product-gallery.index')->with('success', 'Gambar berhasil dihapus.');
    }
}
