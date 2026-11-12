<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\SubCategory;
use App\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Facades\DB;

/** @extends Factory<Product> */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(3, true);

        return [
            'tenant_id' => fn () => app(TenantContext::class)->current()?->getKey() ?? Tenant::factory(),
            'sub_category_id' => SubCategory::factory(),
            'category_id' => fn (array $attributes) => DB::table('sub_categories')->where('id', $attributes['sub_category_id'])->value('category_id'),
            'name' => $name,
            'slug' => str()->slug($name),
            'description' => fake()->sentence(),
            'price' => fake()->randomFloat(2, 10000, 10000000),
            'stock' => 10,
            'image' => null,
        ];
    }
}