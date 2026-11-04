<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
    * Memeriksa apakah index dengan susunan kolom tertentu sudah ada.
    */
    private function hasIndex(string $table, array $columns, bool $unique = false): bool
    {
        $indexes = DB::table('information_schema.statistics')
        ->selectRaw('index_name, GROUP_CONCAT(column_name ORDER BY seq_in_index) AS columns_list, MAX(non_unique) AS non_unique')
        ->where('table_schema', DB::getDatabaseName())
        ->where('table_name', $table)
        ->groupBy('index_name')
        ->get();

        $expected = implode(',', $columns);

        return $indexes->contains(function ($index) use ($expected, $unique) {
            return $index->columns_list === $expected && (int) $index->non_unique === ($unique ? 0 : 1);
        });
    }

    /**
    * Memeriksa apakah foreign key untuk kolom sudah tersedia.
    */
    private function hasForeignKey(string $table, string $column): bool
    {
        return DB::table('information_schema.key_column_usage')
        ->where('table_schema', DB::getDatabaseName())
        ->where('table_name', $table)
        ->where('column_name', $column)
        ->where('referenced_table_name', 'tenants')
        ->exists();
    }

    /**
    * Mencari nama unique index berdasarkan susunan kolom.
    */
    private function findUniqueIndex(string $table, array $columns): ?string
    {
        $indexes = DB::table('information_schema.statistics')
        ->selectRaw('index_name, GROUP_CONCAT(column_name ORDER BY seq_in_index) AS columns_list, MAX(non_unique) AS non_unique')
        ->where('table_schema', DB::getDatabaseName())
        ->where('table_name', $table)
        ->groupBy('index_name')
        ->get();

        $expected = implode(',', $columns);

        return $indexes->first(function ($index) use ($expected) {
            return $index->columns_list === $expected && (int) $index->non_unique === 0 && $index->index_name !== 'PRIMARY';
        })?->index_name;
    }

    public function up(): void
    {
        /*
         * ORDERS
         */

        if (!Schema::hasColumn('orders', 'tenant_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
            });
        }

        if (!$this->hasForeignKey('orders', 'tenant_id')) {
            Schema::table('orders', function (Blueprint $table) {
                $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->restrictOnDelete();
            });
        }

        if (!$this->hasIndex('orders', ['tenant_id', 'user_id'])) {
            Schema::table('orders', function (Blueprint $table) {
                $table->index(['tenant_id', 'user_id']);
            });
        }

        /*
         * CARTS
         */

        if (!Schema::hasColumn('carts', 'tenant_id')) {
            Schema::table('carts', function (Blueprint $table) {
                $table->unsignedBigInteger('tenant_id')->nullable()->after('id');
            });
        }

        if (!$this->hasForeignKey('carts', 'tenant_id')) {
            Schema::table('carts', function (Blueprint $table) {
                $table->foreign('tenant_id')
                ->references('id')
                ->on('tenants')
                ->cascadeOnDelete();
            });
        }

        // Pertahankan index user_id untuk kebutuhan foreign key ke users.id.
        // Index ini harus dibuat sebelum unique user_id lama dihapus.
        if (!$this->hasIndex('carts', ['user_id'])) {
            Schema::table('carts', function (Blueprint $table) {
                $table->index('user_id', 'carts_user_id_index');
            });
        }

        // Hapus unique user_id lama jika masih tersedia.
        $oldUnique = $this->findUniqueIndex('carts', ['user_id']);

        if ($oldUnique) {
            Schema::table('carts', function (Blueprint $table) use ($oldUnique) {
                $table->dropUnique($oldUnique);
            });
        }

        // Pastikan kombinasi tenant_id dan user_id unik.
        if (!$this->hasIndex('carts', ['tenant_id', 'user_id'], true)) {
            Schema::table('carts', function (Blueprint $table) {
                $table->unique(['tenant_id', 'user_id']);
            });
        }

        /*
         * BACKFILL ORDERS
         *
         * Hanya mengisi tenant jika seluruh item memiliki
         * tenant yang sama dan tidak ada tenant_id NULL.
         */

        DB::table('orders')
        ->whereNull('tenant_id')
        ->orderBy('id')
        ->chunkById(200, function ($orders) {
            foreach ($orders as $order) {
                $tenantIds = DB::table('order_items')
                ->join('products', 'products.id', '=', 'order_items.product_id')
                ->where('order_items.order_id', $order->id)
                ->distinct()
                ->pluck('products.tenant_id');

                if ($tenantIds->count() === 1 && $tenantIds->first() !== null) {
                    DB::table('orders')
                    ->where('id', $order->id)
                    ->whereNull('tenant_id')
                    ->update(['tenant_id' => $tenantIds->first()]);
                }
            }
        });

        /*
         * BACKFILL CARTS
         */

        DB::table('carts')
        ->whereNull('tenant_id')
        ->orderBy('id')
        ->chunkById(200,
            function ($carts) {
                foreach ($carts as $cart) {
                    $tenantIds = DB::table('cart_items')
                    ->join('products', 'products.id', '=', 'cart_items.product_id')
                    ->where('cart_items.cart_id', $cart->id)
                    ->distinct()
                    ->pluck('products.tenant_id');

                    if ($tenantIds->count() === 1 && $tenantIds->first() !== null) {
                        DB::table('carts')
                        ->where('id', $cart->id)
                        ->whereNull('tenant_id')
                        ->update(['tenant_id' => $tenantIds->first()]);
                    }
                }
            });
    }

    public function down(): void
    {
        /*
         * Migration ini dapat menemukan kolom yang sudah ada
         * sebelum dijalankan. Karena itu, rollback otomatis
         * berisiko menghapus struktur yang bukan dibuat olehnya.
         *
         * Rollback dilakukan secara manual setelah memeriksa
         * migration dan struktur database sebelumnya.
         */
    }
};