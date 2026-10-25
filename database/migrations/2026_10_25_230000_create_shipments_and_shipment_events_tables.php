<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('order_id')
                ->constrained('orders')
                ->restrictOnDelete();

            $table->string('provider_code', 80);
            $table->string('service_code', 100)->nullable();
            $table->string('service_name')->nullable();
            $table->string('provider_shipment_id')->nullable();
            $table->string('tracking_number')->nullable();

            $table->string('status', 40)->default('pending');
            $table->decimal('shipping_cost', 15, 2)->default(0);

            $table->unsignedInteger('weight_grams')->nullable();
            $table->unsignedInteger('length_cm')->nullable();
            $table->unsignedInteger('width_cm')->nullable();
            $table->unsignedInteger('height_cm')->nullable();

            // Snapshot penerima ketika paket dibuat.
            $table->string('recipient_name')->nullable();
            $table->string('recipient_phone', 30)->nullable();
            $table->json('recipient_address')->nullable();

            // Kunci idempotensi untuk mencegah pembuatan shipment ganda.
            $table->string('idempotency_key')->nullable()->unique();

            $table->timestamp('label_created_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();

            $table->timestamps();

            $table->unique(
                ['provider_code', 'tracking_number'],
                'shipments_provider_tracking_unique'
            );

            $table->index(['order_id', 'status']);
            $table->index(['provider_code', 'provider_shipment_id']);
        });

        Schema::create('shipment_events', function (Blueprint $table) {
            $table->id();

            $table->foreignId('shipment_id')
                ->constrained('shipments')
                ->cascadeOnDelete();

            $table->string('provider_event_id')->nullable();
            $table->string('provider_status')->nullable();
            $table->string('status', 40);
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->timestamp('occurred_at')->nullable();
            $table->json('payload')->nullable();

            $table->timestamps();

            $table->unique(
                ['shipment_id', 'provider_event_id'],
                'shipment_events_shipment_provider_event_unique'
            );

            $table->index(['shipment_id', 'occurred_at']);
            $table->index(['shipment_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_events');
        Schema::dropIfExists('shipments');
    }
};
