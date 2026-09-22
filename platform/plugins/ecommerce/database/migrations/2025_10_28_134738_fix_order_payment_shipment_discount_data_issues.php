<?php

use Botble\Ecommerce\Models\Order;
use Botble\Payment\Models\Payment;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    public function up(): void
    {
        DB::transaction(function (): void {
            if (Schema::hasTable('payments') && is_plugin_active('payment')) {
                $this->fixDuplicatePayments();
                $this->fixOrphanedPayments();
            }

            $this->syncShipmentPrices();
            $this->cleanupIncompleteOrderDiscounts();
        });
    }

    protected function fixDuplicatePayments(): void
    {
        $duplicates = DB::table('payments as p1')
            ->select('p1.id')
            ->join('payments as p2', function ($join): void {
                $join->on('p1.order_id', '=', 'p2.order_id')
                    ->on('p1.payment_channel', '=', 'p2.payment_channel')
                    ->on('p1.id', '>', 'p2.id');
            })
            ->whereNotNull('p1.order_id')
            ->pluck('id');

        if ($duplicates->isNotEmpty()) {
            foreach ($duplicates->chunk(100) as $chunk) {
                DB::table('payments')->whereIn('id', $chunk)->delete();
            }
        }
    }

    protected function fixOrphanedPayments(): void
    {
        Payment::query()
            ->whereNull('order_id')
            ->whereNotNull('metadata')
            ->chunk(100, function ($payments): void {
                foreach ($payments as $payment) {
                    try {
                        $metadata = is_array($payment->metadata) ? $payment->metadata : json_decode($payment->metadata, true);

                        if (empty($metadata)) {
                            continue;
                        }

                        $orderId = null;

                        if (isset($metadata['order_id'])) {
                            $orderId = $metadata['order_id'];
                        } elseif (isset($metadata['notes']['order_id'])) {
                            $orderId = $metadata['notes']['order_id'];
                        }

                        if ($orderId) {
                            $orderExists = DB::table('ec_orders')->where('id', $orderId)->exists();

                            if ($orderExists) {
                                DB::table('payments')
                                    ->where('id', $payment->id)
                                    ->update(['order_id' => $orderId]);
                            }
                        }
                    } catch (Exception $e) {
                        Log::error('Failed to link orphaned payment: ' . $e->getMessage(), [
                            'payment_id' => $payment->id,
                        ]);
                    }
                }
            });
    }

    protected function syncShipmentPrices(): void
    {
        DB::statement('
            UPDATE ec_shipments s
            INNER JOIN ec_orders o ON s.order_id = o.id
            SET s.price = o.shipping_amount
            WHERE s.price != o.shipping_amount
        ');
    }

    protected function cleanupIncompleteOrderDiscounts(): void
    {
        Order::query()
            ->where('is_finished', false)
            ->whereNotNull('coupon_code')
            ->whereNotNull('user_id')
            ->chunk(100, function ($orders): void {
                foreach ($orders as $order) {
                    try {
                        $deletedCount = DB::table('ec_discount_customers')
                            ->where('customer_id', $order->user_id)
                            ->whereIn('discount_id', function ($query) use ($order): void {
                                $query->select('id')
                                    ->from('ec_discounts')
                                    ->where('code', $order->coupon_code);
                            })
                            ->delete();

                        if ($deletedCount > 0) {
                            DB::table('ec_discounts')
                                ->where('code', $order->coupon_code)
                                ->where('total_used', '>', 0)
                                ->decrement('total_used', $deletedCount);
                        }
                    } catch (Exception $e) {
                        Log::error('Failed to cleanup discount for incomplete order: ' . $e->getMessage(), [
                            'order_id' => $order->id,
                            'coupon_code' => $order->coupon_code,
                        ]);
                    }
                }
            });
    }
};
