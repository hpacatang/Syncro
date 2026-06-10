@extends('layouts.app')

@section('page-title', 'Create department')
@section('page-subtitle', 'Add a new department')

@section('content')
<div class="container-fluid py-4">
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('departments.store') }}">
                @csrf

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="department_name" class="form-label">Department name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('department_name') is-invalid @enderror" id="department_name" name="department_name" value="{{ old('department_name') }}" placeholder="e.g., School of Computer Studies" required>
                        @error('department_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="department_short_name" class="form-label">Short name (optional)</label>
                        <input type="text" class="form-control @error('department_short_name') is-invalid @enderror" id="department_short_name" name="department_short_name" value="{{ old('department_short_name') }}" placeholder="e.g., SCS">
                        @error('department_short_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-2"></i> Create</button>
                    <a href="{{ route('departments.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>  
</div>
@endsection
