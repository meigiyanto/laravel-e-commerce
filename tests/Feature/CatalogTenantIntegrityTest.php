<?php

namespace Tests\Feature;

use App\Models\Attribute;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SubCategory;
use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class CatalogTenantIntegrityTest extends TestCase
{
    use RefreshDatabase;

    private function createTenant(string $name): Tenant
    {
        return Tenant::create([
            'name' => $name,
            'slug' => strtolower(str_replace(' ', '-', $name)),
            'status' => 'active',
            'created_by_user_id' => User::factory()->create()->id,
        ]);
    }

    private function createCategory(Tenant $tenant, string $name): Category
    {
        return Category::create([
            'tenant_id' => $tenant->id,
            'name' => $name,
            'slug' => strtolower(str_replace(' ', '-', $name)),
        ]);
    }

    private function createSubCategory(
        Tenant $tenant,
        Category $category
    ): SubCategory {
        return SubCategory::create([
            'tenant_id' => $tenant->id,
            'category_id' => $category->id,
            'name' => 'Test Subcategory',
            'slug' => 'test-subcategory-'.$tenant->id.'-'.$category->id,
        ]);
    }

    private function createProduct(
        Tenant $tenant,
        Category $category
    ): Product {
        $subCategory = $this->createSubCategory($tenant, $category);

        return Product::create([
            'tenant_id' => $tenant->id,
            'category_id' => $category->id,
            'sub_category_id' => $subCategory->id,
            'name' => 'Test Product',
            'slug' => 'test-product-'.$tenant->id,
            'price' => 100000,
            'stock' => 10,
        ]);
    }

    private function createVariant(
        Tenant $tenant,
        Product $product
    ): ProductVariant {
        return ProductVariant::create([
            'tenant_id' => $tenant->id,
            'product_id' => $product->id,
            'sku' => 'SKU-'.$tenant->id,
            'name' => 'Default Variant',
            'price' => 100000,
            'is_default' => true,
            'status' => 'active',
        ]);
    }

    public function test_product_cannot_reference_category_from_another_tenant(): void
    {
        $tenantA = $this->createTenant('Tenant A');
        $tenantB = $this->createTenant('Tenant B');

        $categoryB = $this->createCategory($tenantB, 'Category B');
        $subCategoryB = $this->createSubCategory($tenantB, $categoryB);

        $this->expectException(QueryException::class);

        Product::create([
            'tenant_id' => $tenantA->id,
            'category_id' => $categoryB->id,
            'sub_category_id' => $subCategoryB->id,
            'name' => 'Invalid Product',
            'slug' => 'invalid-product',
            'price' => 100000,
            'stock' => 1,
        ]);
    }

    public function test_variant_cannot_reference_product_from_another_tenant(): void
    {
        $tenantA = $this->createTenant('Tenant A');
        $tenantB = $this->createTenant('Tenant B');

        $categoryB = $this->createCategory($tenantB, 'Category B');
        $productB = $this->createProduct($tenantB, $categoryB);

        $this->expectException(QueryException::class);

        ProductVariant::create([
            'tenant_id' => $tenantA->id,
            'product_id' => $productB->id,
            'sku' => 'INVALID-SKU',
            'price' => 100000,
        ]);
    }

    public function test_inventory_cannot_reference_variant_from_another_tenant(): void
    {
        $tenantA = $this->createTenant('Tenant A');
        $tenantB = $this->createTenant('Tenant B');

        $categoryB = $this->createCategory($tenantB, 'Category B');
        $productB = $this->createProduct($tenantB, $categoryB);
        $variantB = $this->createVariant($tenantB, $productB);

        $this->expectException(QueryException::class);

        DB::table('inventories')->insert([
            'tenant_id' => $tenantA->id,
            'product_variant_id' => $variantB->id,
            'quantity' => 10,
            'reserved_quantity' => 0,
            'reorder_level' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_attribute_value_cannot_be_attached_to_variant_from_another_tenant(): void
    {
        $tenantA = $this->createTenant('Tenant A');
        $tenantB = $this->createTenant('Tenant B');

        $categoryA = $this->createCategory($tenantA, 'Category A');
        $productA = $this->createProduct($tenantA, $categoryA);
        $variantA = $this->createVariant($tenantA, $productA);

        $attributeB = Attribute::create([
            'tenant_id' => $tenantB->id,
            'name' => 'Color',
            'slug' => 'color',
            'type' => 'select',
            'is_variant' => true,
            'sort_order' => 0,
        ]);

        $attributeValueB = DB::table('attribute_values')->insertGetId([
            'tenant_id' => $tenantB->id,
            'attribute_id' => $attributeB->id,
            'value' => 'Red',
            'slug' => 'red',
            'sort_order' => 0,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $this->expectException(QueryException::class);

        DB::table('product_variant_attribute_values')->insert([
            'tenant_id' => $tenantA->id,
            'product_variant_id' => $variantA->id,
            'attribute_value_id' => $attributeValueB,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    public function test_same_sku_can_exist_in_different_tenants(): void
    {
        $tenantA = $this->createTenant('Tenant A');
        $tenantB = $this->createTenant('Tenant B');

        $categoryA = $this->createCategory($tenantA, 'Category A');
        $categoryB = $this->createCategory($tenantB, 'Category B');

        $productA = $this->createProduct($tenantA, $categoryA);
        $productB = $this->createProduct($tenantB, $categoryB);

        ProductVariant::create([
            'tenant_id' => $tenantA->id,
            'product_id' => $productA->id,
            'sku' => 'SHARED-SKU',
            'price' => 100000,
        ]);

        ProductVariant::create([
            'tenant_id' => $tenantB->id,
            'product_id' => $productB->id,
            'sku' => 'SHARED-SKU',
            'price' => 100000,
        ]);

        $this->assertDatabaseCount('product_variants', 2);
    }
}
