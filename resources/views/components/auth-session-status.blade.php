@props(['status'])

@if ($status)
    <div {{ $attributes->merge(['class' => 'alert alert-info', 'role' => 'alert']) }}>
        {{ $status }}
    </div>
@endif
