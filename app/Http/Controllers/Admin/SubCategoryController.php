<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\SubCategory;
use App\Http\Controllers\Admin\Concerns\AuthorizesCatalogManagement;
use App\Support\Tenancy\TenantContext;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class SubCategoryController extends Controller
{
    use AuthorizesCatalogManagement;
    
    private function categoryExistsRule()
    {
        return Rule::exists('categories', 'id')->where(function ($query) {
            if (config('operational.mode', 'single_store') === 'multi_tenant') {
                $tenantId = app(TenantContext::class)->current()?->getKey();
                abort_unless($tenantId, 403);
                $query->where('tenant_id', $tenantId);
                return;
            }
    
            $query->whereNull('tenant_id');
        });
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $this->authorizeCatalogManagement();
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
        $this->authorizeCatalogManagement();
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
        $this->authorizeCatalogManagement();
        $validated = $request->validate([
            'category_id' => [
                'required',
                Rule::exists('categories', 'id')
                    ->where(function ($query) {
                        if (config('operational.mode', 'single_store') === 'multi_tenant') {
                            $tenantId = app(TenanContext::class)
                                ->require()
                                ->getKey();
            
                            $query->where('tenant_id', $tenantId);
            
                            return;
                        }
            
                        $query->whereNull('tenant_id');
                    }),
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
        $this->authorizeCatalogManagement();
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
        $this->authorizeCatalogManagement();
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
        $this->authorizeCatalogManagement();
        $validated = $request->validate([
            'category_id' => [
                'required',
                Rule::exists('categories', 'id')
                    ->where(function ($query) {
                        if (config('operational.mode', 'single_store') === 'multi_tenant') {
                            $tenantId = app(TenanContext::class)
                                ->require()
                                ->getKey();
            
                            $query->where('tenant_id', $tenantId);
            
                            return;
                        }
            
                        $query->whereNull('tenant_id');
                    }),
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
        $this->authorizeCatalogManagement();
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
