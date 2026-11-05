<?php

namespace Database\Factories;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    public function definition(): array
    {
        return [
            'created_by_user_id' => User::query()->value('id') ?? User::factory(),
            'name' => fake()->company(),
            'slug' => Str::slug(fake()->unique()->company()),
            'status' => 'active',
        ];
    }
}
