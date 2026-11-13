<?php

namespace Tests\Feature\Admin;

use App\Models\Product;
use App\Models\Tenant;
use App\Models\TenantMembership;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use App\Http\Middleware\ResolveTenantFromDomain;
use App\Support\Tenancy\TenantContext;
use Tests\TestCase;

class ProductGalleryControllerTest extends TestCase
{
    use RefreshDatabase;
    protected Tenant $tenant;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');

        $this->tenant = Tenant::factory()->create([
            'name' => 'Default Tenant',
            'slug' => 'default',
            'status' => 'active',
        ]);
    }

    private function admin(): User
    {
        $admin = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        TenantMembership::create([
            'tenant_id' => $this->tenant->id,
            'user_id' => $admin->id,
            'role' => TenantMembership::ROLE_ADMIN,
            'status' => 'active',
        ]);

        return $admin;
    }

    public function test_admin_can_upload_valid_product_images(): void
    {
        $admin = $this->admin(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('admin.product-gallery.upload'), [
            'images' => [UploadedFile::fake()->image('sneaker.jpg')],
        ]);

        $response->assertRedirect(route('admin.product-gallery.index', ['status' => 'unused']));
        $this->assertCount(1, Storage::disk('public')->files('products'));
    }

    public function test_upload_rejects_unsupported_image_types(): void
    {
        $admin = $this->admin(['role' => 'admin']);

        $response = $this->actingAs($admin)->post(route('admin.product-gallery.upload'), [
            'images' => [UploadedFile::fake()->create('document.pdf', 100, 'application/pdf')],
        ]);

        $response->assertSessionHasErrors('images.0');
        $this->assertSame([], Storage::disk('public')->files('products'));
    }

    public function test_admin_can_replace_an_image_and_database_references_are_updated(): void
    {
        $admin = $this->admin(['role' => 'admin']);
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
        $admin = $this->admin(['role' => 'admin']);
        $product = Product::factory()->create(['image' => 'products/shared-old.jpg']);
        $secondProduct = Product::factory()->create(['image' => '/storage/products/shared-old.jpg']);
        Storage::disk('public')->put('products/shared-old.jpg', 'old image content');

        $variantId = DB::table('product_variants')->insertGetId([
            'tenant_id' => $secondProduct->tenant_id,
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
        $admin = $this->admin(['role' => 'admin']);
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
        $admin = $this->admin(['role' => 'admin']);
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
        $admin = $this->admin(['role' => 'admin']);
        $product = Product::factory()->create(['image' => null]);
        Storage::disk('public')->put('products/variant.jpg', 'image content');

        $variantId = DB::table('product_variants')->insertGetId([
            'tenant_id' => $product->tenant_id,
            'product_id' => $product->id,
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
        $admin = $this->admin(['role' => 'admin']);
        Storage::disk('public')->put('products/unused.webp', 'image content');

        $response = $this->actingAs($admin)->delete(
            route('admin.product-gallery.destroy', ['filename' => 'unused.webp'])
        );

        $response->assertSessionHas('success');
        Storage::disk('public')->assertMissing('products/unused.webp');
    }

    public function test_replace_only_updates_current_tenant_and_preserves_shared_file_until_last_reference_is_removed(): void
    {
        config()->set('operational.mode', 'multi_tenant');
        $this->withoutMiddleware(ResolveTenantFromDomain::class);

        $tenantA = $this->tenant;
        $tenantB = Tenant::factory()->create([
            'name' => 'Tenant B',
            'slug' => 'tenant-b',
            'status' => 'active',
        ]);

        $adminA = $this->admin();

        $adminB = User::factory()->create([
            'role' => User::ROLE_ADMIN,
        ]);

        TenantMembership::create([
            'tenant_id' => $tenantB->id,
            'user_id' => $adminB->id,
            'role' => TenantMembership::ROLE_ADMIN,
            'status' => 'active',
        ]);

        $context = app(TenantContext::class);

        $productA = $context->run($tenantA, fn () => Product::factory()->create([
            'image' => 'products/shared-image.jpg',
        ]));

        $productB = $context->run($tenantB, fn () => Product::factory()->create([
            'image' => '/storage/products/shared-image.jpg',
        ]));

        DB::table('product_images')->insert([
            [
                'product_id' => $productA->id,
                'product_variant_id' => null,
                'path' => 'products/shared-image.jpg',
                'alt_text' => 'Tenant A image',
                'sort_order' => 0,
                'is_primary' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'product_id' => $productB->id,
                'product_variant_id' => null,
                'path' => 'storage/products/shared-image.jpg',
                'alt_text' => 'Tenant B image',
                'sort_order' => 0,
                'is_primary' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        Storage::disk('public')->put(
            'products/shared-image.jpg',
            'shared original image'
        );

        // Tenant A mengganti gambar.
        $responseA = $context->run($tenantA, fn () =>
            $this->actingAs($adminA)->put(
                route('admin.product-gallery.replace', [
                    'filename' => 'shared-image.jpg',
                ]),
                ['image' => UploadedFile::fake()->image('tenant-a-replacement.png')]
            )
        );

        $responseA->assertRedirect(route('admin.product-gallery.index'));

        $imageA = DB::table('products')
        ->where('id', $productA->id)
        ->value('image');

        $imageB = DB::table('products')
        ->where('id', $productB->id)
        ->value('image');

        $galleryA = DB::table('product_images')
        ->where('product_id', $productA->id)
        ->value('path');

        $galleryB = DB::table('product_images')
        ->where('product_id', $productB->id)
        ->value('path');

        // Referensi Tenant A berubah.
        $this->assertNotSame('products/shared-image.jpg', $imageA);
        $this->assertSame($imageA, $galleryA);

        // Referensi Tenant B tidak boleh berubah.
        $this->assertSame('/storage/products/shared-image.jpg', $imageB);
        $this->assertSame('storage/products/shared-image.jpg', $galleryB);

        // File lama masih digunakan Tenant B.
        Storage::disk('public')->assertExists('products/shared-image.jpg');

        // Tenant A tidak boleh menghapus file yang masih dipakai Tenant B.
        $deleteResponse = $context->run($tenantA, fn () =>
            $this->actingAs($adminA)->delete(
                route('admin.product-gallery.destroy', [
                    'filename' => 'shared-image.jpg',
                ])
            )
        );

        $deleteResponse->assertSessionHas('error');
        Storage::disk('public')->assertExists('products/shared-image.jpg');

        // Tenant B kemudian mengganti referensinya sendiri.
        $responseB = $context->run($tenantB, fn () =>
            $this->actingAs($adminB)->put(
                route('admin.product-gallery.replace', [
                    'filename' => 'shared-image.jpg',
                ]),
                ['image' => UploadedFile::fake()->image('tenant-b-replacement.webp')]
            )
        );

        $responseB->assertRedirect(route('admin.product-gallery.index'));

        // Setelah referensi terakhir berpindah, file lama boleh dihapus.
        Storage::disk('public')->assertMissing('products/shared-image.jpg');

        // Pastikan penggantian Tenant B tidak mengubah referensi Tenant A.
        $this->assertSame(
            $imageA,
            DB::table('products')->where('id', $productA->id)->value('image')
        );
    }

    public function test_single_store_staff_can_view_gallery_but_cannot_upload_images(): void
    {
        config()->set('operational.mode', 'single_store');

        $staff = User::factory()->create([
            'role' => User::ROLE_STAFF,
        ]);

        $this->actingAs($staff)->get(route('admin.product-gallery.index'))->assertOk();

        $this->actingAs($staff)->post(route('admin.product-gallery.upload'), [
            'images' => [UploadedFile::fake()->image('forbidden.jpg')],
        ])->assertForbidden();
        
        $this->assertSame([], Storage::disk('public')->files('products'));
    }
}