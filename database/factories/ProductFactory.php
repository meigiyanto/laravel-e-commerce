<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\SubCategory;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Product>
 */
class ProductFactory extends Factory
{
    protected $model = Product::class;

    public function definition(): array
    {
        $subCategory = SubCategory::factory()->create();
        $name = fake()->unique()->words(3, true);

        return [
            'category_id' => $subCategory->category_id,
            'sub_category_id' => $subCategory->id,
            'name' => $name,
            'slug' => str()->slug($name),
            'description' => fake()->sentence(),
            'price' => fake()->randomFloat(2, 10000, 10000000),
            'stock' => 10,
            'image' => null,
        ];
    }
}
