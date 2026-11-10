<?php

namespace Tests\Feature;

use App\Models\Attribute;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SubCategory;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SingleStoreCatalogScopeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['operational.mode' => 'single_store']);
    }

    private function createCategory(string $slug = 'shoes'): Category
    {
        return Category::create(['name' => 'Shoes', 'slug' => $slug]);
    }

    private function createSubCategory(Category $category, string $slug = 'sneakers'): SubCategory
    {
        return SubCategory::create(['category_id' => $category->id, 'name' => 'Sneakers', 'slug' => $slug]);
    }

    private function createProduct(Category $category, SubCategory $subCategory, string $slug = 'daily-sneaker'): Product
    {
        return Product::create(['category_id' => $category->id, 'sub_category_id' => $subCategory->id, 'name' => 'Daily Sneaker', 'slug' => $slug, 'price' => 100000, 'stock' => 10]);
    }

    public function test_catalog_records_are_created_without_tenant(): void
    {
        $this->assertDatabaseCount('tenants', 0);

        $category = $this->createCategory();
        $subCategory = $this->createSubCategory($category);
        $product = $this->createProduct($category, $subCategory);

        $attribute = Attribute::create(['name' => 'Color', 'slug' => 'color', 'type' => 'select', 'is_variant' => true, 'sort_order' => 0]);

        $variant = ProductVariant::create(['product_id' => $product->id, 'sku' => 'SKU-001', 'barcode' => 'BARCODE-001', 'price' => 100000, 'status' => 'active']);

        foreach ([$category, $subCategory, $product, $attribute, $variant] as $record) {
            $this->assertNull($record->tenant_id);
        }

        $this->assertDatabaseCount('tenants', 0);
    }

    public function test_single_store_query_only_returns_records_without_tenant(): void
    {
        $category = $this->createCategory();
        $subCategory = $this->createSubCategory($category);
        $product = $this->createProduct($category, $subCategory);

        $this->assertSame([$category->id], Category::query()->pluck('id')->all());
        $this->assertSame([$subCategory->id], SubCategory::query()->pluck('id')->all());
        $this->assertSame([$product->id], Product::query()->pluck('id')->all());
    }

    public function test_duplicate_category_slug_is_rejected(): void
    {
        $this->createCategory();

        $this->expectException(QueryException::class);
        $this->createCategory();
    }

    public function test_duplicate_subcategory_slug_is_rejected(): void
    {
        $category = $this->createCategory();
        $this->createSubCategory($category);

        $this->expectException(QueryException::class);
        $this->createSubCategory($category);
    }

    public function test_duplicate_product_slug_is_rejected(): void
    {
        $category = $this->createCategory();
        $subCategory = $this->createSubCategory($category);
        $this->createProduct($category, $subCategory);

        $this->expectException(QueryException::class);
        $this->createProduct($category, $subCategory);
    }

    public function test_duplicate_attribute_slug_is_rejected(): void
    {
        Attribute::create(['name' => 'Color', 'slug' => 'color', 'type' => 'select', 'is_variant' => true, 'sort_order' => 0]);

        $this->expectException(QueryException::class);

        Attribute::create(['name' => 'Color', 'slug' => 'color', 'type' => 'select', 'is_variant' => true, 'sort_order' => 0]);
    }

    public function test_duplicate_sku_is_rejected(): void
    {
        $category = $this->createCategory();
        $subCategory = $this->createSubCategory($category);
        $product = $this->createProduct($category, $subCategory);

        ProductVariant::create(['product_id' => $product->id, 'sku' => 'SKU-001', 'price' => 100000]);

        $this->expectException(QueryException::class);

        ProductVariant::create(['product_id' => $product->id, 'sku' => 'SKU-001', 'price' => 100000]);
    }

    public function test_duplicate_barcode_is_rejected(): void
    {
        $category = $this->createCategory();
        $subCategory = $this->createSubCategory($category);
        $product = $this->createProduct($category, $subCategory);

        ProductVariant::create(['product_id' => $product->id, 'sku' => 'SKU-001', 'barcode' => 'BARCODE-001', 'price' => 100000]);
        ProductVariant::create(['product_id' => $product->id, 'sku' => 'SKU-002', 'barcode' => null, 'price' => 100000]);

        $this->expectException(QueryException::class);

        ProductVariant::create(['product_id' => $product->id, 'sku' => 'SKU-003', 'barcode' => 'BARCODE-001', 'price' => 100000]);
    }
}