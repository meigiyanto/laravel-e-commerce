<?php

namespace App\Http\Controllers\Platform\Evolution;

use App\Http\Controllers\Controller;
use App\Models\Evolution\Assessment;
use App\Models\Evolution\Program;
use App\Models\Evolution\ProgramPhase;
use App\Models\User;
use App\Services\Evolution\AssessmentLifecycleService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AssessmentLifecycleController extends Controller
{
    public function __construct(private readonly AssessmentLifecycleService $lifecycle) {}

    public function index(): View
    {
        $assessments = Assessment::query()
        ->with(['program:id,code,name', 'phase:id,name', 'assessor:id,name'])
        ->withCount(['criteria', 'findings'])
        ->latest('id')
        ->paginate(20);

        $programs = Program::query()
        ->where('status', 'active')
        ->with('phases:id,program_id,name,phase_key,sequence')
        ->orderBy('name')
        ->get(['id', 'code', 'name']);

        return view('platform.assessments.index', compact('assessments', 'programs'));
    }

    public function show(Assessment $assessment): View
    {
        $assessment->load([
            'program:id,code,name,status',
            'phase:id,name,phase_key',
            'assessor:id,name,email',
            'canceller:id,name',
            'criteria',
            'findings',
            'events.actor:id,name',
        ]);

        $actor = request()->user();
        $canManage = $actor->isPlatformAdmin() || (int) $assessment->assessor_id === (int) $actor->id;
        $criteriaIds = $assessment->criteria->pluck('criterion_version_id');
        $evaluatedIds = $assessment->findings->pluck('criterion_version_id')->unique();
        $canComplete = $criteriaIds->diff($evaluatedIds)->isEmpty() && $criteriaIds->isNotEmpty();

        return view('platform.assessments.show', compact(
            'assessment',
            'canManage',
            'canComplete'
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'program_id' => ['required', 'integer', 'exists:evolution_programs,id'],
            'phase_id' => ['required', 'integer', 'exists:evolution_program_phases,id'],
            'baseline_commit' => ['required', 'string', 'max:64'],
            'environment' => ['required', 'string'],
            'ruleset_version' => ['required', 'string', 'max:64'],
            'supersedes_assessment_id' => ['nullable', 'integer', 'exists:evolution_assessments,id'],
        ]);

        $environment = json_decode($data['environment'], true);
        if (! is_array($environment) || json_last_error() !== JSON_ERROR_NONE) {
            throw ValidationException::withMessages([
                'environment' => 'Environment harus berupa JSON object yang valid.',
            ]);
        }

        $assessment = $this->lifecycle->createDraft(
            Program::query()->findOrFail($data['program_id']),
            ProgramPhase::query()->findOrFail($data['phase_id']),
            $request->user(),
            $data['baseline_commit'],
            $environment,
            $data['ruleset_version'],
            isset($data['supersedes_assessment_id']) ? Assessment::query()->findOrFail($data['supersedes_assessment_id']) : null,
        );

        return redirect()
        ->route('platform.assessments.show', $assessment)
        ->with('success', 'Assessment draft berhasil dibuat.');
    }

    public function apiIndex(Request $request): JsonResponse
    {
        $data = $request->validate([
            'program_id' => ['nullable', 'integer', 'exists:evolution_programs,id'],
            'status' => ['nullable', 'string', 'max:32'],
        ]);

        $assessments = Assessment::query()
        ->with(['program:id,code,name', 'phase:id,name', 'assessor:id,name'])
        ->when($data['program_id'] ?? null, fn ($query, $id) => $query->where('program_id', $id))
        ->when($data['status'] ?? null, fn ($query, $status) => $query->where('status', $status))
        ->latest('id')
        ->paginate(20);

        return response()->json($assessments);
    }

    public function apiStore(Request $request, Program $program): JsonResponse
    {
        $data = $request->validate([
            'phase_id' => ['required', 'integer', 'exists:evolution_program_phases,id'],
            'baseline_commit' => ['required', 'string', 'max:64'],
            'environment' => ['required', 'array'],
            'ruleset_version' => ['required', 'string', 'max:64'],
            'supersedes_assessment_id' => ['nullable', 'integer', 'exists:evolution_assessments,id'],
        ]);

        $assessment = $this->lifecycle->createDraft(
            $program,
            ProgramPhase::query()->findOrFail($data['phase_id']),
            $request->user(),
            $data['baseline_commit'],
            $data['environment'],
            $data['ruleset_version'],
            isset($data['supersedes_assessment_id']) ? Assessment::query()->findOrFail($data['supersedes_assessment_id']) : null,
        );

        return response()->json([
            'data' => $this->assessmentPayload($assessment->load(['program', 'phase', 'assessor', 'criteria', 'events.actor'])),
        ], 201);
    }

    public function apiShow(Assessment $assessment): JsonResponse
    {
        $assessment->load([
            'program:id,code,name',
            'phase:id,name,phase_key',
            'assessor:id,name,email',
            'criteria',
            'findings',
            'events.actor:id,name',
        ]);

        return response()->json(['data' => $this->assessmentPayload($assessment)]);
    }

    public function start(Request $request, Assessment $assessment): JsonResponse
    {
        return $this->runAction(fn () => $this->lifecycle->start($assessment, $request->user()));
    }

    public function complete(Request $request, Assessment $assessment): JsonResponse
    {
        return $this->runAction(fn () => $this->lifecycle->complete($assessment, $request->user()));
    }

    public function fail(Request $request, Assessment $assessment): JsonResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:3', 'max:5000']]);
        return $this->runAction(fn () => $this->lifecycle->fail($assessment, $request->user(), $data['reason']));
    }

    public function cancel(Request $request, Assessment $assessment): JsonResponse
    {
        $data = $request->validate(['reason' => ['required', 'string', 'min:3', 'max:5000']]);
        return $this->runAction(fn () => $this->lifecycle->cancel($assessment, $request->user(), $data['reason']));
    }

    private function runAction(callable $action): JsonResponse
    {
        $assessment = $action();
        $assessment->load(['program', 'phase', 'assessor', 'criteria', 'events.actor']);

        return response()->json([
            'message' => 'Assessment lifecycle berhasil diperbarui.',
            'data' => $this->assessmentPayload($assessment),
        ]);
    }

    private function assessmentPayload(Assessment $assessment): array
    {
        return [
            'id' => $assessment->id,
            'assessment_number' => $assessment->assessment_number,
            'status' => $assessment->status,
            'program' => ['id' => $assessment->program?->id,
                'code' => $assessment->program?->code,
                'name' => $assessment->program?->name],
            'phase' => ['id' => $assessment->phase?->id,
                'key' => $assessment->phase?->phase_key,
                'name' => $assessment->phase?->name],
            'assessor' => ['id' => $assessment->assessor?->id,
                'name' => $assessment->assessor?->name],
            'baseline_commit' => $assessment->baseline_commit,
            'environment' => $assessment->environment,
            'ruleset_version' => $assessment->ruleset_version,
            'started_at' => $assessment->started_at,
            'finished_at' => $assessment->finished_at,
            'cancelled_at' => $assessment->cancelled_at,
            'cancellation_reason' => $assessment->cancellation_reason,
            'criteria' => $assessment->criteria,
            'findings' => $assessment->relationLoaded('findings') ? $assessment->findings : [],
            'events' => $assessment->events->map(fn ($event) => [
                'id' => $event->id,
                'event_key' => $event->event_key,
                'actor_type' => $event->actor_type,
                'actor' => $event->actor?->name,
                'payload' => $event->payload,
                'created_at' => $event->created_at,
            ]),
        ];
    }


    public function storeFinding(
        Request $request,
        Assessment $assessment
    ): JsonResponse|RedirectResponse {
        $data = $request->validate([
            'criterion_version_id' => [
                'required',
                'integer',
            ],
            'result' => [
                'required',
                'string',
                'in:pass,fail,partial,not_applicable,error',
            ],
            'severity' => [
                'required',
                'string',
                'in:critical,high,medium,low,info',
            ],
            'summary' => [
                'nullable',
                'string',
                'max:10000',
            ],
            'details' => [
                'nullable',
                'array',
            ],
        ]);

        $finding = $this->lifecycle->recordFinding(
            $assessment,
            $request->user(),
            (int) $data['criterion_version_id'],
            $data['result'],
            $data['severity'],
            $data['summary'] ?? null,
            $data['details'] ?? null,
        );

        if ($request->expectsJson()) {
            return response()->json([
                'message' => 'Finding berhasil dicatat.',
                'data' => [
                    'id' => $finding->id,
                    'assessment_id' => $finding->assessment_id,
                    'criterion_version_id' => $finding->criterion_version_id,
                    'result' => $finding->result,
                    'severity' => $finding->severity,
                    'summary' => $finding->summary,
                    'details' => $finding->details,
                    'created_at' => $finding->created_at,
                ],
            ], 201);
        }

        return redirect()
        ->route('platform.evolution.assessments.show', $assessment)
        ->with('success', 'Finding berhasil dicatat.');
    }
}