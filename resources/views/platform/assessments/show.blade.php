@extends('layouts.platform')

@section('title', 'Assessment #'.$assessment->assessment_number)

@section('content')
<div class="nk-block-head nk-block-head-sm">
    <div class="nk-block-between">
        <div class="nk-block-head-content">
            <a href="{{ route('platform.evolution.assessments.index') }}" class="text-soft small">← Assessment Lifecycle</a>
            <h3 class="nk-block-title page-title mt-1">Assessment #{{ $assessment->assessment_number }}</h3>
            <div class="nk-block-des text-soft">
                {{ $assessment->program?->code }} / {{ $assessment->phase?->name }}
            </div>
        </div>
        <div class="nk-block-head-content">
            <span class="badge bg-primary fs-6">{{ str_replace('_', ' ', ucfirst($assessment->status)) }}</span>
        </div>
    </div>
</div>

<div class="row g-3 mb-4">
    <div class="col-md-6">
        <div class="card card-bordered h-100">
            <div class="card-inner">
                <h6 class="title mb-3">Assessment Information</h6>
                <dl class="row gy-2 mb-0">
                    <dt class="col-sm-5 text-soft">Assessor</dt><dd class="col-sm-7">{{ $assessment->assessor?->name }} ({{ $assessment->assessor?->email }})</dd>
                    <dt class="col-sm-5 text-soft">Baseline</dt><dd class="col-sm-7"><code>{{ $assessment->baseline_commit }}</code></dd>
                    <dt class="col-sm-5 text-soft">Ruleset</dt><dd class="col-sm-7">{{ $assessment->ruleset_version }}</dd>
                    <dt class="col-sm-5 text-soft">Started</dt><dd class="col-sm-7">{{ $assessment->started_at?->format('d M Y H:i') ?? '-' }}</dd>
                    <dt class="col-sm-5 text-soft">Finished</dt><dd class="col-sm-7">{{ $assessment->finished_at?->format('d M Y H:i') ?? '-' }}</dd>
                    @if ($assessment->cancelled_at)
                    <dt class="col-sm-5 text-soft">Cancelled</dt><dd class="col-sm-7">{{ $assessment->cancelled_at->format('d M Y H:i') }} — {{ $assessment->cancellation_reason }}</dd>
                    @endif
                </dl>
                <hr>
                <div class="text-soft small mb-1">
                    Environment
                </div>
                <pre class="bg-lighter p-3 rounded mb-0"><code>{{ json_encode($assessment->environment, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card card-bordered h-100">
            <div class="card-inner">
                <h6 class="title mb-3">Lifecycle Actions</h6>
                @if ($canManage && $assessment->status === 'draft')
                <form method="POST" action="{{ route('platform.api.evolution.assessments.start', $assessment) }}" class="mb-2">
                    @csrf<button class="btn btn-primary" type="submit">Start Assessment</button>
                </form>
                @endif
                @if ($canManage && $assessment->status === 'running')
                <form method="POST" action="{{ route('platform.api.evolution.assessments.complete', $assessment) }}" class="d-inline-block me-2 mb-2">
                    @csrf<button class="btn btn-success" type="submit" @disabled(! $canComplete) title="{{ $canComplete ? 'Complete assessment' : 'Semua kriteria harus memiliki finding terlebih dahulu.' }}">Complete</button>
                </form>
                <form method="POST" action="{{ route('platform.api.evolution.assessments.fail', $assessment) }}" class="mb-2">
                    @csrf<input class="form-control mb-2" name="reason" placeholder="Alasan gagal" minlength="3" required><button class="btn btn-danger" type="submit">Mark Failed</button>
                </form>
                @endif
                @if ($canManage && in_array($assessment->status, ['draft', 'running'], true))
                @if ($assessment->status === 'running' && ! auth()->user()->isPlatformAdmin())
                <div class="text-soft small">
                    Assessment running hanya dapat dibatalkan oleh platform admin.
                </div>
                @else
                <form method="POST" action="{{ route('platform.api.evolution.assessments.cancel', $assessment) }}" class="mt-2">
                    @csrf<input class="form-control mb-2" name="reason" placeholder="Alasan pembatalan" minlength="3" required><button class="btn btn-outline-danger" type="submit">Cancel Assessment</button>
                </form>
                @endif
                @endif
                @if (! $canManage)
                <p class="text-soft mb-0">
                    Anda tidak memiliki hak untuk mengubah lifecycle assessment ini.
                </p>
                @elseif (! in_array($assessment->status, ['draft', 'running'], true))
                <p class="text-soft mb-0">
                    Status ini tidak memiliki aksi lifecycle yang tersedia.
                </p>
                @endif
                @if ($assessment->status === 'running' && ! $canComplete)
                <div class="alert alert-warning mt-3 mb-0">
                    Assessment belum dapat diselesaikan. Pastikan setiap snapshot kriteria sudah memiliki finding.
                </div>
                @endif
            </div>
        </div>
    </div>
</div>

<div class="card card-bordered mb-4">
    <div class="card-inner">
        <div class="card-title-group mb-3">
            <div class="card-title">
                <h6 class="title">Criteria Snapshot &amp; Findings</h6>
            </div>
            <span class="badge bg-light text-dark">
                {{ $assessment->findings->count() }} / {{ $assessment->criteria->count() }}
            </span>
        </div>

        @foreach ($assessment->criteria as $criterion)
            @php($finding = $assessment->findings->firstWhere('criterion_version_id', $criterion->criterion_version_id))

            <div class="border rounded p-3 mb-3">
                <div class="d-flex justify-content-between flex-wrap gap-2 mb-2">
                    <div>
                        <strong>{{ $criterion->name }}</strong>
                        <div class="text-soft small">
                            {{ $criterion->criterion_key }} · Version {{ $criterion->criterion_version }}
                        </div>
                    </div>
                    <span class="badge {{ $finding ? 'bg-success' : 'bg-warning' }}">
                        {{ $finding ? 'Recorded' : 'Pending' }}
                    </span>
                </div>

                <div class="text-soft small mb-2">
                    Severity: {{ ucfirst($criterion->severity) }} ·
                    Evaluation: {{ ucfirst($criterion->evaluation_method) }}
                </div>

                @if ($finding)
                    <div class="bg-lighter rounded p-3">
                        <div class="mb-1">
                            <strong>Result:</strong>
                            {{ ucfirst(str_replace('_', ' ', $finding->result)) }}
                        </div>
                        <div class="mb-1">
                            <strong>Severity:</strong>
                            {{ ucfirst($finding->severity) }}
                        </div>
                        @if ($finding->summary)
                            <div>{{ $finding->summary }}</div>
                        @endif
                        <div class="text-soft small mt-2">
                            Recorded {{ $finding->created_at?->format('d M Y H:i:s') }}
                        </div>
                    </div>
                @elseif ($assessment->status === 'running' && $canManage)
                    <form method="POST" action="{{ route('platform.evolution.assessments.findings.store', $assessment) }}">
                        @csrf
                        <input type="hidden" name="criterion_version_id" value="{{ $criterion->criterion_version_id }}">

                        <div class="row g-2">
                            <div class="col-md-6">
                                <label class="form-label">Result</label>
                                <select name="result" class="form-select" required>
                                    <option value="">Select result</option>
                                    <option value="pass">Pass</option>
                                    <option value="fail">Fail</option>
                                    <option value="partial">Partial</option>
                                    <option value="not_applicable">Not Applicable</option>
                                    <option value="error">Error</option>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Severity</label>
                                <select name="severity" class="form-select" required>
                                    <option value="">Select severity</option>
                                    <option value="critical">Critical</option>
                                    <option value="high">High</option>
                                    <option value="medium">Medium</option>
                                    <option value="low">Low</option>
                                    <option value="info">Info</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label">Summary</label>
                                <textarea name="summary" class="form-control" rows="2" maxlength="10000"></textarea>
                            </div>
                            <div class="col-12">
                                <button type="submit" class="btn btn-primary btn-sm">
                                    Record Finding
                                </button>
                            </div>
                        </div>
                    </form>
                @else
                    <div class="text-soft small">Finding belum dicatat.</div>
                @endif
            </div>
        @endforeach
    </div>
</div>

<div class="card card-bordered">
    <div class="card-inner">
        <div class="card-title-group mb-3">
            <div class="card-title">
                <h6 class="title">Lifecycle Event History</h6>
            </div>
            <span class="badge bg-light text-dark">{{ $assessment->events->count() }} events</span>
        </div>
        <div class="nk-timeline">
            @forelse ($assessment->events->sortBy('created_at') as $event)
            <div class="nk-timeline-item">
                <div class="nk-timeline-item-inner">
                    <div class="nk-timeline-symbol">
                        <em class="icon ni ni-activity-alt"></em>
                    </div>
                    <div class="nk-timeline-content">
                        <div class="d-flex flex-wrap justify-content-between gap-2">
                            <strong>{{ str_replace('.', ' / ', $event->event_key) }}</strong><span class="text-soft small">{{ $event->created_at?->format('d M Y H:i:s') }}</span>
                        </div>
                        <div class="text-soft small">
                            Actor: {{ $event->actor?->name ?? $event->actor_type }}
                        </div>
                        @if ($event->payload)<pre class="bg-lighter p-2 mt-2 rounded mb-0"><code>{{ json_encode($event->payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>@endif
                    </div>
                </div>
            </div>
            @empty
            <p class="text-soft mb-0">
                Belum ada event lifecycle.
            </p>
            @endforelse
        </div>
    </div>
</div>
@endsection