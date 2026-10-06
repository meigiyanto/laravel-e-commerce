@props([
    'categories' => collect(),
    'categoryContext' => null,
    'action' => null,
])

@php
    $selectedCategorySlug = $categoryContext?->slug ?? request('category');
    $selectedSubCategorySlug = request('subcategory');
    $selectedSearch = request('q');
    $selectedSort = request('sort');
    $hasActiveFilters = filled($selectedSearch) || filled($selectedCategorySlug) || filled($selectedSubCategorySlug) || filled($selectedSort);
    $isCategoryContext = $categoryContext !== null;

    $availableSubCategories = $isCategoryContext ? $categoryContext->subCategories
        : $categories->flatMap(function ($category) {
                return $category->subCategories->map(
                    fn ($subCategory) => [
                        'slug' => $subCategory->slug,
                        'name' => $subCategory->name,
                        'category_slug' => $category->slug,
                    ]
                );
            });
@endphp

<div class="store-product-toolbar">
    <div class="store-filter-card">
        <form
            class="store-filter-form"
            action="{{ $action ?? url()->current() }}"
            method="GET"
        >

            @if ($isCategoryContext)
                <input
                    type="hidden"
                    name="category"
                    value="{{ $categoryContext->slug }}"
                >
            @endif

            <div class="row g-3">

                {{-- Search --}}
                <div class="col-12 col-lg-4">

                    <label
                        for="store-filter-search"
                        class="form-label"
                    >
                        Search Product
                    </label>

                    <div class="input-group">

                        <span class="input-group-text bg-white">
                            <i class="bi bi-search text-muted"></i>
                        </span>

                        <input
                            type="search"
                            id="store-filter-search"
                            name="q"
                            class="form-control"
                            value="{{ $selectedSearch }}"
                            placeholder="Name or description product..."
                        >

                    </div>

                </div>


                {{-- Category --}}
                <div class="col-12 col-md-6 {{ $isCategoryContext ? 'col-lg-3' : 'col-lg-3' }}">

                    <label
                        for="store-filter-category"
                        class="form-label"
                    >
                        Category
                    </label>

                    @if ($isCategoryContext)
                        <div class="store-filter-context">
                            <i class="bi bi-grid"></i>

                            <span>
                                {{ $categoryContext->name }}
                            </span>
                        </div>
                    @else
                        <select
                            id="store-filter-category"
                            name="category"
                            class="form-select"
                            data-filter-category
                        >

                            <option value="">
                                All Category
                            </option>

                            @foreach ($categories as $category)
                                <option
                                    value="{{ $category->slug }}"
                                    @selected(
                                        $selectedCategorySlug === $category->slug
                                    )
                                >
                                    {{ $category->name }}
                                </option>
                            @endforeach
                        </select>
                    @endif
                </div>


                {{-- Subcategory --}}
                <div class="col-12 col-md-6 col-lg-3">

                    <label
                        for="store-filter-subcategory"
                        class="form-label"
                    >
                        Sub Category
                    </label>

                    <select
                        id="store-filter-subcategory"
                        name="subcategory"
                        class="form-select"
                        data-filter-subcategory
                        @disabled(! $selectedCategorySlug)
                    >

                        <option value="">
                            {{ $selectedCategorySlug
                                ? 'All Subcategory'
                                : 'Choose category first'
                            }}
                        </option>

                        @if ($isCategoryContext)
                            @foreach ($availableSubCategories as $subCategory)
                                <option
                                    value="{{ $subCategory->slug }}"
                                    @selected(
                                        $selectedSubCategorySlug === $subCategory->slug
                                    )
                                >
                                    {{ $subCategory->name }}
                                </option>
                            @endforeach
                        @else
                            @foreach ($availableSubCategories as $subCategory)

                                <option
                                    value="{{ $subCategory['slug'] }}"
                                    data-category="{{ $subCategory['category_slug'] }}"
                                    @selected(
                                        $selectedSubCategorySlug === $subCategory['slug'] &&
                                        $selectedCategorySlug === $subCategory['category_slug']
                                    )
                                >
                                    {{ $subCategory['name'] }}
                                </option>

                            @endforeach
                        @endif
                    </select>
                </div>


                {{-- Sorting --}}
                <div class="col-12 col-md-6 col-lg-2">

                    <label
                        for="store-filter-sort"
                        class="form-label"
                    >
                        Sorting
                    </label>

                    <select
                        id="store-filter-sort"
                        name="sort"
                        class="form-select"
                    >

                        <option
                            value=""
                            @selected(! $selectedSort)
                        >
                            Newest
                        </option>

                        <option
                            value="oldest"
                            @selected($selectedSort === 'oldest')
                        >
                            Oldest
                        </option>

                        <option
                            value="price_low"
                            @selected($selectedSort === 'price_low')
                        >
                            Lower Price
                        </option>

                        <option
                            value="price_high"
                            @selected($selectedSort === 'price_high')
                        >
                            Higher Price
                        </option>

                        <option
                            value="name_asc"
                            @selected($selectedSort === 'name_asc')
                        >
                            Name A - Z
                        </option>

                        <option
                            value="name_desc"
                            @selected($selectedSort === 'name_desc')
                        >
                            Name Z - A
                        </option>
                    </select>
                </div>


                {{-- Actions --}}
                <div class="col-12 col-md-6 col-lg-2">
                    <label class="form-label d-block">&nbsp;</label>
                    <div class="d-flex gap-2">
                        <button type="submit" class="store-add-cart flex-grow-1">
                            <i class="bi bi-funnel"></i>
                            Apply
                        </button>
                        @if($hasActiveFilters)
                            <a href="{{ $isCategoryContext ? route('storefront.category', $categoryContext->slug) : route('storefront.shop') }}"  class="btn btn-outline-secondary" title="Reset filter" >
                                <i class="bi bi-arrow-counterclockwise"></i>
                            </a>
                        @endif
                    </div>
                </div>
            </div>
        </form>


        {{-- Active filters --}}
        @if($hasActiveFilters)
            <div class="store-active-filters">
                <span class="store-active-filters-label">Active Filter:</span>

                {{-- Search --}}
                @if ($selectedSearch)
                    <span class="store-filter-badge">
                        <i class="bi bi-search"></i>
                        {{ $selectedSearch }}
                    </span>

                @endif


                {{-- Category --}}
                @if ($selectedCategorySlug)
                    @php
                        $activeCategory = $categoryContext;
                        if (! $activeCategory) {
                            $activeCategory = $categories->firstWhere('slug', $selectedCategorySlug);
                        }
                    @endphp

                    @if ($activeCategory)
                        <span class="store-filter-badge">
                            <i class="bi bi-grid"></i>
                            {{ $activeCategory->name }}
                        </span>
                    @endif
                @endif

                {{-- Subcategory --}}
                @if ($selectedSubCategorySlug)

                    @php
                        $activeSubCategory = null;

                        if ($categoryContext) {
                            $activeSubCategory = $categoryContext
                                ->subCategories
                                ->firstWhere(
                                    'slug',
                                    $selectedSubCategorySlug
                                );
                        } else {
                            foreach ($categories as $filterCategory) {
                                $match = $filterCategory
                                    ->subCategories
                                    ->firstWhere(
                                        'slug',
                                        $selectedSubCategorySlug
                                    );

                                if ($match) {
                                    $activeSubCategory = $match;
                                    break;
                                }
                            }
                        }
                    @endphp

                    @if ($activeSubCategory)

                        <span class="store-filter-badge">
                            <i class="bi bi-diagram-3"></i>
                            {{ $activeSubCategory->name }}
                        </span>

                    @endif

                @endif

                {{-- Sorting --}}
                @php
                    $sortLabels = [
                        'oldest' => 'Terlama',
                        'price_low' => 'Harga Terendah',
                        'price_high' => 'Harga Tertinggi',
                        'name_asc' => 'Nama A - Z',
                        'name_desc' => 'Nama Z - A',
                    ];
                @endphp

                @if ($selectedSort)
                    <span class="store-filter-badge">
                        <i class="bi bi-sort-down"></i>
                        {{ $sortLabels[$selectedSort] ?? 'Terbaru' }}
                    </span>

                @endif

                <a
                    href="{{ $isCategoryContext
                        ? route('storefront.category', $categoryContext->slug)
                        : route('storefront.shop')
                    }}"
                    class="store-filter-reset"
                >
                    <i class="bi bi-x-lg"></i>
                    Reset
                </a>
            </div>
        @endif
    </div>
</div>
