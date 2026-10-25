<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        // Hapus FK sebelum mengubah nullability kolom.
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign('orders_user_id_foreign');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });

        // Pesanan dengan item, pembayaran, refund, atau permintaan
        // pembatalan tidak boleh dihapus secara fisik.
        $orderForeignKeys = [
            'order_items' => 'order_items_order_id_foreign',
            'payments' => 'payments_order_id_foreign',
            'refunds' => 'refunds_order_id_foreign',
            'order_cancellation_requests'
                => 'order_cancellation_requests_order_id_foreign',
        ];

        foreach ($orderForeignKeys as $tableName => $foreignKey) {
            Schema::table($tableName, function (Blueprint $table) use ($foreignKey) {
                $table->dropForeign($foreignKey);
            });

            Schema::table($tableName, function (Blueprint $table) {
                $table->foreign('order_id')
                    ->references('id')
                    ->on('orders')
                    ->restrictOnDelete();
            });
        }

        // Simpan riwayat permintaan pembatalan ketika akun pemohon dihapus.
        Schema::table('order_cancellation_requests', function (Blueprint $table) {
            $table->dropForeign(
                'order_cancellation_requests_user_id_foreign'
            );
        });

        Schema::table('order_cancellation_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable()->change();
        });

        Schema::table('order_cancellation_requests', function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->nullOnDelete();
        });

        // Refund juga harus bertahan jika suatu saat pembayaran
        // hendak dihapus secara langsung.
        Schema::table('refunds', function (Blueprint $table) {
            $table->dropForeign('refunds_payment_id_foreign');
        });

        Schema::table('refunds', function (Blueprint $table) {
            $table->foreign('payment_id')
                ->references('id')
                ->on('payments')
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'sqlite') {
            return;
        }

        // Rollback ke user_id NOT NULL tidak aman jika ada transaksi
        // yang sudah terlepas dari akun. Tolak rollback dalam kondisi itu.
        if (DB::table('orders')->whereNull('user_id')->exists()) {
            throw new RuntimeException(
                'Rollback dibatalkan: orders memiliki user_id NULL. '
                . 'Pulihkan relasi akun terlebih dahulu.'
            );
        }

        if (DB::table('order_cancellation_requests')
            ->whereNull('user_id')->exists()) {
            throw new RuntimeException(
                'Rollback dibatalkan: permintaan pembatalan memiliki user_id NULL.'
            );
        }

        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign('orders_user_id_foreign');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });

        $orderForeignKeys = [
            'order_items' => 'order_items_order_id_foreign',
            'payments' => 'payments_order_id_foreign',
            'refunds' => 'refunds_order_id_foreign',
            'order_cancellation_requests'
                => 'order_cancellation_requests_order_id_foreign',
        ];

        foreach ($orderForeignKeys as $tableName => $foreignKey) {
            Schema::table($tableName, function (Blueprint $table) use ($foreignKey) {
                $table->dropForeign($foreignKey);
            });

            Schema::table($tableName, function (Blueprint $table) {
                $table->foreign('order_id')
                    ->references('id')
                    ->on('orders')
                    ->cascadeOnDelete();
            });
        }

        Schema::table('order_cancellation_requests', function (Blueprint $table) {
            $table->dropForeign(
                'order_cancellation_requests_user_id_foreign'
            );
        });

        Schema::table('order_cancellation_requests', function (Blueprint $table) {
            $table->unsignedBigInteger('user_id')->nullable(false)->change();
        });

        Schema::table('order_cancellation_requests', function (Blueprint $table) {
            $table->foreign('user_id')
                ->references('id')
                ->on('users')
                ->cascadeOnDelete();
        });

        Schema::table('refunds', function (Blueprint $table) {
            $table->dropForeign('refunds_payment_id_foreign');
        });

        Schema::table('refunds', function (Blueprint $table) {
            $table->foreign('payment_id')
                ->references('id')
                ->on('payments')
                ->cascadeOnDelete();
        });
    }
};
