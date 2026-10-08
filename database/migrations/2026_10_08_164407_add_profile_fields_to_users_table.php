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
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 30)
                ->nullable()
                ->after('email');

            $table->date('birth_date')
                ->nullable()
                ->after('phone');

            $table->string('avatar')
                ->nullable()
                ->after('birth_date');

            $table->text('address')
                ->nullable()
                ->after('avatar');

            $table->string('city', 100)
                ->nullable()
                ->after('address');

            $table->string('province', 100)
                ->nullable()
                ->after('city');

            $table->string('postal_code', 20)
                ->nullable()
                ->after('province');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone',
                'birth_date',
                'avatar',
                'address',
                'city',
                'province',
                'postal_code',
            ]);
        });
    }
};
