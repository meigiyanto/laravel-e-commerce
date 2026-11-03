<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Kolom role sudah berupa string. Tidak perlu mengubah
        // struktur tabel atau mengubah role pengguna lama.
        DB::table('users')
            ->where('role', 'platform_admin')
            ->update(['role' => 'platform_admin']);
    }

    public function down(): void
    {
        // Sengaja tidak mengubah role pengguna saat rollback.
        // Promosi platform admin harus dikelola secara eksplisit.
    }
};
