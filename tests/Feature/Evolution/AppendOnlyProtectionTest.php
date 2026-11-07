<?php

namespace Tests\Feature\Evolution;

use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;
use Throwable;

class AppendOnlyProtectionTest extends TestCase
{
    private const TABLES = [
        'evolution_findings',
        'evolution_finding_resolutions',
        'evolution_evidence_assets',
        'evolution_reviews',
        'evolution_approvals',
        'evolution_events',
    ];

    public function test_append_only_tables_reject_updates_and_deletes(): void
    {
        $originalConnection = DB::getDefaultConnection();
        $sqliteConfig = config('database.connections.sqlite');
        $sqliteConfig['database'] = ':memory:';
        $sqliteConfig['foreign_key_constraints'] = true;

        config([
            'database.connections.evolution_append_only' => $sqliteConfig,
        ]);

        DB::purge('evolution_append_only');
        DB::setDefaultConnection('evolution_append_only');

        try {
            $db = DB::connection('evolution_append_only');
            $schema = Schema::connection('evolution_append_only');

            foreach (self::TABLES as $table) {
                $schema->create($table, function (Blueprint $blueprint) {
                    $blueprint->id();
                    $blueprint->string('value')->nullable();
                });
            }

            $migrationPath = database_path('migrations/2026_11_07_180000_protect_evolution_append_only_tables.php');

            $this->assertFileExists($migrationPath);

            $migration = require $migrationPath;
            $migration->up();

            foreach (self::TABLES as $table) {
                $id = $db->table($table)->insertGetId([
                    'value' => 'original',
                ]);

                $this->assertDatabaseHas($table, [
                    'id' => $id,
                    'value' => 'original',
                ], 'evolution_append_only');

                $updateRejected = false;

                try {
                    $db->table($table)
                        ->where('id', $id)
                        ->update(['value' => 'modified']);
                } catch (Throwable) {
                    $updateRejected = true;
                }

                $this->assertTrue($updateRejected,"UPDATE must be rejected on {$table}");

                $this->assertSame('original',$db->table($table)->where('id', $id)->value('value'));

                $deleteRejected = false;

                try {
                    $db->table($table)->where('id', $id)->delete();
                } catch (Throwable) {
                    $deleteRejected = true;
                }

                $this->assertTrue($deleteRejected,"DELETE must be rejected on {$table}");

                $this->assertDatabaseHas($table, [
                    'id' => $id,
                    'value' => 'original',
                ], 'evolution_append_only');
            }
            
            $migration->down();
            
            foreach (self::TABLES as $table) {
                $id = $db->table($table)->value('id');
            
                $this->assertSame(1, $db->table($table)->where('id', $id)->update([ 'value' => 'after_rollback']), "UPDATE should work after rollback on {$table}");
            
                $this->assertSame(1, $db->table($table)->where('id', $id)->delete(), "DELETE should work after rollback on {$table}");
            }
        } finally {
            DB::setDefaultConnection($originalConnection);
            DB::purge('evolution_append_only');
            config(['database.connections.evolution_append_only' => null]);
        }
    }
}
