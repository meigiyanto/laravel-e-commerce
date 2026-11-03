<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('inventory_reservations', function (Blueprint $table) {
            $table->foreignId('tenant_id')
                ->nullable()
                ->constrained('tenants')
                ->cascadeOnDelete();
        });

        DB::table('inventory_reservations')
            ->orderBy('id')
            ->get(['id', 'product_variant_id'])
            ->each(function ($reservation) {
                $tenantId = DB::table('product_variants')
                    ->where('id', $reservation->product_variant_id)
                    ->value('tenant_id');

                if ($tenantId) {
                    DB::table('inventory_reservations')
                        ->where('id', $reservation->id)
                        ->update(['tenant_id' => $tenantId]);
                }
            });
    }

    public function down(): void
    {
        Schema::table('inventory_reservations', function (Blueprint $table) {
            $table->dropConstrainedForeignId('tenant_id');
        });
    }
};