<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class () extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement('
            DELETE s1 FROM ec_shipments s1
            INNER JOIN ec_shipments s2
            WHERE s1.order_id = s2.order_id
            AND s1.id > s2.id
        ');

        Schema::table('ec_shipments', function (Blueprint $table): void {
            if (! Schema::hasColumn('ec_shipments', 'order_id')) {
                return;
            }

            $indexes = DB::select("SHOW INDEX FROM ec_shipments WHERE Key_name = 'ec_shipments_order_id_unique'");

            if (empty($indexes)) {
                $table->unique('order_id', 'ec_shipments_order_id_unique');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ec_shipments', function (Blueprint $table): void {
            $table->dropUnique('ec_shipments_order_id_unique');
        });
    }
};
