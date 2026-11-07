<?php

namespace Tests\Feature\Evolution;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EvidenceAssetMigrationTest extends TestCase
{
    public function test_evidence_asset_table_has_expected_structure_and_constraints(): void
    {
        $originalConnection = DB::getDefaultConnection();

        $sqliteConfig = config('database.connections.sqlite');
        $sqliteConfig['database'] = ':memory:';
        $sqliteConfig['foreign_key_constraints'] = true;

        config(['database.connections.evolution_evidence' => $sqliteConfig]);
        DB::purge('evolution_evidence');
        DB::setDefaultConnection('evolution_evidence');

        try {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
            });

            Schema::create('evolution_assessments', function (Blueprint $table) {
                $table->id();
            });

            Schema::create('evolution_findings', function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('assessment_id');

                $table->unique(
                    ['assessment_id', 'id'],
                    'uq_test_findings_assessment_pair'
                );
            });

            $migration = require database_path(
                'migrations/2026_11_07_153923_create_evolution_evidence_assets_table.php'
            );
            $migration->up();

            $columns = Schema::getColumnListing('evolution_evidence_assets');

            foreach ([
                'id',
                'assessment_id',
                'finding_id',
                'evidence_type',
                'storage_disk',
                'storage_path',
                'original_filename',
                'mime_type',
                'size_bytes',
                'sha256',
                'metadata',
                'uploaded_by',
                'created_at',
            ] as $column) {
                $this->assertContains($column, $columns);
            }

            $this->assertNotContains('updated_at', $columns);
            $this->assertTrue(
                Schema::hasColumn('evolution_evidence_assets', 'finding_id')
            );

            $indexes = collect(
                DB::select("PRAGMA index_list('evolution_evidence_assets')")
            )->pluck('name')->all();

            foreach ([
                'idx_evp_evidence_assessment_created',
                'idx_evp_evidence_finding_created',
                'idx_evp_evidence_type',
                'idx_evp_evidence_sha256',
                'idx_evp_evidence_uploader',
            ] as $index) {
                $this->assertContains($index, $indexes);
            }

            $foreignKeys = DB::select(
                "PRAGMA foreign_key_list('evolution_evidence_assets')"
            );

            $foreignKeyTargets = collect($foreignKeys)
                ->pluck('table')
                ->unique()
                ->all();

            $this->assertContains('evolution_assessments', $foreignKeyTargets);
            $this->assertContains('evolution_findings', $foreignKeyTargets);
            $this->assertContains('users', $foreignKeyTargets);

            $findingForeignKeys = collect($foreignKeys)
                ->where('table', 'evolution_findings');

            $this->assertCount(2, $findingForeignKeys);
            $this->assertSame(
                ['assessment_id', 'finding_id'],
                $findingForeignKeys->sortBy('seq')->pluck('from')->values()->all()
            );
            $this->assertSame(
                ['assessment_id', 'id'],
                $findingForeignKeys->sortBy('seq')->pluck('to')->values()->all()
            );
        } finally {
            DB::setDefaultConnection($originalConnection);
            DB::purge('evolution_evidence');
            config(['database.connections.evolution_evidence' => null]);
        }
    }
}