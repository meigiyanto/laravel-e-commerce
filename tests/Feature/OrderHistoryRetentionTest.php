<?php

namespace Tests\Feature;

use App\Models\Order;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class OrderHistoryRetentionTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_deleting_user_preserves_order_and_detaches_user(): void
    {
        $user = User::factory()->create();

        $order = Order::create([
            'user_id' => $user->id,
            'order_number' => 'RETENTION-TEST-001',
            'status' => 'completed',
            'customer_name' => 'Test Customer',
            'phone' => '08123456789',
            'shipping_address' => 'Alamat pengujian',
            'subtotal' => 100000,
            'shipping_cost' => 10000,
            'total' => 110000,
        ]);

        $user->delete();

        $this->assertDatabaseHas('orders', [
            'id' => $order->id,
            'order_number' => 'RETENTION-TEST-001',
            'user_id' => null,
        ]);

        $this->assertDatabaseMissing('users', [
            'id' => $user->id,
        ]);
    }
}
