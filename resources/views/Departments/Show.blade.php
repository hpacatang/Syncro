@extends('layouts.app')

@section('page-title', $department->displayName())
@section('page-subtitle', 'Department details')

@section('content')
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1"><i class="bi bi-building me-2"></i> {{ $department->department_name }}</h2>
            @if($department->department_short_name)
                <p class="text-muted mb-0">
                    <span class="badge bg-light text-dark">{{ $department->department_short_name }}</span>
                </p>
            @endif
        </div>
        <a href="{{ route('departments.edit', $department) }}" class="btn btn-primary"><i class="bi bi-pencil me-2"></i> Edit</a>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">Information</h5>
                </div>
                <div class="card-body">
                    <h6 class="text-muted">Department name</h6>
                    <p>{{ $department->department_name }}</p>
                    
                    <h6 class="text-muted">Short name</h6>
                    <p>
                        @if($department->department_short_name)
                            <span class="badge bg-light text-dark">{{ $department->department_short_name }}</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </p>

                    <h6 class="text-muted">Created</h6>
                    <p><x-formatted-date :at="$department->created_at" /></p>

                    @if($department->updated_at !== $department->created_at)
                        <h6 class="text-muted">Last updated</h6>
                        <p><x-formatted-date :at="$department->updated_at" /></p>
                    @endif
                </div>
            </div>

            <form method="POST" action="{{ route('departments.destroy', $department) }}">
                @csrf
                @method('DELETE')
                <button type="submit" class="btn btn-danger" onclick="return confirm('Delete this department? All associated organizations will lose their department assignment.')">
                    <i class="bi bi-trash me-2"></i> Delete department
                </button>
            </form>
        </div>

        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-light">
                    <h5 class="card-title mb-0">
                        <i class="bi bi-people me-2"></i> Organizations ({{ $department->users()->count() }})
                    </h5>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Organization</th>
                                <th>Email</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($department->users()->where('role', 'org')->orderBy('profile_name')->get() as $user)
                                <tr>
                                    <td>{{ $user->displayName() }}</td>
                                    <td>{{ $user->email }}</td>
                                    <td>
                                        <a href="{{ route('users.show', $user) }}" class="btn btn-sm btn-outline-primary">View</a>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-center text-muted py-4">No organizations registered yet.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
