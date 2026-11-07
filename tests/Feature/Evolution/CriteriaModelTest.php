<?php

namespace Tests\Feature\Evolution;

use App\Models\Evolution\Criterion;
use App\Models\Evolution\CriterionVersion;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class CriteriaModelTest extends TestCase
{
    public function test_criterion_and_version_relationships_and_casts_work(): void
    {
        $originalConnection = DB::getDefaultConnection();

        $sqliteConfig = config('database.connections.sqlite');
        $sqliteConfig['database'] = ':memory:';
        $sqliteConfig['foreign_key_constraints'] = true;

        config(['database.connections.evolution_model' => $sqliteConfig]);
        DB::purge('evolution_model');
        DB::setDefaultConnection('evolution_model');

        try {
            Schema::create('evolution_criteria', function (Blueprint $table) {
                $table->id();
                $table->string('criterion_key', 100)->unique();
                $table->boolean('is_active')->default(true);
                $table->timestamps();
            });

            Schema::create('evolution_criteria_versions', function (Blueprint $table) {
                $table->id();
                $table->foreignId('criterion_id')
                    ->constrained('evolution_criteria')
                    ->restrictOnDelete()
                    ->restrictOnUpdate();
                $table->unsignedInteger('version');
                $table->string('name', 150);
                $table->text('description')->nullable();
                $table->string('severity', 20);
                $table->string('evaluation_method', 20);
                $table->json('definition');
                $table->timestamp('created_at')->useCurrent();
                $table->unique(['criterion_id', 'version']);
            });

            $criterion = Criterion::create([
                'criterion_key' => 'database_isolation',
            ]);

            $versionTwo = $criterion->versions()->create([
                'version' => 2,
                'name' => 'Database Isolation',
                'description' => 'Checks database isolation boundaries.',
                'severity' => 'critical',
                'evaluation_method' => 'hybrid',
                'definition' => [
                    'checks' => ['foreign_keys', 'query_scoping'],
                    'threshold' => 100,
                ],
            ]);

            $versionOne = $criterion->versions()->create([
                'version' => 1,
                'name' => 'Database Isolation',
                'description' => 'Initial database isolation checks.',
                'severity' => 'high',
                'evaluation_method' => 'manual',
                'definition' => [
                    'checks' => ['foreign_keys'],
                    'threshold' => 80,
                ],
            ]);

            $criterion->refresh();
            $versionOne->refresh();
            $versionTwo->refresh();

            // HasMany relationship, ordered by version.
            $this->assertCount(2, $criterion->versions);
            $this->assertSame(
                [1, 2],
                $criterion->versions->pluck('version')->all()
            );

            // BelongsTo relationship.
            $this->assertTrue($versionOne->criterion->is($criterion));
            $this->assertTrue($versionTwo->criterion->is($criterion));

            // Boolean cast and database default.
            $this->assertIsBool($criterion->is_active);
            $this->assertTrue($criterion->is_active);

            // Integer and JSON casts.
            $this->assertIsInt($versionOne->version);
            $this->assertSame(1, $versionOne->version);
            $this->assertIsArray($versionOne->definition);
            $this->assertSame(
                ['foreign_keys'],
                $versionOne->definition['checks']
            );
            $this->assertSame(
                100,
                $versionTwo->definition['threshold']
            );

            // Version is an immutable historical record.
            $this->assertNotNull($versionOne->created_at);
            $this->assertNull($versionOne->getUpdatedAtColumn());

            // Severity and evaluation method remain string values.
            $this->assertSame('high', $versionOne->severity);
            $this->assertSame('manual', $versionOne->evaluation_method);
        } finally {
            DB::setDefaultConnection($originalConnection);
            DB::purge('evolution_model');
            config(['database.connections.evolution_model' => null]);
        }
    }
}
