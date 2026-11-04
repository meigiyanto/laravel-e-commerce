<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Gate;

class CategoryController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', Product::class);
        $categories = Category::withCount('subCategories')
            ->latest()
            ->get();

        return view('admin.categories.index', compact('categories'));
    }

    public function create()
    {
        Gate::authorize('viewAny', Product::class);
        return view('admin.categories.create');
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Product::class);
        $tenantId = app(TenantContext::class)->require()->getKey();

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')->where('tenant_id', $tenantId),
            ],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('categories', 'slug')->where('tenant_id', $tenantId),
            ],
            'image' => ['nullable', 'string', 'max:2048'],
            'description' => ['nullable', 'string'],
        ]);

        $validated['slug'] = $validated['slug']
            ?: Str::slug($validated['name']);

        Category::create($validated);

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Kategori berhasil dibuat.');
    }

    public function show(Category $category)
    {
        return redirect()->route('admin.categories.edit', $category);
    }

    public function edit(Category $category)
    {
        Gate::authorize('update', Product::class);
        return view('admin.categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category)
    {
        Gate::authorize('update', Product::class);
        $tenantId = app(TenantContext::class)->require()->getKey();

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')
                    ->where('tenant_id', $tenantId)
                    ->ignore($category->id),
            ],
            'slug' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('categories', 'slug')
                    ->where('tenant_id', $tenantId)
                    ->ignore($category->id),
            ],
            'image' => ['nullable', 'string', 'max:2048'],
            'description' => ['nullable', 'string'],
        ]);

        $validated['slug'] = $validated['slug']
            ?: Str::slug($validated['name']);

        $category->update($validated);

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Category has been updated successfully');
    }

    public function destroy(Category $category)
    {
        Gate::authorize('destroy', Product::class);
        if ($category->products()->exists()) {
            return back()->with(
                'error',
                'Kategori tidak dapat dihapus karena masih memiliki produk.'
            );
        }

        $category->delete();

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Kategori berhasil dihapus.');
    }
}
