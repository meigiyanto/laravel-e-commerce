<?php

namespace Database\Factories;

use App\Models\Order;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Order>
 */
class OrderFactory extends Factory
{
    public function definition(): array
    {
        $subtotal = fake()->randomFloat(2, 10000, 1000000);
        $shippingCost = fake()->randomFloat(2, 0, 50000);

        return [
            'user_id' => User::factory(),
            'order_number' => 'ORD-'.fake()->unique()->numerify('########'),
            'status' => 'pending',
            'customer_name' => fake()->name(),
            'phone' => fake()->numerify('08##########'),
            'shipping_address' => fake()->address(),
            'notes' => null,
            'subtotal' => $subtotal,
            'shipping_cost' => $shippingCost,
            'total' => $subtotal + $shippingCost,
        ];
    }
}
