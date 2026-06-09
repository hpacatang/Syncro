@extends('layouts.app')

@section('page-title', 'Edit department')
@section('page-subtitle', $department->department_name)

@section('content')
<div class="container-fluid py-4">
    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('departments.update', $department) }}">
                @csrf
                @method('PUT')

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="department_name" class="form-label">Department name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('department_name') is-invalid @enderror" id="department_name" name="department_name" value="{{ old('department_name', $department->department_name) }}" required>
                        @error('department_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="department_short_name" class="form-label">Short name (optional)</label>
                        <input type="text" class="form-control @error('department_short_name') is-invalid @enderror" id="department_short_name" name="department_short_name" value="{{ old('department_short_name', $department->department_short_name) }}">
                        @error('department_short_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-2"></i> Update</button>
                    <a href="{{ route('departments.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
