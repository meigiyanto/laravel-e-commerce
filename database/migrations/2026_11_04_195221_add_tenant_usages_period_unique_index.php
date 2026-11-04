<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasIndex('tenant_usages', 'tenant_usages_period_unique')) {
            Schema::table('tenant_usages', function (Blueprint $table) {
                $table->unique(
                    ['tenant_id', 'feature_key', 'period_start', 'period_end'],
                    'tenant_usages_period_unique'
                );
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasIndex('tenant_usages', 'tenant_usages_period_unique')) {
            Schema::table('tenant_usages', function (Blueprint $table) {
                $table->dropUnique('tenant_usages_period_unique');
            });
        }
    }
};