@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="mb-4">
        <h2 class="mb-0"><i class="bi bi-person-plus me-2"></i> Create account</h2>
        <p class="text-muted mb-0">Super admin creates department and PAIR accounts. Organizations register on their own.</p>
    </div>

    <div class="card">
        <div class="card-body">
            <form method="POST" action="{{ route('users.store') }}">
                @csrf

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="profile_name" class="form-label">Profile / display name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('profile_name') is-invalid @enderror" id="profile_name" name="profile_name" value="{{ old('profile_name') }}" placeholder="Shown in the app (e.g. College of Engineering)" required>
                        @error('profile_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="name" class="form-label">Login username <span class="text-danger">*</span></label>
                        <input type="text" class="form-control @error('name') is-invalid @enderror" id="name" name="name" value="{{ old('name') }}" required>
                        @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row mb-3">
                    <div class="col-md-6">
                        <label for="email" class="form-label">Email <span class="text-danger">*</span></label>
                        <input type="email" class="form-control @error('email') is-invalid @enderror" id="email" name="email" value="{{ old('email') }}" required>
                        @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="role" class="form-label">Account type <span class="text-danger">*</span></label>
                        <select class="form-select @error('role') is-invalid @enderror" id="role" name="role" required>
                            <option value="">— Select —</option>
                            @foreach($roles as $role)
                                <option value="{{ $role }}" {{ old('role') === $role ? 'selected' : '' }}>
                                    {{ $role === 'pair' ? 'PAIR staff' : ucfirst($role) }}
                                </option>
                            @endforeach
                        </select>
                        @error('role')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                </div>

                <div class="row mb-4">
                    <div class="col-md-6">
                        <label for="password" class="form-label">Password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control @error('password') is-invalid @enderror" id="password" name="password" required>
                        <small class="text-muted">Minimum 8 characters</small>
                        @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-md-6">
                        <label for="password_confirmation" class="form-label">Confirm password <span class="text-danger">*</span></label>
                        <input type="password" class="form-control" id="password_confirmation" name="password_confirmation" required>
                    </div>
                </div>

                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary"><i class="bi bi-check-circle me-2"></i> Create</button>
                    <a href="{{ route('users.index') }}" class="btn btn-secondary">Cancel</a>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
