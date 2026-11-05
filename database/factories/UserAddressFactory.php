<?php

namespace Database\Factories;

use App\Models\User;
use App\Models\UserAddress;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<UserAddress> */
class UserAddressFactory extends Factory
{
    protected $model = UserAddress::class;

    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'label' => fake()->randomElement(['Rumah', 'Kantor']),
            'recipient_name' => fake()->name(),
            'phone' => fake()->numerify('08##########'),
            'address_line' => fake()->streetAddress(),
            'province' => 'DKI Jakarta',
            'city' => 'Jakarta Selatan',
            'district' => fake()->city(),
            'village' => fake()->streetName(),
            'postal_code' => fake()->numerify('#####'),
            'country' => 'Indonesia',
            'notes' => fake()->optional()->sentence(),
            'is_default' => false,
        ];
    }

    public function default(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_default' => true,
        ]);
    }
}