@extends('layouts.admin')

@section('title', 'User Role Management')
@section('header', 'User Role Management')

@section('content')
    <div class="nk-block-head nk-block-head mb-3">
        <div class="nk-block-between flex-wrap gap-2">
            <div class="nk-block-head-content">
                <h3 class="nk-block-title page-title">User Role Management</h3>
                <div class="nk-block-des text-soft">
                    <p>Kelola hak akses pengguna. Hanya Admin yang dapat membuka halaman ini dan mengubah role.</p>
                </div>
            </div>
            <div class="nk-block-head-content">
                <span class="badge bg-primary">{{ $users->total() }} pengguna</span>
            </div>
        </div>
    </div>

    <div class="row g-gs mb-3">
        <div class="col-sm-4">
            <div class="card card-bordered h-100">
                <div class="card-inner">
                    <div class="card-title-group align-start mb-2">
                        <div class="card-title">
                            <h6 class="title">Admin</h6>
                        </div>
                    </div>
                    <div class="align-end flex-sm-wrap g-4 flex-md-nowrap">
                        <div>
                            <span class="amount">{{ $roleCounts['admin'] }}</span>
                            <span class="sub-title"> Administrator</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-4">
            <div class="card card-bordered h-100">
                <div class="card-inner">
                    <div class="card-title-group align-start mb-2">
                        <div class="card-title">
                            <h6 class="title">Staff</h6>
                        </div>
                    </div>
                    <div class="align-end flex-sm-wrap g-4 flex-md-nowrap">
                        <div>
                            <span class="amount">{{ $roleCounts['staff'] }}</span>
                            <span class="sub-title"> Operasional toko</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-sm-4">
            <div class="card card-bordered h-100">
                <div class="card-inner">
                    <div class="card-title-group align-start mb-2">
                        <div class="card-title">
                            <h6 class="title">Customer</h6>
                        </div>
                    </div>
                    <div class="align-end flex-sm-wrap g-4 flex-md-nowrap">
                        <div>
                            <span class="amount">{{ $roleCounts['customer'] }}</span>
                            <span class="sub-title"> Pelanggan toko</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card card-bordered">
        <div class="card-inner border-bottom">
            <form method="GET" action="{{ route('admin.users.index') }}" class="row g-2 align-items-end">
                <div class="col-md-6">
                    <label class="form-label" for="user-search">Cari pengguna</label>
                    <input id="user-search" type="search" name="search" value="{{ $search }}" class="form-control" placeholder="Nama atau email">
                </div>
                <div class="col-md-3">
                    <label class="form-label" for="user-role-filter">Filter role</label>
                    <select id="user-role-filter" name="role" class="form-select">
                        <option value="">Semua role</option>
                        <option value="admin" @selected($role === 'admin')>Admin</option>
                        <option value="staff" @selected($role === 'staff')>Staff</option>
                        <option value="customer" @selected($role === 'customer')>Customer</option>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Filter</button>
                    <a href="{{ route('admin.users.index') }}" class="btn btn-outline-light">Reset</a>
                </div>
            </form>
        </div>

        <div class="card-inner p-2">
            <div class="table-responsive">
                <table class="table table-hover mb-2 datatable-init">
                    <thead>
                        <tr>
                            <th scope="col">Pengguna</th>
                            <th scope="col">Role saat ini</th>
                            <th scope="col">Terdaftar</th>
                            <th scope="col" style="min-width: 290px">Ubah role</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse ($users as $user)
                            <tr>
                                <td class="align-middle">
                                    <div class="fw-bold">{{ $user->name }}</div>
                                    <div class="text-soft small">{{ $user->email }}</div>
                                    @if (auth()->id() === $user->id)
                                        <span class="badge bg-outline-primary mt-1">Akun Anda</span>
                                    @endif
                                </td>
                                <td class="align-middle">
                                    @php($currentRole = $user->role === 'user' ? 'customer' : $user->role)
                                    @if ($currentRole === 'admin')
                                        <span class="badge bg-danger">Admin</span>
                                    @elseif ($currentRole === 'staff')
                                        <span class="badge bg-warning">Staff</span>
                                    @else
                                        <span class="badge bg-info">Customer</span>
                                    @endif
                                </td>
                                <td class="align-middle">{{ $user->created_at?->format('d M Y') ?? '—' }}</td>
                                <td class="align-middle">
                                    <form method="POST" action="{{ route('admin.users.role.update', $user) }}" class="d-flex flex-wrap gap-2 align-items-center">
                                        @csrf
                                        @method('PATCH')
                                        <label class="visually-hidden" for="role-{{ $user->id }}">Role untuk {{ $user->name }}</label>
                                        <select id="role-{{ $user->id }}" name="role" class="form-select form-select-sm" style="max-width: 145px" required>
                                            <option value="admin" @selected($currentRole === 'admin')>Admin</option>
                                            <option value="staff" @selected($currentRole === 'staff')>Staff</option>
                                            <option value="customer" @selected($currentRole === 'customer')>Customer</option>
                                        </select>
                                        <button type="submit" class="btn btn-sm btn-primary" @disabled(auth()->id() === $user->id)>Simpan</button>
                                    </form>
                                    @if (auth()->id() === $user->id)
                                        <small class="text-soft d-block mt-1">Role akun sendiri tidak dapat diubah.</small>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr><td colspan="4" class="text-center text-soft py-5">Tidak ada pengguna yang cocok dengan filter.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
        @if ($users->hasPages())
            <div class="card-inner border-top">{{ $users->links() }}</div>
        @endif
    </div>

    <div class="card card-bordered mt-3">
        <div class="card-inner">
            <h6 class="title mb-2">Ringkasan hak akses</h6>
            <ul class="list list-sm list-checked">
                <li><strong>Admin:</strong> akses penuh, termasuk pengelolaan pengguna dan role.</li>
                <li><strong>Staff:</strong> dashboard operasional, produk, kategori, inventaris, pesanan, dan refund; tanpa pengelolaan akun/role.</li>
                <li><strong>Customer:</strong> berbelanja, checkout, melihat pesanan sendiri, wishlist, ulasan, dan profil.</li>
            </ul>
        </div>
    </div>
@endsection
