<?php

namespace Tests\Feature\Evolution;

use App\Models\Evolution\Assessment;
use App\Models\Evolution\Criterion;
use App\Models\Evolution\EvidenceAsset;
use App\Models\Evolution\Finding;
use App\Models\Evolution\Program;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class EvidenceAssetModelTest extends TestCase
{
    public function test_evidence_asset_relationships_and_casts_work(): void
    {
        $originalConnection = DB::getDefaultConnection();

        $sqliteConfig = config('database.connections.sqlite');
        $sqliteConfig['database'] = ':memory:';
        $sqliteConfig['foreign_key_constraints'] = true;

        config(['database.connections.evolution_model' => $sqliteConfig]);
        DB::purge('evolution_model');
        DB::setDefaultConnection('evolution_model');

        try {
            Schema::create('users', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('email')->unique();
                $table->timestamp('email_verified_at')->nullable();
                $table->string('password');
                $table->string('role')->default('customer');
                $table->rememberToken();
                $table->timestamps();
            });

            $migrationFiles = [
                '2026_11_06_235504_create_evolution_program_tables.php',
                '2026_11_06_235505_create_evolution_criteria_tables.php',
                '2026_11_06_235506_create_evolution_assessment_tables.php',
                '2026_11_06_235508_create_evolution_finding_tables.php',
                '2026_11_07_153923_create_evolution_evidence_assets_table.php',
            ];

            foreach ($migrationFiles as $file) {
                $migration = require database_path("migrations/{$file}");
                $migration->up();
            }

            $assessor = User::factory()->create();
            $uploader = User::factory()->create();

            $program = Program::create([
                'code' => 'EVOLUTION-EVIDENCE-001',
                'name' => 'Evidence Asset Model Test',
                'created_by' => $assessor->id,
            ]);

            $phase = $program->phases()->create([
                'phase_key' => 'single_store',
                'name' => 'Single Store',
                'sequence' => 1,
                'entry_requirements' => [],
                'exit_requirements' => [],
                'is_initial' => true,
            ]);

            $criterion = Criterion::create([
                'criterion_key' => 'database_isolation',
            ]);

            $criterionVersion = $criterion->versions()->create([
                'version' => 1,
                'name' => 'Database Isolation',
                'severity' => 'critical',
                'evaluation_method' => 'hybrid',
                'definition' => [
                    'checks' => ['foreign_keys', 'query_scoping'],
                ],
            ]);

            $assessment = Assessment::create([
                'program_id' => $program->id,
                'phase_id' => $phase->id,
                'assessment_number' => 1,
                'status' => 'completed',
                'assessor_id' => $assessor->id,
                'baseline_commit' => 'abc123def456',
                'environment' => [
                    'php' => '8.3',
                    'laravel' => '13',
                    'database' => 'sqlite',
                ],
                'ruleset_version' => '1.0.0',
                'started_at' => now()->subHour(),
                'finished_at' => now(),
            ]);

            $assessment->criteria()->create([
                'criterion_version_id' => $criterionVersion->id,
                'criterion_key' => 'database_isolation',
                'criterion_version' => 1,
                'name' => 'Database Isolation',
                'description' => 'Database isolation verification.',
                'severity' => 'critical',
                'evaluation_method' => 'hybrid',
                'definition' => [
                    'checks' => ['foreign_keys', 'query_scoping'],
                ],
            ]);

            $finding = Finding::create([
                'assessment_id' => $assessment->id,
                'criterion_version_id' => $criterionVersion->id,
                'result' => Finding::RESULT_FAIL,
                'severity' => Finding::SEVERITY_CRITICAL,
                'summary' => 'Database isolation check failed.',
                'details' => [
                    'failed_checks' => ['query_scoping'],
                ],
            ]);

            $findingEvidence = EvidenceAsset::create([
                'assessment_id' => $assessment->id,
                'finding_id' => $finding->id,
                'evidence_type' => EvidenceAsset::TYPE_REMEDIATION,
                'storage_disk' => 'private',
                'storage_path' => 'evolution/evidence/report-001.pdf',
                'original_filename' => 'database-report.pdf',
                'mime_type' => 'application/pdf',
                'size_bytes' => 204800,
                'sha256' => str_repeat('a', 64),
                'metadata' => [
                    'source' => 'automated_test',
                    'test_name' => 'DatabaseIsolationTest',
                    'tags' => ['database', 'security'],
                ],
                'uploaded_by' => $uploader->id,
            ]);

            $assessmentEvidence = EvidenceAsset::create([
                'assessment_id' => $assessment->id,
                'finding_id' => null,
                'evidence_type' => EvidenceAsset::TYPE_ASSESSMENT_RESULT,
                'storage_disk' => 'private',
                'storage_path' => 'evolution/evidence/summary-001.json',
                'original_filename' => 'assessment-summary.json',
                'mime_type' => 'application/json',
                'size_bytes' => 4096,
                'sha256' => str_repeat('b', 64),
                'metadata' => [
                    'source' => 'assessment_runner',
                    'format' => 'json',
                ],
                'uploaded_by' => $uploader->id,
            ]);

            $findingEvidence->refresh();
            $assessmentEvidence->refresh();
            $assessment->refresh();
            $finding->refresh();

            // Evidence belongs to assessment.
            $this->assertTrue($findingEvidence->assessment->is($assessment));
            $this->assertTrue($assessmentEvidence->assessment->is($assessment));

            // Evidence may belong to a finding.
            $this->assertTrue($findingEvidence->finding->is($finding));
            $this->assertNull($assessmentEvidence->finding);

            // Assessment has both evidence assets.
            $this->assertCount(2, $assessment->evidenceAssets);
            $this->assertSame(
                [$findingEvidence->id, $assessmentEvidence->id],
                $assessment->evidenceAssets->pluck('id')->all()
            );

            // Finding has only its related evidence.
            $this->assertCount(1, $finding->evidenceAssets);
            $this->assertTrue(
                $finding->evidenceAssets->first()->is($findingEvidence)
            );

            // Evidence belongs to its uploader.
            $this->assertTrue($findingEvidence->uploader->is($uploader));
            $this->assertTrue($assessmentEvidence->uploader->is($uploader));

            // Metadata JSON cast.
            $this->assertIsArray($findingEvidence->metadata);
            $this->assertSame(
                ['database', 'security'],
                $findingEvidence->metadata['tags']
            );

            $this->assertIsArray($assessmentEvidence->metadata);
            $this->assertSame(
                'assessment_runner',
                $assessmentEvidence->metadata['source']
            );

            // Numeric and datetime casts.
            $this->assertIsInt($findingEvidence->size_bytes);
            $this->assertSame(204800, $findingEvidence->size_bytes);
            $this->assertInstanceOf(Carbon::class, $findingEvidence->created_at);

            // Evidence metadata and storage attributes.
            $this->assertSame(
                EvidenceAsset::TYPE_REMEDIATION,
                $findingEvidence->evidence_type
            );
            $this->assertSame('private', $findingEvidence->storage_disk);
            $this->assertSame(
                'evolution/evidence/report-001.pdf',
                $findingEvidence->storage_path
            );
            $this->assertSame(str_repeat('a', 64), $findingEvidence->sha256);

            // Append-only timestamp behavior.
            $this->assertNull($findingEvidence->getUpdatedAtColumn());
            $this->assertNull($assessmentEvidence->getUpdatedAtColumn());
        } finally {
            DB::setDefaultConnection($originalConnection);
            DB::purge('evolution_model');
            config(['database.connections.evolution_model' => null]);
        }
    }
}
