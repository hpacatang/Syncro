<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Syncro - Register</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        html, body {
            height: 100%;
            background: linear-gradient(135deg, #2d582e 0%, #1a3520 100%);
        }

        .auth-container {
            display: flex;
            height: 100vh;
        }

        /* Form Section */
        .form-section {
            flex: 0 0 28%;
            display: flex;
            align-items: center;
            justify-content: center;
            padding: 2rem;
            background: #ffffff;
            overflow-y: auto;
        }

        .form-wrap {
            width: 100%;
            max-width: 380px;
        }

        .brand-header {
            text-align: center;
            margin-bottom: 1.5rem;
        }

        .brand-header h1 {
            font-size: 2rem;
            font-weight: 800;
            color: #2d582e;
            margin-bottom: 0.25rem;
        }

        .brand-header p {
            color: #efcb58;
            font-size: 0.95rem;
            font-weight: 600;
            letter-spacing: 1px;
        }

        .brand-header .subtitle {
            color: #6c757d;
            font-size: 0.85rem;
            margin-top: 0.5rem;
        }

        .form-group {
            margin-bottom: 1rem;
        }

        .form-label {
            color: #2d582e;
            font-weight: 600;
            font-size: 0.95rem;
            margin-bottom: 0.5rem;
            display: block;
        }

        .form-control {
            border: 2px solid #e9ecef;
            padding: 0.75rem 1rem;
            font-size: 0.95rem;
            transition: all 0.3s ease;
            background-color: #f8f9fa;
        }

        .form-control:focus {
            background-color: #ffffff;
            border-color: #2d582e;
            box-shadow: 0 0 0 3px rgba(45, 88, 46, 0.1);
            color: #2d582e;
        }

        .form-control.is-invalid {
            border-color: #dc3545;
            background-color: #fff5f5;
        }

        .btn-register {
            background: linear-gradient(135deg, #2d582e 0%, #1a3520 100%);
            border: none;
            padding: 0.85rem 1.5rem;
            font-weight: 600;
            font-size: 0.95rem;
            color: #ffffff;
            transition: all 0.3s ease;
            margin-top: 0.75rem;
        }

        .btn-register:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 16px rgba(45, 88, 46, 0.3);
            color: #ffffff;
        }

        .btn-register:active {
            transform: translateY(0);
        }

        .form-divider {
            text-align: center;
            margin: 1.25rem 0;
            position: relative;
        }

        .form-divider::before {
            content: '';
            position: absolute;
            top: 50%;
            left: 0;
            right: 0;
            height: 1px;
            background: #e9ecef;
        }

        .form-divider span {
            position: relative;
            background: #ffffff;
            padding: 0 0.75rem;
            color: #6c757d;
            font-size: 0.85rem;
        }

        .auth-link {
            text-align: center;
            margin-top: 1rem;
            font-size: 0.9rem;
        }

        .auth-link a {
            color: #efcb58;
            text-decoration: none;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .auth-link a:hover {
            color: #2d582e;
            text-decoration: underline;
        }

        .error-message {
            color: #dc3545;
            font-size: 0.85rem;
            margin-top: 0.3rem;
            display: flex;
            align-items: center;
            gap: 0.4rem;
        }

        .password-requirements {
            background: #f8f9fa;
            border-left: 3px solid #efcb58;
            padding: 0.75rem;
            border-radius: 0.25rem;
            font-size: 0.85rem;
            color: #495057;
            margin-top: 0.75rem;
            display: none;
        }

        .password-requirements.show {
            display: block;
        }

        /* Image Section */
        .image-section {
            flex: 0 0 72%;
            background: linear-gradient(135deg, #2d582e 0%, #1a3520 100%);
            position: relative;
            overflow: hidden;
            display: none;
        }

        @media (min-width: 992px) {
            .image-section {
                display: flex;
                align-items: center;
                justify-content: center;
            }
        }

        .image-section img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }

        .image-overlay {
            position: absolute;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background: linear-gradient(135deg, rgba(45, 88, 46, 0.25) 0%, rgba(26, 53, 32, 0.35) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            flex-direction: column;
            color: #ffffff;
            text-align: center;
            padding: 3rem;
        }

        .image-overlay h2 {
            font-size: 2.5rem;
            font-weight: 800;
            margin-bottom: 1rem;
            text-shadow: 3px 3px 8px rgba(0, 0, 0, 0.5);
            color: #ffffff;
        }

        .image-overlay p {
            font-size: 1.1rem;
            max-width: 450px;
            color: #efcb58;
            font-weight: 600;
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.5);
        }

        /* Responsive */
        @media (max-width: 991px) {
            .auth-container {
                flex-direction: column;
            }

            .form-section {
                flex: 0 0 100%;
                max-height: 100vh;
            }

            .image-section {
                flex: 0 0 100%;
                height: 300px;
            }

            .form-wrap {
                max-width: 360px;
            }
        }
    </style>
</head>
<body>
<div class="auth-container">
    <!-- Form Section -->
    <div class="form-section">
        <div class="form-wrap">
            <!-- Brand Header -->
            <div class="brand-header">
                <h1><i class="fas fa-sync"></i> Syncro</h1>
                <p class="subtitle">Create your account</p>
            </div>

            <!-- Register Form -->
            <form method="POST" action="{{ route('store') }}" class="auth-form">
                @csrf

                <!-- Username -->
                <div class="form-group">
                    <label for="name" class="form-label">
                        <i class="fas fa-user"></i> Username
                    </label>
                    <input 
                        id="name" 
                        type="text" 
                        class="form-control @error('name') is-invalid @enderror" 
                        name="name" 
                        value="{{ old('name') }}" 
                        placeholder="Choose a username"
                        required 
                        autofocus>
                    @error('name')
                        <div class="error-message">
                            <i class="fas fa-exclamation-circle"></i> {{ $message }}
                        </div>
                    @enderror
                </div>

                <!-- Password -->
                <div class="form-group">
                    <label for="password" class="form-label">
                        <i class="fas fa-lock"></i> Password
                    </label>
                    <input 
                        id="password" 
                        type="password" 
                        class="form-control @error('password') is-invalid @enderror" 
                        name="password" 
                        placeholder="Enter a strong password"
                        onfocus="document.querySelector('.password-requirements').classList.add('show')"
                        onblur="document.querySelector('.password-requirements').classList.remove('show')"
                        required>
                    <div class="password-requirements">
                        <i class="fas fa-info-circle"></i> At least 6 characters
                    </div>
                    @error('password')
                        <div class="error-message">
                            <i class="fas fa-exclamation-circle"></i> {{ $message }}
                        </div>
                    @enderror
                </div>

                <!-- Confirm Password -->
                <div class="form-group">
                    <label for="password2" class="form-label">
                        <i class="fas fa-check-circle"></i> Confirm Password
                    </label>
                    <input 
                        id="password2" 
                        type="password" 
                        class="form-control @error('password2') is-invalid @enderror" 
                        name="password2" 
                        placeholder="Confirm your password"
                        required>
                    @error('password2')
                        <div class="error-message">
                            <i class="fas fa-exclamation-circle"></i> {{ $message }}
                        </div>
                    @enderror
                </div>

                <!-- Submit Button -->
                <button type="submit" class="btn btn-register w-100">
                    <i class="fas fa-user-plus"></i> Create Account
                </button>
            </form>

            <!-- Divider -->
            <div class="form-divider">
                <span>Already registered?</span>
            </div>

            <!-- Login Link -->
            <div class="auth-link">
                <a href="{{ route('login') }}">
                    <i class="fas fa-sign-in-alt"></i> Sign in to your account
                </a>
            </div>
        </div>
    </div>

    <!-- Image Section -->
    <div class="image-section">
        <img src="{{ asset('storage/img/school-banner.jpg') }}" alt="USJR Campus">
        
    </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
