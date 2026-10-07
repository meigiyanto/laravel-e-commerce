<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Perbaiki data lama jika ada product yang tidak konsisten.
        //
        // Tidak menggunakan UPDATE ... INNER JOIN karena sintaks tersebut
        // tidak didukung oleh SQLite yang digunakan oleh test suite.
        DB::table('products')
            ->whereNotNull('category_id')
            ->whereNotNull('sub_category_id')
            ->select('id', 'category_id', 'sub_category_id')
            ->orderBy('id')
            ->each(function ($product) {
                $subCategoryCategoryId = DB::table('sub_categories')
                    ->where('id', $product->sub_category_id)
                    ->value('category_id');

                if (
                    $subCategoryCategoryId !== null
                    && (int) $product->category_id !== (int) $subCategoryCategoryId
                ) {
                    DB::table('products')
                        ->where('id', $product->id)
                        ->update([
                            'category_id' => $subCategoryCategoryId,
                        ]);
                }
            });

        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign(['category_id']);
            $table->dropForeign(['sub_category_id']);
        });

        Schema::table('sub_categories', function (Blueprint $table) {
            $table->unique(
                ['category_id', 'id'],
                'sub_categories_category_id_id_unique'
            );
        });

        Schema::table('products', function (Blueprint $table) {
            $table->foreign(
                ['category_id', 'sub_category_id'],
                'products_category_sub_category_fk'
            )
                ->references(['category_id', 'id'])
                ->on('sub_categories')
                ->cascadeOnUpdate()
                ->restrictOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropForeign('products_category_sub_category_fk');

            $table->foreign('category_id')
                ->references('id')
                ->on('categories')
                ->cascadeOnDelete();

            $table->foreign('sub_category_id')
                ->references('id')
                ->on('sub_categories')
                ->cascadeOnDelete();
        });

        Schema::table('sub_categories', function (Blueprint $table) {
            $table->dropUnique('sub_categories_category_id_id_unique');
        });
    }
};
