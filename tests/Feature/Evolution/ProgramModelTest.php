<?php

namespace Tests\Feature\Evolution;

use App\Models\Evolution\Program;
use App\Models\Evolution\ProgramPhase;
use App\Models\User;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class ProgramModelTest extends TestCase
{
    public function test_program_and_phase_relationships_and_casts_work(): void
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

            $migration = require database_path(
                'migrations/2026_11_06_235504_create_evolution_program_tables.php'
            );
            
            $migration->up();

            $user = User::factory()->create();

            $program = Program::create([
                'code' => 'EVOLUTION-TEST-001',
                'name' => 'Evolution Test',
                'created_by' => $user->id,
            ]);

            $target = $program->phases()->create([
                'phase_key' => 'multi_tenant',
                'name' => 'Multi Tenant',
                'sequence' => 2,
                'entry_requirements' => ['review_approved'],
                'exit_requirements' => ['tenant_isolation'],
                'is_target' => true,
            ]);

            $initial = $program->phases()->create([
                'phase_key' => 'single_store',
                'name' => 'Single Store',
                'sequence' => 1,
                'entry_requirements' => ['baseline_ready'],
                'exit_requirements' => ['assessment_passed'],
                'is_initial' => true,
            ]);
            
            $target->refresh();
            $initial->refresh();
            $program->refresh();

            // BelongsTo creator.
            $this->assertTrue($program->creator->is($user));

            // HasMany phases, ordered by sequence.
            $this->assertCount(2, $program->phases);
            $this->assertSame(
                ['single_store', 'multi_tenant'],
                $program->phases->pluck('phase_key')->all()
            );

            // HasOne initial and target phases.
            $this->assertTrue($program->initialPhase->is($initial));
            $this->assertTrue($program->targetPhase->is($target));

            // BelongsTo program.
            $this->assertTrue($initial->program->is($program));

            // JSON and boolean casts.
            $this->assertSame(
                ['baseline_ready'],
                $initial->entry_requirements
            );
            $this->assertSame(
                ['assessment_passed'],
                $initial->exit_requirements
            );
            $this->assertIsBool($initial->is_initial);
            $this->assertTrue($initial->is_initial);
            $this->assertFalse($initial->is_target);

            // Phase uses created_at without updated_at.
            $this->assertNotNull($initial->created_at);
            $this->assertNull($initial->getUpdatedAtColumn());

            // Program datetime casts.
            $this->assertNull($program->started_at);
            $this->assertNull($program->completed_at);
            $this->assertNull($program->cancelled_at);
            $this->assertSame('paused', $program->status);
        } finally {
            DB::setDefaultConnection($originalConnection);
            DB::purge('evolution_model');
            config(['database.connections.evolution_model' => null]);
        }
    }
}
