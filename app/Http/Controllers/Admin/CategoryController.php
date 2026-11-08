<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Support\Facades\Gate;

class CategoryController extends Controller
{
    public function index()
    {
        Gate::authorize('viewAny', Category::class);

        $categories = Category::withCount('subCategories')
            ->latest()
            ->get();

        return view('admin.categories.index', compact('categories'));
    }

    public function create()
    {
        Gate::authorize('create', Category::class);

        return view('admin.categories.create');
    }

    public function store(Request $request)
    {
        Gate::authorize('create', Category::class);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')->where(
                    fn ($query) => $this->applyOperationalScope($query)
                ),
            ],
            'slug' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'string', 'max:2048'],
            'description' => ['nullable', 'string'],
        ]);

        $validated['slug'] = $this->uniqueSlug(
            $validated['slug'] ?: $validated['name']
        );

        Category::create($validated);

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Kategori berhasil dibuat.');
    }

    public function show(Category $category)
    {
        Gate::authorize('view', $category);

        return redirect()->route('admin.categories.edit', $category);
    }

    public function edit(Category $category)
    {
        Gate::authorize('update', $category);

        return view('admin.categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category)
    {
        Gate::authorize('update', $category);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                Rule::unique('categories', 'name')
                    ->where(fn ($query) => $this->applyOperationalScope($query))
                    ->ignore($category->id),
            ],
            'slug' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'string', 'max:2048'],
            'description' => ['nullable', 'string'],
        ]);

        $validated['slug'] = $this->uniqueSlug(
            $validated['slug'] ?: $validated['name'],
            $category->id
        );

        $category->update($validated);

        return redirect()
            ->route('admin.categories.index')
            ->with('success', 'Kategori berhasil diperbarui.');
    }

    public function destroy(Category $category)
    {
        Gate::authorize('delete', $category);

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

    private function applyOperationalScope($query)
    {
        if (config('operational.mode', 'single_store') === 'multi_tenant') {
            return $query->where(
                'tenant_id',
                app(\App\Support\Tenancy\TenantContext::class)->require()->getKey()
            );
        }

        return $query->whereNull('tenant_id');
    }

    private function uniqueSlug(string $value, ?int $ignoreId = null): string
    {
        $base = Str::slug($value) ?: 'category';
        $slug = $base;
        $suffix = 1;

        while (
            Category::query()
                ->where('slug', $slug)
                ->when($ignoreId, fn ($query) => $query->whereKeyNot($ignoreId))
                ->exists()
        ) {
            $slug = $base . '-' . $suffix++;
        }

        return $slug;
    }
}
