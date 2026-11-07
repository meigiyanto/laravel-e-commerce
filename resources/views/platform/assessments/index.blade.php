@extends('layouts.platform')

@section('title', 'Assessment Lifecycle')

@section('content')
<div class="nk-block-head nk-block-head-sm">
    <div class="nk-block-between">
        <div class="nk-block-head-content">
            <h3 class="nk-block-title page-title">Assessment Lifecycle</h3>
            <div class="nk-block-des text-soft">Buat assessment, pantau status, dan telusuri riwayat transisinya.</div>
        </div>
    </div>
</div>

<div class="card card-bordered mb-4">
    <div class="card-inner">
        <div class="card-title-group mb-3"><div class="card-title"><h6 class="title">Buat Assessment Draft</h6></div></div>
        <form method="POST" action="{{ route('platform.evolution.assessments.store') }}">
            @csrf
            <div class="row g-3">
                <div class="col-md-6">
                    <label class="form-label" for="program_id">Program</label>
                    <select class="form-select" id="program_id" name="program_id" required>
                        <option value="">Pilih program aktif</option>
                        @foreach ($programs as $program)
                            <option value="{{ $program->id }}" @selected(old('program_id') == $program->id)>{{ $program->code }} — {{ $program->name }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="phase_id">Phase</label>
                    <select class="form-select" id="phase_id" name="phase_id" required>
                        <option value="">Pilih phase</option>
                        @foreach ($programs as $program)
                            @foreach ($program->phases as $phase)
                                <option value="{{ $phase->id }}" data-program="{{ $program->id }}" @selected(old('phase_id') == $phase->id)>{{ $program->code }} / {{ $phase->sequence }}. {{ $phase->name }}</option>
                            @endforeach
                        @endforeach
                    </select>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="baseline_commit">Baseline Commit</label>
                    <input class="form-control" id="baseline_commit" name="baseline_commit" maxlength="64" value="{{ old('baseline_commit') }}" required>
                </div>
                <div class="col-md-6">
                    <label class="form-label" for="ruleset_version">Ruleset Version</label>
                    <input class="form-control" id="ruleset_version" name="ruleset_version" maxlength="64" value="{{ old('ruleset_version', '1.0.0') }}" required>
                </div>
                <div class="col-12">
                    <label class="form-label" for="environment">Environment (JSON)</label>
                    <textarea class="form-control" id="environment" name="environment" rows="3" required>{{ old('environment', "{\n  \"php\": \"".PHP_VERSION."\",\n  \"database\": \"".config('database.default')."\"\n}") }}</textarea>
                </div>
                <div class="col-12">
                    <button class="btn btn-primary" type="submit">Create Draft</button>
                </div>
            </div>
        </form>
        @if ($errors->any())
            <div class="alert alert-danger mt-3 mb-0"><ul class="mb-0">@foreach ($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul></div>
        @endif
    </div>
</div>

<div class="card card-bordered">
    <div class="card-inner">
        <div class="card-title-group mb-3"><div class="card-title"><h6 class="title">Assessment Records</h6></div><span class="badge bg-light text-dark">{{ $assessments->total() }} records</span></div>
        <div class="table-responsive">
            <table class="table table-hover align-middle">
                <thead><tr><th>Assessment</th><th>Program / Phase</th><th>Assessor</th><th>Status</th><th>Criteria</th><th>Created</th><th></th></tr></thead>
                <tbody>
                    @forelse ($assessments as $assessment)
                        @php($statusClass = match($assessment->status) { 'running' => 'bg-info', 'completed', 'approved' => 'bg-success', 'failed', 'rejected' => 'bg-danger', 'cancelled' => 'bg-secondary', default => 'bg-warning' })
                        <tr>
                            <td><strong>#{{ $assessment->assessment_number }}</strong><div class="text-soft small">ID {{ $assessment->id }}</div></td>
                            <td>{{ $assessment->program?->code }}<div class="text-soft small">{{ $assessment->phase?->name }}</div></td>
                            <td>{{ $assessment->assessor?->name ?? '-' }}</td>
                            <td><span class="badge {{ $statusClass }}">{{ str_replace('_', ' ', ucfirst($assessment->status)) }}</span></td>
                            <td>{{ $assessment->findings_count }} / {{ $assessment->criteria_count }} evaluated</td>
                            <td>{{ $assessment->created_at?->format('d M Y H:i') }}</td>
                            <td><a class="btn btn-sm btn-outline-primary" href="{{ route('platform.evolution.assessments.show', $assessment) }}">Detail</a></td>
                        </tr>
                    @empty
                        <tr><td colspan="7" class="text-center text-soft py-4">Belum ada assessment.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        {{ $assessments->links() }}
    </div>
</div>
@endsection

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', () => {
    const program = document.getElementById('program_id');
    const phase = document.getElementById('phase_id');
    const filterPhases = () => {
        [...phase.options].forEach(option => {
            if (!option.value) return;
            option.hidden = option.dataset.program !== program.value;
            if (option.hidden && option.selected) phase.value = '';
        });
    };
    program.addEventListener('change', filterPhases);
    filterPhases();
});
</script>
@endpush
