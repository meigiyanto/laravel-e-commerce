<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class ProductGalleryController extends Controller
{
    private const DISK = 'public';
    private const DIRECTORY = 'products';
    private const ALLOWED_EXTENSIONS = ['jpg', 'jpeg', 'png', 'webp'];
    private const MAX_IMAGE_KB = 5120;

    public function index(Request $request)
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:all,unused,used'],
        ]);

        $search = trim($request->input('search', ''));
        $status = $request->input('status', 'all');

        $productNamesByPath = collect();

        DB::table('product_images')
            ->join('products', 'products.id', '=', 'product_images.product_id')
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
            ->whereNotNull('image')->where('image', '!=', '')
            ->select('image', 'name')->get()
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
                    'is_used' => $this->isPathUsed($path),
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
                // Update gallery records even if older data used a leading slash or storage/ prefix.
                $oldRepresentations = array_unique([
                    $oldPath, $normalizedOldPath, '/' . $normalizedOldPath,
                    'storage/' . $normalizedOldPath, '/storage/' . $normalizedOldPath,
                ]);
                DB::table('product_images')->whereIn('path', $oldRepresentations)->update([
                    'path' => $normalizedNewPath,
                    'updated_at' => now(),
                ]);

                // Preserve the legacy representation style in products.image.
                DB::table('products')->where('image', $oldPath)->update(['image' => $normalizedNewPath]);
                DB::table('products')->where('image', '/' . $normalizedOldPath)->update(['image' => '/' . $normalizedNewPath]);
                DB::table('products')->where('image', 'storage/' . $normalizedOldPath)->update(['image' => 'storage/' . $normalizedNewPath]);
                DB::table('products')->where('image', '/storage/' . $normalizedOldPath)->update(['image' => '/storage/' . $normalizedNewPath]);
            });
        } catch (\Throwable $exception) {
            Storage::disk(self::DISK)->delete($newPath);
            throw $exception;
        }

        Storage::disk(self::DISK)->delete($oldPath);

        return redirect()->route('admin.product-gallery.index')
            ->with('success', 'Gambar berhasil diganti dan seluruh referensinya diperbarui.');
    }

    /** Delete only files that are not referenced by products or product variants. */
    public function destroy(string $filename)
    {
        $path = $this->pathFromFilename($filename);
        abort_unless(Storage::disk(self::DISK)->exists($path), 404);

        if ($this->isPathUsed($path)) {
            return redirect()->route('admin.product-gallery.index')
                ->with('error', 'Gambar tidak dapat dihapus karena masih digunakan oleh produk atau varian.');
        }

        Storage::disk(self::DISK)->delete($path);

        return redirect()->route('admin.product-gallery.index')
            ->with('success', 'Gambar berhasil dihapus.');
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

    private function isPathUsed(string $path): bool
    {
        $normalized = $this->normalizeStoredPath($path);

        $productImageUsed = DB::table('products')->whereNotNull('image')->get(['image'])
            ->contains(fn ($product) => $this->normalizeStoredPath($product->image) === $normalized);

        return $productImageUsed || DB::table('product_images')->get(['path'])
            ->contains(fn ($image) => $this->normalizeStoredPath($image->path) === $normalized);
    }
}
