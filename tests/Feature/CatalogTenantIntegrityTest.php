<?php

namespace Tests\Feature;

use App\Models\Attribute;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\SubCategory;
use App\Models\Tenant;
use App\Models\User;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;
use LogicException;

class CatalogTenantIntegrityTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['operational.mode' => 'multi_tenant']);
        $tenant = Tenant::factory()->create();
        app(TenantContext::class)->set($tenant);
    }
    
    private function runForTenant(Tenant $tenant, callable $callback): mixed
    {
        return app(TenantContext::class)->run($tenant, $callback);
    }

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
        /*
        return Category::create([
            'tenant_id' => $tenant->id,
            'name' => $name,
            'slug' => strtolower(str_replace(' ', '-', $name)),
        ]);
        */

        return $this->runForTenant($tenant, fn () => Category::create([
            'tenant_id' => $tenant->id,
            'name' => $name,b
            'slug' => strtolower(str_replace(' ', '-', $name)),
        ]));
    }

    private function createSubCategory(
        Tenant $tenant,
        Category $category
    ): SubCategory {
        // return SubCategory::create([
        return $this->runForTenant($tenant, fn () => SubCategory::create([
            'tenant_id' => $tenant->id,
            'category_id' => $category->id,
            'name' => 'Test Subcategory',
            'slug' => 'test-subcategory-'.$tenant->id.'-'.$category->id,
        ]));
    }

    private function createProduct(
        Tenant $tenant,
        Category $category
    ): Product {
        $subCategory = $this->createSubCategory($tenant, $category);

        // return Product::create([
        return $this->runForTenant($tenant, fn () => Product::create([
            'tenant_id' => $tenant->id,
            'category_id' => $category->id,
            'sub_category_id' => $subCategory->id,
            'name' => 'Test Product',
            'slug' => 'test-product-'.$tenant->id,
            'price' => 100000,
            'stock' => 10,
        ]));
    }

    private function createVariant(
        Tenant $tenant,
        Product $product
    ): ProductVariant {
        // return ProductVariant::create([
        return $this->runForTenant($tenant, fn () => ProductVariant::create([
            'tenant_id' => $tenant->id,
            'product_id' => $product->id,
            'sku' => 'SKU-'.$tenant->id,
            'name' => 'Default Variant',
            'price' => 100000,
            'is_default' => true,
            'status' => 'active',
        ]));
    }

    public function test_product_cannot_reference_category_from_another_tenant(): void
    {
        $tenantA = $this->createTenant('Tenant A');
        $tenantB = $this->createTenant('Tenant B');

        $categoryB = $this->createCategory($tenantB, 'Category B');
        $subCategoryB = $this->createSubCategory($tenantB, $categoryB);

        /*
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
        */
        
        $this->expectException(LogicException::class);

        $this->runForTenant($tenantA, fn () => Product::create([
            'tenant_id' => $tenantA->id,
            'category_id' => $categoryB->id,
            'sub_category_id' => $subCategoryB->id,
            'name' => 'Invalid Product',
            'slug' => 'invalid-product',
            'price' => 100000,
            'stock' => 1,
        ]));
    }

    public function test_variant_cannot_reference_product_from_another_tenant(): void
    {
        $tenantA = $this->createTenant('Tenant A');
        $tenantB = $this->createTenant('Tenant B');

        $categoryB = $this->createCategory($tenantB, 'Category B');
        $productB = $this->createProduct($tenantB, $categoryB);

        $this->expectException(QueryException::class);

        // ProductVariant::create([
        $this->runForTenant($tenantA, fn () => ProductVariant::create([
            'tenant_id' => $tenantA->id,
            'product_id' => $productB->id,
            'sku' => 'INVALID-SKU',
            'price' => 100000,
        ]));
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

        // ProductVariant::create([
        $this->runForTenant($tenantA, fn () => Product::create([
            'tenant_id' => $tenantA->id,
            'product_id' => $productA->id,
            'category_id' => $categoryA->id,
            'sku' => 'SHARED-SKU',
            'price' => 100000,
        ]));

        // ProductVariant::create([
        $this->runForTenant($tenantA, fn () => ProductVariant::create([
            'tenant_id' => $tenantB->id,
            'product_id' => $productB->id,
            'category_id' => $categoryB->id,
            'sku' => 'SHARED-SKU',
            'price' => 100000,
        ]));

        $this->assertDatabaseCount('product_variants', 2);
    }

    public function test_catalog_slugs_can_be_reused_across_tenants(): void
    {
        $tenantA = $this->createTenant('Tenant A');
        $tenantB = $this->createTenant('Tenant B');

        // Category::create([
        $this->runForTenant($tenantA, fn () => Category::create([
            'tenant_id' => $tenantA->id, 
            'name' => 'Shoes', 
            'slug' => 'shoes'
        ]));
        
        // Category::create([
        $this->runForTenant($tenantB, fn () => Category::create([
            'tenant_id' => $tenantB->id, 
            'name' => 'Shoes', 
            'slug' => 'shoes'
        ]));

        $categoryA = $this->createCategory($tenantA, 'Fashion');
        $categoryB = $this->createCategory($tenantB, 'Fashion');
        
        /*
        SubCategory::create(['tenant_id' => $tenantA->id, 'category_id' => $categoryA->id, 'name' => 'Sneakers', 'slug' => 'sneakers']);
        SubCategory::create(['tenant_id' => $tenantB->id, 'category_id' => $categoryB->id, 'name' => 'Sneakers', 'slug' => 'sneakers']);
        */
        
        $this->runForTenant($tenantA, fn () => SubCategory::create([
            'tenant_id' => $tenantA->id, 
            'category_id' => $categoryA->id, 
            'name' => 'Sneakers', 
            'slug' => 'sneakers'
        ]));
        
        $this->runForTenant($tenantB, fn () => SubCategory::create([
            'tenant_id' => $tenantB->id, 
            'category_id' => $categoryB->id, 
            'name' => 'Sneakers', 
            'slug' => 'sneakers'
        ]));

        $subA = $this->createSubCategory($tenantA, $categoryA);
        $subB = $this->createSubCategory($tenantB, $categoryB);
        
        // Product::create([
        $this->runForTenant($tenantA, fn () => Product::create([
            'tenant_id' => $tenantA->id, 
            'category_id' => $categoryA->id, 
            'sub_category_id' => $subA->id, 
            'name' => 'Sneaker', 
            'slug' => 'sneaker', 
            'price' => 100000, 
            'stock' => 1
        ]));
        
        // Product::create([
        $this->runForTenant($tenantB, fn () => Product::create([
            'tenant_id' => $tenantB->id, 
            'category_id' => $categoryB->id, 
            'sub_category_id' => $subB->id, 
            'name' => 'Sneaker', 
            'slug' => 'sneaker', 
            'price' => 100000, 
            'stock' => 1
        ]));
        
        
        Attribute::create(['tenant_id' => $tenantA->id, 'name' => 'Color', 'slug' => 'color', 'type' => 'select', 'is_variant' => true, 'sort_order' => 0]);
        Attribute::create(['tenant_id' => $tenantB->id, 'name' => 'Color', 'slug' => 'color', 'type' => 'select', 'is_variant' => true, 'sort_order' => 0]);

        $this->assertSame(2, DB::table('categories')->where('slug', 'shoes')->count());
        $this->assertSame(2, DB::table('sub_categories')->where('slug', 'sneakers')->count());
        $this->assertSame(2, DB::table('products')->where('slug', 'sneaker')->count());
        $this->assertSame(2, DB::table('attributes')->where('slug', 'color')->count());
    }

    public function test_same_barcode_can_exist_in_different_tenants(): void
    {
        $tenantA = $this->createTenant('Tenant A');
        $tenantB = $this->createTenant('Tenant B');

        $categoryA = $this->createCategory($tenantA, 'Category A');
        $categoryB = $this->createCategory($tenantB, 'Category B');
        $productA = $this->createProduct($tenantA, $categoryA);
        $productB = $this->createProduct($tenantB, $categoryB);

        // ProductVariant::create([
        $this->runForTenant($tenantA, fn () => ProductVariant::create([
            'tenant_id' => $tenantA->id,
            'product_id' => $productA->id, 
            'sku' => 'SKU-A', 
            'barcode' => 'SHARED-BARCODE', 
            'price' => 100000
        ]));
        
        // ProductVariant::create([
        $this->runForTenant($tenantB, fn () => ProductVariant::create([
            'tenant_id' => $tenantB->id, 
            'product_id' => $productB->id, 
            'sku' => 'SKU-B', 
            'barcode' => 'SHARED-BARCODE', 
            'price' => 100000
        ]));

        $this->assertSame(2, DB::table('product_variants')->where('barcode', 'SHARED-BARCODE')->count());
    }

    public function test_duplicate_sku_is_rejected_within_same_tenant(): void
    {
        $tenant = $this->createTenant('Tenant A');
        $category = $this->createCategory($tenant, 'Category A');
        $product = $this->createProduct($tenant, $category);

        // ProductVariant::create([
        $this->runForTenant($tenant, fn () => ProductVariant::create([
            'tenant_id' => $tenant->id, 
            'product_id' => $product->id, 
            'sku' => 'DUPLICATE-SKU', 
            'price' => 100000
        ]));

        $this->expectException(QueryException::class);

        // ProductVariant::create([
        $this->runForTenant($tenant, fn () => ProductVariant::create([
            'tenant_id' => $tenant->id, 
            'product_id' => $product->id, 
            'sku' => 'DUPLICATE-SKU', 
            'price' => 100000
        ]));
    }

    public function test_duplicate_barcode_is_rejected_within_same_tenant(): void
    {
        $tenant = $this->createTenant('Tenant A');
        $category = $this->createCategory($tenant, 'Category A');
        $product = $this->createProduct($tenant, $category);

        // ProductVariant::create([
        $this->runForTenant($tenant, fn () => ProductVariant::create([
            'tenant_id' => $tenant->id, 
            'product_id' => $product->id, 
            'sku' => 'SKU-A', 
            'barcode' => 'DUPLICATE-BARCODE', 
            'price' => 100000
        ]));

        $this->expectException(QueryException::class);

        // ProductVariant::create([
        $this->runForTenant($tenant, fn () => ProductVariant::create([
            'tenant_id' => $tenant->id, 
            'product_id' => $product->id, 
            'sku' => 'SKU-B', 
            'barcode' => 'DUPLICATE-BARCODE', 
            'price' => 100000
        ]));
    }
}