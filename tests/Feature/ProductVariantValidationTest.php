<?php

namespace Tests\Feature;

use App\Models\Inventory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductVariantValidationTest extends TestCase
{
    use RefreshDatabase;

    public function test_duplicate_sku_across_two_new_variants_is_rejected(): void
    {
        $admin = $this->admin();
        $product = Product::factory()->create();

        $payload = $this->validPayload($product, [
            $this->variantInput('New Variant A', 'DUPLICATE-SKU'),
            $this->variantInput('New Variant B', 'DUPLICATE-SKU'),
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.products.edit', $product))
            ->put(route('admin.products.update', $product), $payload);

        $response->assertRedirect(route('admin.products.edit', $product));
        $response->assertSessionHasErrors();

        $this->assertDatabaseMissing('product_variants', [
            'product_id' => $product->id,
            'sku' => 'DUPLICATE-SKU',
        ]);
    }

    public function test_duplicate_barcode_across_two_new_variants_is_rejected(): void
    {
        $admin = $this->admin();
        $product = Product::factory()->create();

        $payload = $this->validPayload($product, [
            $this->variantInput(
                'New Variant A',
                'SKU-BARCODE-A',
                'DUPLICATE-BARCODE'
            ),
            $this->variantInput(
                'New Variant B',
                'SKU-BARCODE-B',
                'DUPLICATE-BARCODE'
            ),
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.products.edit', $product))
            ->put(route('admin.products.update', $product), $payload);

        $response->assertRedirect(route('admin.products.edit', $product));
        $response->assertSessionHasErrors();

        $this->assertDatabaseMissing('product_variants', [
            'product_id' => $product->id,
            'barcode' => 'DUPLICATE-BARCODE',
        ]);
    }

    public function test_two_existing_variants_can_swap_skus_in_one_update(): void
    {
        $admin = $this->admin();
        $product = Product::factory()->create();

        $first = ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'Variant A',
            'sku' => 'SKU-A',
            'barcode' => null,
            'price' => 50000,
            'is_default' => true,
            'status' => 'active',
        ]);

        $second = ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'Variant B',
            'sku' => 'SKU-B',
            'barcode' => null,
            'price' => 60000,
            'is_default' => false,
            'status' => 'active',
        ]);

        Inventory::create([
            'product_variant_id' => $first->id,
            'quantity' => 10,
            'reserved_quantity' => 0,
            'reorder_level' => 0,
        ]);

        Inventory::create([
            'product_variant_id' => $second->id,
            'quantity' => 20,
            'reserved_quantity' => 0,
            'reorder_level' => 0,
        ]);

        $payload = $this->validPayload($product, [
            $this->variantInput(
                'Variant A',
                'SKU-B',
                null,
                $first->id,
                50000
            ),
            $this->variantInput(
                'Variant B',
                'SKU-A',
                null,
                $second->id,
                60000
            ),
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.products.edit', $product))
            ->put(route('admin.products.update', $product), $payload);

        $response->assertSessionHasNoErrors();

        $this->assertDatabaseHas('product_variants', [
            'id' => $first->id,
            'sku' => 'SKU-B',
        ]);

        $this->assertDatabaseHas('product_variants', [
            'id' => $second->id,
            'sku' => 'SKU-A',
        ]);
    }

    public function test_new_variant_is_saved_when_legacy_product_has_no_variants(): void
    {
        $admin = $this->admin();

        $product = Product::factory()->create([
            'stock' => 7,
        ]);

        $this->assertDatabaseMissing('product_variants', [
            'product_id' => $product->id,
        ]);

        $payload = $this->validPayload($product, [
            $this->variantInput(
                'New Color',
                'LEGACY-NEW-COLOR',
                null,
                null,
                65000,
                3
            ),
        ]);

        $response = $this->actingAs($admin)
            ->from(route('admin.products.edit', $product))
            ->put(route('admin.products.update', $product), $payload);

        $response->assertSessionHasNoErrors();

        // Produk lama harus tetap menyimpan stok legacy.
        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock' => 7,
        ]);

        // Varian yang dikirim dari formulir harus benar-benar tersimpan.
        $this->assertDatabaseHas('product_variants', [
            'product_id' => $product->id,
            'sku' => 'LEGACY-NEW-COLOR',
            'name' => 'New Color',
            'price' => 65000,
        ]);

        $newVariant = ProductVariant::where('product_id', $product->id)
            ->where('sku', 'LEGACY-NEW-COLOR')
            ->firstOrFail();

        $this->assertDatabaseHas('inventories', [
            'product_variant_id' => $newVariant->id,
            'quantity' => 3,
            'reserved_quantity' => 0,
        ]);

        // Migrasi ke sistem varian juga harus menghasilkan varian default.
        $this->assertDatabaseHas('product_variants', [
            'product_id' => $product->id,
            'is_default' => true,
        ]);

        $this->assertGreaterThanOrEqual(
            2,
            ProductVariant::where('product_id', $product->id)->count()
        );
    }

    private function admin(): User
    {
        return User::factory()->create([
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);
    }

    private function validPayload(
        Product $product,
        array $variants
    ): array {
        return [
            'category_id' => $product->category_id,
            'sub_category_id' => $product->sub_category_id,
            'name' => $product->name,
            'slug' => $product->slug,
            'description' => $product->description,
            'variants' => $variants,
        ];
    }

    private function variantInput(
        string $name,
        string $sku,
        ?string $barcode = null,
        ?int $id = null,
        int $price = 50000,
        int $quantity = 5
    ): array {
        return [
            'id' => $id,
            'name' => $name,
            'sku' => $sku,
            'barcode' => $barcode,
            'price' => $price,
            'compare_at_price' => null,
            'cost_price' => null,
            'weight' => null,
            'is_default' => false,
            'status' => 'active',
            'quantity' => $quantity,
            'reorder_level' => 0,
        ];
    }
}