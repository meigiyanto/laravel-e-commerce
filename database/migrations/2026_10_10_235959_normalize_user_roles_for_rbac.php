<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('customer')->change();
        });

        // Normalize the legacy role value used before the RBAC branch.
        DB::table('users')
            ->where('role', 'user')
            ->update(['role' => 'customer']);
    }

    public function down(): void
    {
        DB::table('users')
            ->where('role', 'customer')
            ->update(['role' => 'user']);

        Schema::table('users', function (Blueprint $table) {
            $table->string('role')->default('user')->change();
        });
    }
};
