@extends('layouts.app')

@section('title', 'Users')
@section('header', 'Users')

@section('content')
<div class="nk-content-body">
    <div class="nk-block-head nk-block-head-sm">
        <div class="nk-block-between">
            <div class="nk-block-head-content">
                <h3 class="nk-block-title page-title">Users</h3>
                <div class="nk-block-des text-soft">
                    @if(session('success'))
                        <div class="alert alert-success">
                            {{ session('success') }}
                        </div>
                    @endif

                    @if(session('error'))
                        <div class="alert alert-error">
                            {{ session('error') }}
                        </div>
                    @endif
                </div>
            </div>

            <!--
            <div class="nk-block-head-content">
                <a href="#" class="btn btn-outline-primary">
                    <em class="icon ni ni-plus"></em>
                    <span>Add User</span>
                </a>
            </div>
            -->

        </div>
    </div>

    <div class="card card-bordered">
        <div class="card-inner">

            <form method="GET" action="{{ route('admin.users.index') }}" class="search-form mb-3">
                <div class="form-control-wrap">
                    <div class="input-group">
                        <input type="text" name="search" value="{{ $search }}" class="form-control" placeholder="Search name or email">
                        <div class="input-group-append">
                            <button class="btn btn-outline-primary btn-dim" type="submit">Search</button>
                        </div>
                    </div>
                </div>

                @if ($search)
                    <a href="{{ route('admin.users.index') }}" class="btn btn-secondary">
                        Reset
                    </a>
                @endif
            </form>

            <table class="nowrap table table-bordered">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Registered</th>
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
                                 {{ $user->role }}
                            </td>

                            <td>
                                {{ $user->created_at->format('d M Y H:i') }}
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="empty-state">
                                User not found
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>

            @if ($users->hasPages())
                <div class="pagination-wrapper">
                    {{ $users->links() }}
                </div>
            @endif

        </div>
    </div>

</div>
@endsection
