<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('refunds', function (Blueprint $table) {
            $table->timestamp('processing_at')
                ->nullable()
                ->after('requested_at');

            $table->index([
                'status',
                'processing_at',
            ]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('refunds', function (Blueprint $table) {
            $table->dropIndex([
                'status',
                'processing_at',
            ]);

            $table->dropColumn('processing_at');
        });
    }
};
