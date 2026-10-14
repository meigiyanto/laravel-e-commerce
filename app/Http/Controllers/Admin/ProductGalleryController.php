<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProductGalleryController extends Controller
{
    public function index(Request $request)
    {
        $request->validate([
            'search' => ['nullable', 'string', 'max:255'],
            'status' => ['nullable', 'in:all,unused,used'],
        ]);

        $search = trim($request->input('search', ''));
        $status = $request->input('status', 'all');

        $usedPaths = DB::table('products')
            ->whereNotNull('image')
            ->pluck('image')
            ->merge(
                DB::table('product_images')->pluck('path')
            )
            ->filter()
            ->unique()
            ->values()
            ->all();
        
        $productNamesByPath = collect();

        // Gambar yang terhubung melalui product_images.
        DB::table('product_images')
            ->join(
                'products',
                'products.id',
                '=',
                'product_images.product_id'
            )
            ->whereNotNull('product_images.path')
            ->select(
                'product_images.path',
                'products.name'
            )
            ->get()
            ->each(function ($row) use ($productNamesByPath) {
                $productNamesByPath
                    ->put(
                        $row->path,
                        collect($productNamesByPath->get($row->path, []))
                            ->push($row->name)
                            ->filter()
                            ->unique()
                            ->values()
                            ->all()
                    );
            });
        
        // Gambar lama yang tersimpan pada products.image.
        DB::table('products')
            ->whereNotNull('image')
            ->where('image', '!=', '')
            ->select('image', 'name')
            ->get()
            ->each(function ($row) use ($productNamesByPath) {
                $path = ltrim($row->image, '/');
        
                if (str_starts_with($path, 'storage/')) {
                    $path = substr($path, strlen('storage/'));
                }
        
                $productNamesByPath->put(
                    $path,
                    collect($productNamesByPath->get($path, []))
                        ->push($row->name)
                        ->filter()
                        ->unique()
                        ->values()
                        ->all()
                );
            });
        
        $images = collect(Storage::disk('public')->files('products'))
            ->filter(function ($path) {
                return in_array(
                    strtolower(pathinfo($path, PATHINFO_EXTENSION)), ['jpg', 'jpeg', 'png', 'webp', 'gif'], true
                );
            })
            ->map(function ($path) use ($usedPaths, $productNamesByPath) {
                return [
                    'path' => $path,
                    'filename' => basename($path),
                    'url' => Storage::disk('public')->url($path),
                    'is_used' => in_array($path, $usedPaths, true),
                    'product_names' => $productNamesByPath->get($path, []),
                ];
            })
            ->filter(function ($image) use ($search, $status) {
                if ($search !== '' && stripos($image['filename'], $search) === false ) {
                    return false;
                }

                if ($status === 'unused' && $image['is_used']){
                    return false;
                }

                if ($status === 'used' && ! $image['is_used']) {
                    return false;
                }

                return true;
            })
            ->sortBy('filename')
            ->values();
        
        
        $page = max(1, (int) $request->input('page', 1));
        $perPage = 12;

        $gallery = new \Illuminate\Pagination\LengthAwarePaginator(
            $images->forPage($page, $perPage)->values(),
            $images->count(),
            $perPage,
            $page,
            [
                'path' => $request->url(),
                'query' => $request->query(),
            ]
        );

        return view('admin.product-gallery.index', compact(
            'gallery',
            'search',
            'status'
        ));
    }

    public function upload(Request $request)
    {
        $validated = $request->validate([
            'images' => ['required', 'array', 'max:20'],
            'images.*' => [
                'required',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:5120',
            ],
        ]);

        foreach ($validated['images'] as $image) {
            $image->store('products', 'public');
        }

        return redirect()
            ->route('admin.product-gallery.index', [
                'status' => 'unused',
            ])
            ->with('success',count($validated['images']) . ' gambar berhasil diunggah.');
    }
}
