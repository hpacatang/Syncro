@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="mb-4">
        <h2 class="mb-0"><i class="bi bi-person me-2"></i> Edit user</h2>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('users.update', $user) }}">
                @csrf
                @method('PUT')

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="profile_name" class="form-label">Profile / display name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('profile_name') is-invalid @enderror" id="profile_name" name="profile_name" value="{{ old('profile_name', $user->profile_name) }}" required>
                        @error('profile_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="name" class="form-label">Login username <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name', $user->name) }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email', $user->email) }}" required>
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="role" class="form-label">Role <span class="text-danger">*</span></label>
                        <select class="form-select @error('role') is-invalid @enderror" id="role" name="role" required>
                            @foreach($roles as $role)
                                <option value="{{ $role }}" {{ old('role', $user->role) === $role ? 'selected' : '' }}>{{ ucfirst($role) }}</option>
                            @endforeach
                        </select>
                        @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row mb-3" id="departmentField" style="{{ old('role', $user->role) === 'org' ? '' : 'display:none' }}">
                    <div class="col-md-6">
                        <label for="department_id" class="form-label">Department <span class="text-danger">*</span></label>
                        <select class="form-select @error('department_id') is-invalid @enderror" id="department_id" name="department_id">
                            <option value="">— Select department —</option>
                            @foreach($departments as $department)
                                <option value="{{ $department->id }}" {{ (string) old('department_id', $user->department_id) === (string) $department->id ? 'selected' : '' }}>
                                    {{ $department->displayName() }}
                                </option>
                            @endforeach
                        </select>
                        @error('department_id')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <label for="password" class="form-label">New password (optional)</label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password">
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="password_confirmation" class="form-label">Confirm password</label>
                        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation">
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">Update</button>
                    <a href="{{ route('users.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
document.getElementById('role')?.addEventListener('change', function () {
    const field = document.getElementById('departmentField');
    field.style.display = this.value === 'org' ? '' : 'none';
});
</script>
@endpush
