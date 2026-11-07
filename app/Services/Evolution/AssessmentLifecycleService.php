<?php

namespace App\Services\Evolution;

use App\Models\Evolution\Assessment;
use App\Models\Evolution\Criterion;
use App\Models\Evolution\EvolutionEvent;
use App\Models\Evolution\Program;
use App\Models\Evolution\ProgramPhase;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use App\Models\Evolution\Finding;

class AssessmentLifecycleService
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_RUNNING = 'running';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_FAILED = 'failed';

    public const STATUS_CANCELLED = 'cancelled';

    public function createDraft(
        Program $program,
        ProgramPhase $phase,
        User $assessor,
        string $baselineCommit,
        array $environment,
        string $rulesetVersion,
        ?Assessment $supersedes = null,
    ): Assessment {
        $this->assertNonEmpty($baselineCommit, 'baseline_commit');
        $this->assertNonEmpty($rulesetVersion, 'ruleset_version');

        return DB::transaction(function () use (
            $program,
            $phase,
            $assessor,
            $baselineCommit,
            $environment,
            $rulesetVersion,
            $supersedes,
        ): Assessment {
            $lockedProgram = Program::query()
            ->lockForUpdate()
            ->findOrFail($program->getKey());

            if ($lockedProgram->status !== 'active') {
                $this->invalid('program', 'Assessment hanya dapat dibuat pada program aktif.');
            }

            $lockedPhase = ProgramPhase::query()
            ->where('program_id', $lockedProgram->id)
            ->find($phase->getKey());

            if (! $lockedPhase) {
                $this->invalid('phase', 'Fase tidak termasuk dalam program ini.');
            }

            if ($supersedes) {
                $previous = Assessment::query()
                ->where('program_id', $lockedProgram->id)
                ->lockForUpdate()
                ->find($supersedes->getKey());

                if (! $previous || ! in_array($previous->status, [
                    self::STATUS_COMPLETED,
                    self::STATUS_FAILED,
                    self::STATUS_CANCELLED,
                    'changes_requested',
                    'rejected',
                ], true)) {
                    $this->invalid('supersedes_assessment_id', 'Assessment sebelumnya tidak dapat dijadikan dasar tindak lanjut.');
                }

                if ((int) $previous->phase_id !== (int) $lockedPhase->id) {
                    $this->invalid('supersedes_assessment_id', 'Assessment tindak lanjut harus menggunakan fase yang sama.');
                }
            }

            $criteria = Criterion::query()
            ->where('is_active', true)
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

            $snapshots = [];
            foreach ($criteria as $criterion) {
                $version = $criterion->versions()
                ->orderByDesc('version')
                ->first();

                if (! $version) {
                    continue;
                }

                $snapshots[] = [
                    'criterion_version_id' => $version->id,
                    'criterion_key' => $criterion->criterion_key,
                    'criterion_version' => $version->version,
                    'name' => $version->name,
                    'description' => $version->description,
                    'severity' => $version->severity,
                    'evaluation_method' => $version->evaluation_method,
                    'definition' => $version->definition,
                ];
            }

            if ($snapshots === []) {
                $this->invalid('criteria', 'Tidak ada kriteria aktif dengan versi yang tersedia.');
            }

            $assessment = $lockedProgram->assessments()->create([
                'phase_id' => $lockedPhase->id,
                'assessment_number' => ((int) $lockedProgram->assessments()->max('assessment_number')) + 1,
                'status' => self::STATUS_DRAFT,
                'assessor_id' => $assessor->id,
                'supersedes_assessment_id' => $supersedes?->id,
                'baseline_commit' => trim($baselineCommit),
                'environment' => $environment,
                'ruleset_version' => trim($rulesetVersion),
            ]);

            $assessment->criteria()->createMany($snapshots);

            $this->recordEvent(
                assessment: $assessment,
                actor: $assessor,
                eventKey: 'assessment.created',
                payload: [
                    'status' => self::STATUS_DRAFT,
                    'phase_id' => $lockedPhase->id,
                    'criteria_count' => count($snapshots),
                    'supersedes_assessment_id' => $supersedes?->id,
                ],
            );

            return $assessment->fresh(['program', 'phase', 'assessor', 'criteria']);
        });
    }

    public function start(Assessment $assessment,
        User $actor): Assessment
    {
        return DB::transaction(function () use ($assessment, $actor): Assessment {
            $locked = $this->lockAssessment($assessment);

            $this->assertAssessorOrPlatformAdmin($locked, $actor);
            $this->assertStatus($locked, self::STATUS_DRAFT);

            $program = Program::query()->lockForUpdate()->findOrFail($locked->program_id);
            if ($program->status !== 'active') {
                $this->invalid('program', 'Assessment hanya dapat dimulai pada program aktif.');
            }

            if (! $locked->criteria()->exists()) {
                $this->invalid('assessment', 'Assessment belum memiliki snapshot kriteria.');
            }

            $hasRunningAssessment = Assessment::query()
            ->where('program_id', $locked->program_id)
            ->where('status', self::STATUS_RUNNING)
            ->where('id', '!=', $locked->id)
            ->exists();

            if ($hasRunningAssessment) {
                $this->invalid('assessment', 'Program sudah memiliki assessment yang sedang berjalan.');
            }

            $locked->forceFill([
                'status' => self::STATUS_RUNNING,
                'started_at' => now(),
            ])->save();

            $this->recordEvent($locked, $actor, 'assessment.started', [
                'from' => self::STATUS_DRAFT,
                'to' => self::STATUS_RUNNING,
            ]);

            return $locked->fresh(['program', 'phase', 'assessor', 'criteria']);
        });
    }

    public function complete(Assessment $assessment,
        User $actor): Assessment
    {
        return $this->finish($assessment,
            $actor,
            self::STATUS_COMPLETED);
    }

    public function fail(
        Assessment $assessment,
        User $actor,
        string $reason,
    ): Assessment {
        $this->assertNonEmpty($reason,
            'reason');

        return $this->finish($assessment,
            $actor,
            self::STATUS_FAILED,
            [
                'reason' => trim($reason),
            ]);
    }

    public function cancel(
        Assessment $assessment,
        User $actor,
        string $reason,
    ): Assessment {
        $this->assertNonEmpty($reason,
            'reason');

        return DB::transaction(function () use ($assessment, $actor, $reason): Assessment {
            $locked = $this->lockAssessment($assessment);

            if (! in_array($locked->status, [self::STATUS_DRAFT, self::STATUS_RUNNING], true)) {
                $this->invalid('status', 'Hanya assessment draft atau running yang dapat dibatalkan.');
            }

            if ($locked->status === self::STATUS_DRAFT) {
                $this->assertAssessorOrPlatformAdmin($locked, $actor);
            } elseif (! $actor->isPlatformAdmin()) {
                $this->invalid('actor', 'Assessment yang sedang berjalan hanya dapat dibatalkan oleh platform admin.');
            }

            $previousStatus = $locked->status;
            $now = now();

            $locked->forceFill([
                'status' => self::STATUS_CANCELLED,
                'finished_at' => $previousStatus === self::STATUS_RUNNING ? $now : null,
                'cancelled_at' => $now,
                'cancelled_by' => $actor->id,
                'cancellation_reason' => trim($reason),
            ])->save();

            $this->recordEvent($locked, $actor, 'assessment.cancelled', [
                'from' => $previousStatus,
                'to' => self::STATUS_CANCELLED,
                'reason' => trim($reason),
            ]);

            return $locked->fresh(['program', 'phase', 'assessor', 'canceller', 'criteria']);
        });
    }

    private function finish(
        Assessment $assessment,
        User $actor,
        string $status,
        array $payload = [],
    ): Assessment {
        return DB::transaction(function () use ($assessment, $actor, $status, $payload): Assessment {
            $locked = $this->lockAssessment($assessment);

            $this->assertAssessorOrPlatformAdmin($locked, $actor);
            $this->assertStatus($locked, self::STATUS_RUNNING);

            if ($status === self::STATUS_COMPLETED) {
                $snapshotIds = $locked->criteria()->pluck('criterion_version_id')->all();
                $findingIds = $locked->findings()->pluck('criterion_version_id')->all();
                $missing = array_diff($snapshotIds, $findingIds);

                if ($missing !== []) {
                    $this->invalid('findings', 'Semua kriteria harus memiliki hasil sebelum assessment diselesaikan.');
                }
            }

            $locked->forceFill([
                'status' => $status,
                'finished_at' => now(),
            ])->save();

            $this->recordEvent($locked, $actor, 'assessment.'.$status, array_merge([
                'from' => self::STATUS_RUNNING,
                'to' => $status,
            ], $payload));

            return $locked->fresh(['program', 'phase', 'assessor', 'criteria']);
        });
    }

    private function lockAssessment(Assessment $assessment): Assessment
    {
        return Assessment::query()
        ->lockForUpdate()
        ->findOrFail($assessment->getKey());
    }

    private function assertAssessorOrPlatformAdmin(Assessment $assessment,
        User $actor): void
    {
        if (! $actor->isPlatformAdmin() && (int) $assessment->assessor_id !== (int) $actor->id) {
            $this->invalid('actor', 'Hanya assessor pemilik assessment atau platform admin yang dapat melakukan aksi ini.');
        }
    }

    private function assertStatus(Assessment $assessment, string $expected): void
    {
        if ($assessment->status !== $expected) {
            $this->invalid('status', "Aksi ini hanya dapat dilakukan pada assessment berstatus {$expected}.");
        }
    }

    private function assertNonEmpty(string $value, string $field): void
    {
        if (trim($value) === '') {
            $this->invalid($field, 'Field ini wajib diisi.');
        }
    }

    private function invalid(string $field, string $message): never
    {
        throw ValidationException::withMessages([$field => $message]);
    }

    private function recordEvent(
        Assessment $assessment,
        User $actor,
        string $eventKey,
        array $payload,
    ): void {
        EvolutionEvent::query()->create([
            'program_id' => $assessment->program_id,
            'assessment_id' => $assessment->id,
            'actor_type' => $actor->isPlatformAdmin() ? EvolutionEvent::ACTOR_PLATFORM_ADMIN : 'user',
            'actor_id' => $actor->id,
            'event_key' => $eventKey,
            'payload' => $payload,
        ]);
    }

    public function recordFinding(
        Assessment $assessment,
        User $actor,
        int $criterionVersionId,
        string $result,
        string $severity,
        ?string $summary = null,
        ?array $details = null,
    ): Finding {
        return DB::transaction(function () use (
            $assessment,
            $actor,
            $criterionVersionId,
            $result,
            $severity,
            $summary,
            $details,
        ): Finding {
            $locked = $this->lockAssessment($assessment);

            $this->assertAssessorOrPlatformAdmin($locked, $actor);
            $this->assertStatus($locked, self::STATUS_RUNNING);

            $criterion = $locked->criteria()
            ->where('criterion_version_id', $criterionVersionId)
            ->first();

            if (! $criterion) {
                $this->invalid(
                    'criterion_version_id',
                    'Kriteria tidak termasuk snapshot assessment ini.'
                );
            }

            if (Finding::query()
                ->where('assessment_id', $locked->id)
                ->where('criterion_version_id', $criterionVersionId)
                ->exists()) {
                $this->invalid(
                    'criterion_version_id',
                    'Finding untuk kriteria ini sudah tercatat.'
                );
            }

            $finding = Finding::query()->create([
                'assessment_id' => $locked->id,
                'criterion_version_id' => $criterionVersionId,
                'result' => $result,
                'severity' => $severity,
                'summary' => $summary,
                'details' => $details,
            ]);

            $this->recordEvent(
                $locked,
                $actor,
                'finding.recorded',
                [
                    'finding_id' => $finding->id,
                    'criterion_version_id' => $criterionVersionId,
                    'result' => $result,
                    'severity' => $severity,
                ],
            );

            return $finding->fresh(['criterionVersion']);
        });
    }

}