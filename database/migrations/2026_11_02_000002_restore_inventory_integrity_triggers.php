<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            return;
        }

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
    }

    public function down(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            return;
        }

        DB::unprepared(
            'DROP TRIGGER IF EXISTS inventories_reserved_quantity_insert_check'
        );

        DB::unprepared(
            'DROP TRIGGER IF EXISTS inventories_reserved_quantity_update_check'
        );
    }
};