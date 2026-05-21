@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-people me-2"></i> User Management</h2>
    </div>

    <!-- Search and Filter -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-6">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" name="search" id="search" class="form-control" placeholder="Search by name or email" value="{{ request('search') }}">
                </div>
                <div class="col-md-4">
                    <label for="role" class="form-label">Role</label>
                    <select name="role" id="role" class="form-select">
                        <option value="">-- All Roles --</option>
                        <option value="super_admin" {{ request('role') === 'super_admin' ? 'selected' : '' }}>Super Admin</option>
                        <option value="admin" {{ request('role') === 'admin' ? 'selected' : '' }}>Admin</option>
                        <option value="pair" {{ request('role') === 'pair' ? 'selected' : '' }}>PAIR</option>
                        <option value="org" {{ request('role') === 'org' ? 'selected' : '' }}>Organization</option>
                        <option value="user" {{ request('role') === 'user' ? 'selected' : '' }}>User</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary w-100">Search</button>
                    <a href="{{ route('users.index') }}" class="btn btn-secondary w-100">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Users Table -->
    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Name</th>
                        <th>Email</th>
                        <th>Role</th>
                        <th>Created</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($users as $user)
                        <tr>
                            <td>{{ $user->name }}</td>
                            <td>{{ $user->email }}</td>
                            <td>
                                <span class="badge bg-{{ $user->role === 'admin' ? 'danger' : ($user->role === 'pair' ? 'info' : 'secondary') }}">
                                    {{ ucfirst($user->role) }}
                                </span>
                            </td>
                            <td><small>{{ $user->created_at->format('M d, Y') }}</small></td>
                            <td>
                                <div class="btn-group btn-group-sm">
                                    @if($user->role === 'user')
                                    <form method="POST" action="{{ route('users.accept', $user) }}" style="display: inline-block;">
                                        @csrf
                                        <button type="submit" class="btn btn-success btn-sm" onclick="return confirm('Accept this user as an organization?')">Accept</button>
                                    </form>
                                    @endif
                                    
                                    @if($user->id !== auth()->id())
                                    <form method="POST" action="{{ route('users.reject', $user) }}" style="display: inline-block; margin-left: 0.25rem;">
                                        @csrf
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="return confirm('Reject and remove this user?')">Reject</button>
                                    </form>
                                    @else
                                    <button class="btn btn-secondary btn-sm disabled" style="margin-left: 0.25rem;">Reject</button>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="5" class="text-center py-4 text-muted">
                                No users found
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>

    <!-- Pagination -->
    <div class="d-flex justify-content-center mt-4">
        {{ $users->links() }}
    </div>
</div>
@endsection
