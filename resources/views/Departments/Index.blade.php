@extends('layouts.app')

@section('page-title', 'Department management')
@section('page-subtitle', 'Create and manage departments')

@section('content')
<div class="container-fluid px-0 py-1">
    <div class="d-flex justify-content-end mb-3">
        <a href="{{ route('departments.create') }}" class="btn btn-primary">
            <i class="bi bi-building me-1"></i> New department
        </a>
    </div>

    <div class="card mb-4">
        <div class="card-body">
            <form method="GET" class="row g-3">
                <div class="col-md-8">
                    <label for="search" class="form-label">Search</label>
                    <input type="text" name="search" id="search" class="form-control" placeholder="Department name or short name" value="{{ request('search') }}">
                </div>
                <div class="col-md-4 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary w-100">Search</button>
                    <a href="{{ route('departments.index') }}" class="btn btn-secondary w-100">Reset</a>
                </div>
            </form>
        </div>
    </div>

    <div class="card">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead class="table-light">
                    <tr>
                        <th>Department name</th>
                        <th>Short name</th>
                        <th>Organizations</th>
                        <th>Created</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($departments as $department)
                        <tr>
                            <td>{{ $department->department_name }}</td>
                            <td>
                                @if($department->department_short_name)
                                    <span class="badge bg-warning text-dark">{{ $department->department_short_name }}</span>
                                @else
                                    —
                                @endif
                            </td>
                            <td>{{ $department->users()->count() }}</td>
                            <td><small><x-formatted-date :at="$department->created_at" /></small></td>
                            <td>
                                <a href="{{ route('departments.show', $department) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-eye me-1"></i> View</a>
                                <a href="{{ route('departments.edit', $department) }}" class="btn btn-sm btn-outline-secondary"><i class="bi bi-pencil me-1"></i> Edit</a>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="text-center text-muted py-4">No departments found.</td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($departments->hasPages())
            <div class="card-footer">{{ $departments->links() }}</div>
        @endif
    </div>
</div>
@endsection
