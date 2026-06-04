@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2><i class="bi bi-person me-2"></i> {{ $user->displayName() }}</h2>
        <a href="{{ route('users.edit', $user) }}" class="btn btn-primary"><i class="bi bi-pencil me-2"></i> Edit</a>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <h6 class="text-muted">Profile name</h6>
                    <p>{{ $user->displayName() }}</p>
                    <h6 class="text-muted">Login username</h6>
                    <p><code>{{ $user->name }}</code></p>
                    <h6 class="text-muted">Email</h6>
                    <p>{{ $user->email }}</p>
                </div>
                <div class="col-md-6">
                    <h6 class="text-muted">Role</h6>
                    <p><span class="badge bg-secondary">{{ $user->roleLabel() }}</span></p>
                    @if($user->department)
                        <h6 class="text-muted">Department</h6>
                        <p>{{ $user->department->displayName() }}</p>
                    @endif
                    @if($user->role === 'department' && $user->organizations->isNotEmpty())
                        <h6 class="text-muted">Registered organizations</h6>
                        <ul class="mb-0">
                            @foreach($user->organizations as $org)
                                <li>{{ $org->displayName() }}</li>
                            @endforeach
                        </ul>
                    @endif
                </div>
            </div>

            @if($user->id !== auth()->id())
            <form method="POST" action="{{ route('users.destroy', $user) }}" class="mt-3">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger" onclick="return confirm('Delete this user?')">Delete</button>
            </form>
            @endif
        </div>
    </div>
</div>
@endsection
