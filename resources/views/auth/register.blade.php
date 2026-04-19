<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Register</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        html, body { height: 100%; }
        .auth-image { background:#f6f7fb; background-size:cover; background-position:center; color:#6c757d; }
        .auth-card { border:0; }
        .form-wrap { max-width:360px; width:100%; }
    </style>
</head>
<body>
<div class="container-fluid">
    <div class="row g-0 min-vh-100">
        <div class="col-12 col-lg-3 d-flex align-items-center justify-content-center p-4">
            <div class="form-wrap">
                <div class="card shadow-sm auth-card w-100">
                    <div class="card-body p-4">
                        <h4 class="mb-3">Create an account</h4>
                        <form method="POST" action="{{ route('store') }}">
                            @csrf
                            <div class="mb-3">
                                <label for="name" class="form-label">Username</label>
                                <input id="name" type="text" class="form-control @error('name') is-invalid @enderror" name="name" value="{{ old('name') }}" required autofocus>
                                @error('name')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input id="password" type="password" class="form-control @error('password') is-invalid @enderror" name="password" required>
                                @error('password')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="mb-3">
                                <label for="password2" class="form-label">Confirm Password</label>
                                <input id="password2" type="password" class="form-control @error('password2') is-invalid @enderror" name="password2" required>
                                @error('password2')
                                    <div class="text-danger small">{{ $message }}</div>
                                @enderror
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Register</button>
                            <div class="mt-3 text-center">
                                <a href="{{ route('login') }}">Already have an account? Login</a>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-9 d-none d-lg-flex auth-image" style="align-items:center; justify-content:center;">
            <!-- full-height image placeholder; replace background-image via inline style or CSS -->
            <div class="text-center w-100">Image placeholder</div>
        </div>
    </div>
</div>
</body>
</html>
