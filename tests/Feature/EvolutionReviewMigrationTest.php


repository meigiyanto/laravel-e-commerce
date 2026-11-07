<?php

namespace Tests\Feature;

use Illuminate\Database\QueryException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class EvolutionReviewMigrationTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config([
            'database.default' => 'sqlite',
            'database.connections.sqlite.database' => ':memory:',
            'database.connections.sqlite.foreign_key_constraints' => true,
        ]);

        DB::purge('sqlite');
        DB::setDefaultConnection('sqlite');

        Schema::create('users', function (Blueprint $table) {
            $table->id();
        });

        Schema::create('evolution_assessments', function (Blueprint $table) {
            $table->id();
        });

        $migration = require database_path(
            'migrations/2026_11_06_235509_create_evolution_review_tables.php'
        );

        $migration->up();
    }

    public function test_review_table_has_expected_structure_and_foreign_keys(): void
    {
        $this->assertTrue(Schema::hasTable('evolution_reviews'));

        $this->assertTrue(Schema::hasColumns('evolution_reviews', [
            'id',
            'assessment_id',
            'reviewer_id',
            'outcome',
            'rationale',
            'created_at',
        ]));

        $foreignKeys = DB::select(
            'PRAGMA foreign_key_list(evolution_reviews)'
        );

        $this->assertCount(2, $foreignKeys);

        $this->assertEqualsCanonicalizing(
            ['users', 'evolution_assessments'],
            array_column($foreignKeys, 'table')
        );

        $indexes = DB::select(
            'PRAGMA index_list(evolution_reviews)'
        );

        $uniqueIndexes = array_filter(
            $indexes,
            fn ($index) => (bool) $index->unique
        );

        $this->assertCount(2, $uniqueIndexes);
    }

    public function test_assessment_can_only_have_one_review(): void
    {
        DB::table('users')->insert(['id' => 1]);
        DB::table('evolution_assessments')->insert(['id' => 1]);

        DB::table('evolution_reviews')->insert([
            'assessment_id' => 1,
            'reviewer_id' => 1,
            'outcome' => 'accepted_for_approval',
            'rationale' => 'Assessment memenuhi persyaratan.',
        ]);

        $this->expectException(QueryException::class);

        DB::table('evolution_reviews')->insert([
            'assessment_id' => 1,
            'reviewer_id' => 1,
            'outcome' => 'rejected',
            'rationale' => 'Review kedua tidak diperbolehkan.',
        ]);
    }

    public function test_review_rejects_invalid_foreign_keys(): void
    {
        DB::table('users')->insert(['id' => 1]);

        $this->expectException(QueryException::class);

        DB::table('evolution_reviews')->insert([
            'assessment_id' => 999,
            'reviewer_id' => 1,
            'outcome' => 'accepted_for_approval',
            'rationale' => 'Assessment tidak tersedia.',
        ]);
    }
}
