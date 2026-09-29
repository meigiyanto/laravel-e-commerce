@extends('layouts.app')

@section('title', 'Users')

@section('header', 'Users')

@section('content')
<div class="admin-container">

    <div class="page-header">
        <div>
            <h1>Users</h1>
            <p>Daftar pengguna yang terdaftar di database.</p>
        </div>
    </div>

    <div class="card">

        <form method="GET" action="{{ route('admin.users.index') }}" class="search-form">
            <input
                type="text"
                name="search"
                value="{{ $search }}"
                placeholder="Cari nama atau email..."
            >

            <button type="submit" class="btn btn-primary">
                Cari
            </button>

            @if ($search)
                <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
                    Reset
                </a>
            @endif
        </form>

        <div class="table-wrapper">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Nama</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Terdaftar</th>
                    </tr>
                </thead>

                <tbody>
                    @forelse ($users as $user)
                        <tr>
                            <td>{{ $user->id }}</td>

                            <td>
                                <strong>{{ $user->name }}</strong>
                            </td>

                            <td>{{ $user->email }}</td>

                            <td>
                                <span class="badge">
                                    {{ $user->role ?? 'user' }}
                                </span>
                            </td>

                            <td>
                                {{ $user->created_at->format('d M Y H:i') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="empty-state">
                                Tidak ada user ditemukan.
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        @if ($users->hasPages())
            <div class="pagination-wrapper">
                {{ $users->links() }}
            </div>
        @endif

    </div>

</div>
@endsection
