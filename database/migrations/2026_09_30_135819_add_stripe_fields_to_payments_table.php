<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->string('stripe_payment_intent_id')
                ->nullable()
                ->unique()
                ->after('reference_id');

            $table->string('payment_method')
                ->nullable()
                ->after('currency');
        });
    }

    public function down(): void
    {
        Schema::table('payments', function (Blueprint $table) {
            $table->dropUnique([
                'stripe_payment_intent_id',
            ]);

            $table->dropColumn([
                'stripe_payment_intent_id',
                'payment_method',
            ]);
        });
    }
};
