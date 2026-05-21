@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-person me-2"></i> User Details</h2>
        <a href="{{ route('users.edit', $user) }}" class="btn btn-primary">
            <i class="bi bi-pencil me-2"></i> Edit
        </a>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <h6 class="text-muted mb-2">Name</h6>
                    <p class="mb-3">{{ $user->name }}</p>

                    <h6 class="text-muted mb-2">Email</h6>
                    <p class="mb-3">{{ $user->email }}</p>
                </div>
                <div class="col-md-6">
                    <h6 class="text-muted mb-2">Role</h6>
                    <p class="mb-3">
                        <span class="badge bg-{{ $user->role === 'admin' ? 'danger' : ($user->role === 'pair' ? 'info' : 'secondary') }}">
                            {{ ucfirst($user->role) }}
                        </span>
                    </p>

                    <h6 class="text-muted mb-2">Account Status</h6>
                    <p class="mb-3">
                        <span class="badge bg-success">Active</span>
                    </p>
                </div>
            </div>

            <hr>

            <div class="row">
                <div class="col-md-6">
                    <h6 class="text-muted mb-2">Created At</h6>
                    <p class="mb-3">{{ $user->created_at->format('M d, Y H:i:s') }}</p>
                </div>
                <div class="col-md-6">
                    <h6 class="text-muted mb-2">Last Updated</h6>
                    <p class="mb-3">{{ $user->updated_at->format('M d, Y H:i:s') }}</p>
                </div>
            </div>

            <hr>

            <div class="alert alert-info" role="alert">
                <i class="bi bi-info-circle me-2"></i>
                <strong>User ID:</strong> {{ $user->id }}
            </div>

            @if($user->id !== auth()->id())
            <div class="d-flex gap-2 mt-4">
                <form method="POST" action="{{ route('users.destroy', $user) }}" style="display: inline;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="btn btn-danger" onclick="return confirm('Are you sure you want to delete this user? This action cannot be undone.')">
                        <i class="bi bi-trash me-2"></i> Delete User
                    </button>
                </form>
            </div>
            @endif
        </div>
    </div>
</div>
@endsection
