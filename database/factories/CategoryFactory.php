<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Tenant;
use App\Support\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'tenant_id' => fn () => config('operational.mode') === 'multi_tenant' ? app(TenantContext::class)->require()->getKey() : null,
            'name' => $name,
            'slug' => str()->slug($name),
            'image' => null,
            'description' => fake()->optional()->sentence(),
        ];
    }
}
