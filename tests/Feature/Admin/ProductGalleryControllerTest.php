<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductGalleryControllerTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        Storage::fake('public');
    }

    public function test_admin_can_upload_valid_product_images(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('admin.product-gallery.upload'), [
            'images' => [UploadedFile::fake()->image('sneaker.jpg')],
        ]);

        $response->assertRedirect(route('admin.product-gallery.index', ['status' => 'unused']));
        $this->assertCount(1, Storage::disk('public')->files('products'));
    }

    public function test_upload_rejects_unsupported_image_types(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('admin.product-gallery.upload'), [
            'images' => [UploadedFile::fake()->create('document.pdf', 100, 'application/pdf')],
        ]);

        $response->assertSessionHasErrors('images.0');
        $this->assertSame([], Storage::disk('public')->files('products'));
    }

    public function test_admin_can_replace_an_image_and_database_references_are_updated(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create(['image' => 'products/old.jpg']);
        Storage::disk('public')->put('products/old.jpg', 'old image content');
        DB::table('product_images')->insert([
            'product_id' => $product->id,
            'product_variant_id' => null,
            'path' => 'products/old.jpg',
            'alt_text' => null,
            'sort_order' => 0,
            'is_primary' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($admin)->put(
            route('admin.product-gallery.replace', ['filename' => 'old.jpg']),
            ['image' => UploadedFile::fake()->image('replacement.png')]
        );

        $response->assertRedirect(route('admin.product-gallery.index'));
        $newPath = DB::table('products')->where('id', $product->id)->value('image');
        $this->assertNotSame('products/old.jpg', $newPath);
        $this->assertStringStartsWith('products/', $newPath);
        $this->assertSame($newPath, DB::table('product_images')->where('product_id', $product->id)->value('path'));
        Storage::disk('public')->assertExists($newPath);
        Storage::disk('public')->assertMissing('products/old.jpg');
    }


    public function test_replace_updates_all_product_and_variant_gallery_references_with_legacy_path_formats(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create(['image' => 'products/shared-old.jpg']);
        $secondProduct = Product::factory()->create(['image' => '/storage/products/shared-old.jpg']);
        Storage::disk('public')->put('products/shared-old.jpg', 'old image content');

        $variantId = DB::table('product_variants')->insertGetId([
            'product_id' => $secondProduct->id,
            'sku' => 'SKU-GALLERY-REPLACE-ALIAS',
            'barcode' => null,
            'name' => 'Variant replacement test',
            'price' => 10000,
            'compare_at_price' => null,
            'cost_price' => null,
            'weight' => null,
            'is_default' => true,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('product_images')->insert([
            [
                'product_id' => $product->id,
                'product_variant_id' => null,
                'path' => 'products/shared-old.jpg',
                'alt_text' => 'Product image',
                'sort_order' => 0,
                'is_primary' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'product_id' => $secondProduct->id,
                'product_variant_id' => $variantId,
                'path' => 'storage/products/shared-old.jpg',
                'alt_text' => 'Variant image',
                'sort_order' => 0,
                'is_primary' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $response = $this->actingAs($admin)->put(
            route('admin.product-gallery.replace', ['filename' => 'shared-old.jpg']),
            ['image' => UploadedFile::fake()->image('shared-replacement.webp')]
        );

        $response->assertRedirect(route('admin.product-gallery.index'));

        $newProductPath = DB::table('products')->where('id', $product->id)->value('image');
        $newSecondProductPath = DB::table('products')->where('id', $secondProduct->id)->value('image');
        $newProductImagePath = DB::table('product_images')
            ->where('product_id', $product->id)
            ->value('path');
        $newVariantImagePath = DB::table('product_images')
            ->where('product_variant_id', $variantId)
            ->value('path');

        $this->assertStringStartsWith('products/', $newProductPath);
        $this->assertStringStartsWith('/storage/products/', $newSecondProductPath);
        $this->assertSame($newProductPath, $newProductImagePath);
        
        $canonicalNewPath = ltrim(
            str_replace('/storage/', '', $newSecondProductPath),
            '/'
        );
        
        $canonicalVariantPath = ltrim(
            str_replace('/storage/', '', $newVariantImagePath),
            '/'
        );
        
        $this->assertSame($canonicalNewPath, $canonicalVariantPath);
        
        Storage::disk('public')->assertExists($canonicalNewPath);
        Storage::disk('public')->assertMissing('products/shared-old.jpg');
    }

    public function test_replace_rejects_invalid_files_without_changing_existing_references(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create(['image' => 'products/keep-this.jpg']);
        Storage::disk('public')->put('products/keep-this.jpg', 'original image content');

        $response = $this->actingAs($admin)->put(
            route('admin.product-gallery.replace', ['filename' => 'keep-this.jpg']),
            ['image' => UploadedFile::fake()->create('not-an-image.pdf', 100, 'application/pdf')]
        );

        $response->assertSessionHasErrors('image');
        $this->assertSame(
            'products/keep-this.jpg',
            DB::table('products')->where('id', $product->id)->value('image')
        );
        Storage::disk('public')->assertExists('products/keep-this.jpg');
        $this->assertCount(1, Storage::disk('public')->files('products'));
    }

    public function test_delete_is_rejected_when_image_is_used_by_a_product(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Product::factory()->create(['image' => 'products/used.jpg']);
        Storage::disk('public')->put('products/used.jpg', 'image content');

        $response = $this->actingAs($admin)->delete(
            route('admin.product-gallery.destroy', ['filename' => 'used.jpg'])
        );

        $response->assertSessionHas('error');
        Storage::disk('public')->assertExists('products/used.jpg');
    }

    public function test_delete_is_rejected_when_image_is_used_by_a_product_variant_gallery_record(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $product = Product::factory()->create(['image' => null]);
        Storage::disk('public')->put('products/variant.jpg', 'image content');
        $variantId = DB::table('product_variants')->insertGetId([
            'product_id' => $product->id,
            'sku' => 'SKU-GALLERY-VARIANT-1',
            'barcode' => null,
            'name' => 'Variant gallery test',
            'price' => 10000,
            'compare_at_price' => null,
            'cost_price' => null,
            'weight' => null,
            'is_default' => true,
            'status' => 'active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('product_images')->insert([
            'product_id' => $product->id,
            'product_variant_id' => $variantId,
            'path' => 'products/variant.jpg',
            'alt_text' => null,
            'sort_order' => 0,
            'is_primary' => false,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $response = $this->actingAs($admin)->delete(
            route('admin.product-gallery.destroy', ['filename' => 'variant.jpg'])
        );

        $response->assertSessionHas('error');
        Storage::disk('public')->assertExists('products/variant.jpg');
    }

    public function test_admin_can_delete_an_unused_image(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        Storage::disk('public')->put('products/unused.webp', 'image content');

        $response = $this->actingAs($admin)->delete(
            route('admin.product-gallery.destroy', ['filename' => 'unused.webp'])
        );

        $response->assertSessionHas('success');
        Storage::disk('public')->assertMissing('products/unused.webp');
    }
}
