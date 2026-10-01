<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('provider')->default('xendit')->after('order_id');

            $table->string('reference_id')->nullable()->unique()->after('provider');

            $table->string('payment_session_id')
                ->nullable()
                ->unique()
                ->after('reference_id');

            $table->string('payment_request_id')
                ->nullable()
                ->after('payment_session_id');

            $table->string('xendit_payment_id')
                ->nullable()
                ->after('payment_request_id');

            $table->string('payment_link_url')
                ->nullable()
                ->after('xendit_payment_id');

            $table->string('currency', 3)
                ->default('IDR')
                ->after('payment_link_url');

            $table->string('status')
                ->default('pending')
                ->after('currency');

            $table->timestamp('expires_at')
                ->nullable()
                ->after('paid_at');

            $table->json('metadata')
                ->nullable()
                ->after('expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropColumn([
                'provider',
                'reference_id',
                'payment_session_id',
                'payment_request_id',
                'xendit_payment_id',
                'payment_link_url',
                'currency',
                'status',
                'expires_at',
                'metadata',
            ]);
        });
    }
};
