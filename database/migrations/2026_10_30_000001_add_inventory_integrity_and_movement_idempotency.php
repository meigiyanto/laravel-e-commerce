<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const CONSTRAINT = 'inventories_reserved_quantity_lte_quantity_check';

    public function up(): void
    {
        $driver = DB::getDriverName();

        if (! in_array($driver, ['sqlite', 'mysql', 'pgsql'], true)) {
            throw new RuntimeException(
                "Database driver {$driver} belum didukung untuk inventory constraint."
            );
        }

        $invalidInventoryExists = DB::table('inventories')
            ->whereColumn('reserved_quantity', '>', 'quantity')
            ->exists();

        if ($invalidInventoryExists) {
            throw new RuntimeException(
                'Terdapat inventory dengan reserved_quantity lebih besar dari quantity. Perbaiki datanya sebelum menjalankan migration.'
            );
        }

        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->string('idempotency_key', 191)
                ->nullable()
                ->unique('inventory_movements_idempotency_key_unique');
        });

        if ($driver === 'sqlite') {
            DB::unprepared("
                CREATE TRIGGER inventories_reserved_quantity_insert_check
                BEFORE INSERT ON inventories
                FOR EACH ROW
                WHEN NEW.reserved_quantity > NEW.quantity
                BEGIN
                    SELECT RAISE(ABORT, 'reserved_quantity cannot exceed quantity');
                END
            ");

            DB::unprepared("
                CREATE TRIGGER inventories_reserved_quantity_update_check
                BEFORE UPDATE OF quantity, reserved_quantity ON inventories
                FOR EACH ROW
                WHEN NEW.reserved_quantity > NEW.quantity
                BEGIN
                    SELECT RAISE(ABORT, 'reserved_quantity cannot exceed quantity');
                END
            ");

            return;
        }

        DB::statement(
            'ALTER TABLE inventories ADD CONSTRAINT '
            .self::CONSTRAINT
            .' CHECK (reserved_quantity <= quantity)'
        );
    }

    public function down(): void
    {
        $driver = DB::getDriverName();

        if ($driver === 'sqlite') {
            DB::unprepared(
                'DROP TRIGGER IF EXISTS inventories_reserved_quantity_insert_check'
            );

            DB::unprepared(
                'DROP TRIGGER IF EXISTS inventories_reserved_quantity_update_check'
            );
        } elseif ($driver === 'mysql') {
            DB::statement(
                'ALTER TABLE inventories DROP CHECK '.self::CONSTRAINT
            );
        } elseif ($driver === 'pgsql') {
            DB::statement(
                'ALTER TABLE inventories DROP CONSTRAINT '.self::CONSTRAINT
            );
        }

        Schema::table('inventory_movements', function (Blueprint $table) {
            $table->dropUnique(
                'inventory_movements_idempotency_key_unique'
            );

            $table->dropColumn('idempotency_key');
        });
    }
};