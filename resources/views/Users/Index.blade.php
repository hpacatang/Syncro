@extends('layouts.app')

@section('page-title', 'User management')
@section('page-subtitle', 'Department & PAIR accounts')

@section('content')
@php $userRoutePrefix = auth()->user()->isDepartment() ? 'department.users' : 'users'; @endphp
<div class="container-fluid px-0 py-1">
    <div class="d-flex justify-content-end mb-3">
        <a href="{{ route($userRoutePrefix.'.create') }}" class="btn btn-primary">
            <i class="bi bi-person-plus me-1"></i> New account
        </a>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3" data-debounce-search="450">
                <div class="col-md-6">
                    <label for="search" class="form-label">Search</label>
                    <input type="search" name="search" id="search" class="form-control" placeholder="Name, profile, or email" value="{{ request('search') }}" data-debounce-search-field data-submitted-value="{{ request('search') }}">
                </div>
                <div class="col-md-4">
                    <label for="role" class="form-label">Role</label>
                    <select name="role" id="role" class="form-select">
                        <option value="">All roles</option>
                        <option value="department" {{ request('role') === 'department' ? 'selected' : '' }}>Department</option>
                        <option value="pair" {{ request('role') === 'pair' ? 'selected' : '' }}>PAIR</option>
                        <option value="org" {{ request('role') === 'org' ? 'selected' : '' }}>Student</option>
                        <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                        <option value="super_admin" {{ request('role') === 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary w-100">Search</button>
                    <a href="{{ route($userRoutePrefix.'.index') }}" class="btn btn-secondary w-100">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0 align-middle">
                <thead class="table-light align-middle text-nowrap syncro-table-head">
                    <tr>
                        <th class="ps-3">PROFILE NAME</th>
                        <th>USERNAME</th>
                        <th>EMAIL</th>
                        <th>ROLE</th>
                        <th>DEPARTMENT</th>
                        <th>CREATED</th>
                        <th class="pe-3 text-end">ACTIONS</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td>{{ $user->displayName() }}</td>
                            <td><code>{{ $user->name }}</code></td>
                            <td>{{ $user->email }}</td>
                            <td><span class="badge bg-warning text-dark">{{ $user->roleLabel() }}</span></td>
                            <td>
                                @if($user->department)
                                    {{ $user->department->displayName() }}
                                @else
                                    —
                                @endif
                            </td>
                            <td><small><x-formatted-date :at="$user->created_at" /></small></td>
                            <td>
                                <a href="{{ route($userRoutePrefix.'.show', $user) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye me-1"></i> View</a>
                                <a href="{{ route($userRoutePrefix.'.edit', $user) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil me-1"></i> Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No users found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($users->hasPages())
            <div class="card-footer">{{ $users->links() }}</div>
        @endif
    </div>
</div>
@endsection
