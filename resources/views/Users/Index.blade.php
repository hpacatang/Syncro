@extends('layouts.app')

@section('page-title', 'User management')
@section('page-subtitle', 'Department & PAIR accounts')

@section('content')
<div class="container-fluid px-0 py-1">
    <div class="d-flex justify-content-end mb-3">
        <a href="{{ route('users.create') }}" class="btn btn-primary">
            <i class="bi bi-person-plus me-1"></i> New account
        </a>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-6">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" name="search" id="search" class="form-control" placeholder="Name, profile, or email" value="{{ request('search') }}">
                </div>
                <div class="col-md-4">
                    <label for="role" class="form-label">Role</label>
                    <select name="role" id="role" class="form-select">
                        <option value="">All roles</option>
                        <option value="department" {{ request('role') === 'department' ? 'selected' : '' }}>Department</option>
                        <option value="pair" {{ request('role') === 'pair' ? 'selected' : '' }}>PAIR</option>
                        <option value="org" {{ request('role') === 'org' ? 'selected' : '' }}>Organization</option>
                        <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                        <option value="super_admin" {{ request('role') === 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary w-100">Search</button>
                    <a href="{{ route('users.index') }}" class="btn btn-secondary w-100">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Profile name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Department</th>
                        <th>Created</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td>{{ $user->displayName() }}</td>
                            <td><code>{{ $user->name }}</code></td>
                            <td>{{ $user->email }}</td>
                            <td><span class="badge bg-secondary">{{ $user->roleLabel() }}</span></td>
                            <td>
                                @if($user->department)
                                    {{ $user->department->displayName() }}
                                @else
                                    —
                                @endif
                            </td>
                            <td><small>{{ $user->created_at->format('M d, Y') }}</small></td>
                            <td>
                                <a href="{{ route('users.show', $user) }}" class="btn btn-sm btn-outline-primary">View</a>
                                <a href="{{ route('users.edit', $user) }}" class="btn btn-sm btn-outline-secondary">Edit</a>
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
