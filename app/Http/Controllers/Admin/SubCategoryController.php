<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\SubCategory;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SubCategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = trim((string) $request->input('search', ''));

        $subCategories = SubCategory::with('category')
            ->when($search !== '', function ($query) use ($search) {
                $query->where(function ($query) use ($search) {
                    $query->where('name', 'like', "%{$search}%")
                        ->orWhere('slug', 'like', "%{$search}%");
                });
            })
            ->latest()
            ->get();

        return view(
            'admin.sub-categories.index',
            compact('subCategories', 'search')
        );
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $categories = Category::orderBy('name')->get();

        return view(
            'admin.sub-categories.create',
            compact('categories')
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'category_id' => [
                'required',
                'exists:categories,id',
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'slug' => [
                'nullable',
                'string',
                'max:255',
                'unique:sub_categories,slug',
            ],

            'description' => [
                'nullable',
                'string',
            ],
        ]);

        $validated['slug'] = ! empty($validated['slug'])
            ? Str::slug($validated['slug'])
            : Str::slug($validated['name']);

        SubCategory::create($validated);

        return redirect()
            ->route('admin.sub-categories.index')
            ->with('success', 'Sub-category berhasil ditambahkan.');
    }

    /**
     * Display the specified resource.
     */
    public function show(SubCategory $subCategory)
    {
        return redirect()->route(
            'admin.sub-categories.edit',
            $subCategory
        );
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(SubCategory $subCategory)
    {
        $categories = Category::orderBy('name')->get();

        return view(
            'admin.sub-categories.edit',
            compact('subCategory', 'categories')
        );
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(
        Request $request,
        SubCategory $subCategory
    ) {
        $validated = $request->validate([
            'category_id' => [
                'required',
                'exists:categories,id',
            ],

            'name' => [
                'required',
                'string',
                'max:255',
            ],

            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('sub_categories', 'slug')
                    ->ignore($subCategory->id),
            ],

            'description' => [
                'nullable',
                'string',
            ],
        ]);

        $validated['slug'] = ! empty($validated['slug'])
            ? Str::slug($validated['slug'])
            : Str::slug($validated['name']);

        $subCategory->update($validated);

        return redirect()
            ->route('admin.sub-categories.index')
            ->with('success', 'Sub-category berhasil diperbarui.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(SubCategory $subCategory)
    {
        if ($subCategory->products()->exists()) {
            return redirect()
                ->route('admin.sub-categories.index')
                ->with(
                    'error',
                    'Sub-category tidak dapat dihapus karena masih memiliki produk.'
                );
        }

        $subCategory->delete();

        return redirect()
            ->route('admin.sub-categories.index')
            ->with(
                'success',
                'Sub-category berhasil dihapus.'
            );
    }
}
