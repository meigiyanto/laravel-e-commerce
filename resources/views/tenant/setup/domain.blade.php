@extends('layouts.app')

@section('content')
<div class="container py-5">
    <div class="mx-auto" style="max-width: 760px">
        <h1 class="h4 mb-2">Pengaturan domain toko</h1>
        <p class="text-muted">Tenant: {{ $tenant->name }}</p>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        @if ($errors->any())
            <div class="alert alert-danger">
                <ul class="mb-0">
                    @foreach ($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @if ($tenant->status === 'pending')
            <div class="alert alert-info">
                Tenant masih menunggu aktivasi platform administrator. Setelah domain
                terverifikasi, administrator platform dapat mengaktifkan toko.
            </div>
        @endif

        <div class="card mb-4">
            <div class="card-body">
                <h2 class="h6">Daftarkan domain pertama</h2>
                <form method="POST" action="{{ route('tenant.setup.domain.store', $tenant) }}">
                    @csrf
                    <div class="mb-3">
                        <label for="domain" class="form-label">Hostname domain</label>
                        <input id="domain" name="domain" type="text" class="form-control"
                            value="{{ old('domain') }}" placeholder="shop.example.com" required>
                        <small class="text-muted">Masukkan hostname saja, tanpa https:// atau path.</small>
                    </div>
                    <button class="btn btn-primary" type="submit">Buat instruksi verifikasi</button>
                </form>
            </div>
        </div>

        @if (session('verification_record'))
            <div class="alert alert-warning">
                <h2 class="h6">Tambahkan DNS TXT record</h2>
                <p class="mb-1">Name: <code>{{ session('verification_record.name') }}</code></p>
                <p class="mb-1">Type: <code>TXT</code></p>
                <p class="mb-1">Value: <code>{{ session('verification_record.value') }}</code></p>
                <p class="mb-0">Berlaku sampai: {{ session('verification_record.expires_at') }}</p>
            </div>
        @endif

        <div class="card">
            <div class="card-body">
                <h2 class="h6 mb-3">Domain terdaftar</h2>
                @forelse ($domains as $domain)
                    <div class="border-bottom py-3">
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                            <div>
                                <strong>{{ $domain->domain }}</strong>
                                <div class="small text-muted">
                                    {{ ucfirst($domain->verification_status) }}
                                    @if ($domain->is_primary)
                                        · Primary
                                    @endif
                                </div>
                            </div>
                            @if ($domain->verification_status !== 'verified')
                                <form method="POST"
                                    action="{{ route('tenant.setup.domain.verify', [$tenant, $domain]) }}">
                                    @csrf
                                    <button class="btn btn-outline-primary btn-sm" type="submit">
                                        Verifikasi DNS
                                    </button>
                                </form>
                            @else
                                <span class="badge bg-success">Verified</span>
                            @endif
                        </div>

                        @if ($domain->verificationChallenge && $domain->verification_status !== 'verified')
                            <div class="mt-2 small">
                                <div>TXT name: <code>{{ $domain->verificationChallenge->record_name }}</code></div>
                                <div>TXT value: <code>{{ $domain->verificationChallenge->record_value }}</code></div>
                                <div>Expires: {{ $domain->verificationChallenge->expires_at?->toDateTimeString() }}</div>
                            </div>
                        @endif
                    </div>
                @empty
                    <p class="text-muted mb-0">Belum ada domain terdaftar.</p>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection