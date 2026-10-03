<?php

namespace Database\Factories;

use App\Models\Order;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends \Illuminate\Database\Eloquent\Factories\Factory<\App\Models\Payment>
 */
class PaymentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'order_id' => Order::factory(),

            'provider' => 'stripe',
            'reference_id' => null,

            'stripe_payment_intent_id' => 'pi_' . fake()->unique()->regexify('[A-Za-z0-9]{24}'),

            'midtrans_snap_token' => null,

            'currency' => 'IDR',
            'status' => 'pending',
            'payment_method' => 'card',

            'transaction_id' => null,
            'payment_type' => 'card',
            'transaction_status' => 'pending',
            'fraud_status' => null,
            'status_code' => '200',

            'gross_amount' => 100000,

            'paid_at' => null,
            'expires_at' => null,

            'metadata' => null,
        ];
    }
}
