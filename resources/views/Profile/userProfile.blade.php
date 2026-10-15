@extends('layouts.app')

@section('page-title', 'My profile')
@section('page-subtitle', 'Manage your personal details and sign-in credentials')

@section('content')
<div class="container-fluid px-0 py-1">
    @if(session('success'))
        <div class="alert alert-success" role="status">{{ session('success') }}</div>
    @endif
    <div class="row g-4">
        <div class="col-lg-4">
            <section class="card h-100">
                <div class="card-body text-center p-4">
                    @if($user->profilePhotoUrl())
                        <img src="{{ $user->profilePhotoUrl() }}" alt="{{ $user->displayName() }} profile photo" class="profile-photo-preview mb-3">
                    @else
                        <div class="profile-photo-placeholder mb-3" aria-hidden="true">
                            {{ strtoupper(substr($user->displayName(), 0, 1)) }}
                        </div>
                    @endif
                    <h2 class="h5 mb-1">{{ $user->displayName() }}</h2>
                    <p class="text-muted mb-3">{{ $user->roleLabel() }}</p>
                    <div class="small text-start border-top pt-3">
                        <div class="d-flex justify-content-between gap-3 mb-2">
                            <span class="text-muted">Username</span><span>{{ $user->name }}</span>
                        </div>
                        <div class="d-flex justify-content-between gap-3">
                            <span class="text-muted">School / department</span>
                            <span class="text-end">{{ $user->department?->displayName() ?? '—' }}</span>
                        </div>
                    </div>
                </div>
            </section>
        </div>
        <div class="col-lg-8">
            <section class="card">
                <div class="card-header bg-white py-3">
                    <h2 class="h5 mb-0">Personal details</h2>
                </div>
                <div class="card-body p-4">
                    <form method="POST" action="{{ route('profile.update') }}" enctype="multipart/form-data">
                        @csrf
                        @method('PUT')

                        <div class="mb-3">
                            <label for="profile_name" class="form-label">Display name</label>
                            <input id="profile_name" name="profile_name" class="form-control @error('profile_name') is-invalid @enderror" value="{{ old('profile_name', $user->profile_name) }}" required>
                            @error('profile_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>

                        <div class="row g-3 mb-3">
                            <div class="col-md-6">
                                <label for="name" class="form-label">Username</label>
                                <input id="name" name="name" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required>
                                @error('name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-6">
                                <label for="email" class="form-label">Email address</label>
                                <input id="email" type="email" name="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required>
                                @error('email')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                        </div>

                        <div class="mb-4">
                            <label for="profile_photo" class="form-label">Profile photo</label>
                            <input id="profile_photo" type="file" name="profile_photo" accept="image/*" class="form-control @error('profile_photo') is-invalid @enderror">
                            <div class="form-text">Choose a JPG, PNG, GIF, or WebP image up to 2 MB.</div>
                            @error('profile_photo')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            @if($user->profile_photo_path)
                                <div class="form-check mt-2">
                                    <input class="form-check-input" type="checkbox" id="remove_profile_photo" name="remove_profile_photo" value="1">
                                    <label class="form-check-label" for="remove_profile_photo">Remove current photo</label>
                                </div>
                            @endif
                        </div>

                        <hr class="my-4">
                        <h3 class="h6 mb-3">Change password <span class="text-muted fw-normal">(optional)</span></h3>
                        <div class="row g-3 mb-4">
                            <div class="col-md-4">
                                <label for="current_password" class="form-label">Current password</label>
                                <input id="current_password" type="password" name="current_password" autocomplete="current-password" class="form-control @error('current_password') is-invalid @enderror">
                                @error('current_password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="password" class="form-label">New password</label>
                                <input id="password" type="password" name="password" autocomplete="new-password" class="form-control @error('password') is-invalid @enderror">
                                @error('password')<div class="invalid-feedback">{{ $message }}</div>@enderror
                            </div>
                            <div class="col-md-4">
                                <label for="password_confirmation" class="form-label">Confirm new password</label>
                                <input id="password_confirmation" type="password" name="password_confirmation" autocomplete="new-password" class="form-control">
                            </div>
                        </div>

                        <button type="submit" class="btn btn-primary"><i class="bi bi-check-lg me-1"></i> Save changes</button>
                    </form>
                </div>
            </section>
        </div>
    </div>
</div>
@endsection
