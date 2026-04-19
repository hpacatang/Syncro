@extends('layouts.app')

@section('content')
<div class="container">
    <div class="row justify-content-center mt-5">
        <div class="col-md-8">
            <div class="card shadow-sm border-0">
                <div class="card-body text-center py-5">
                    <i class="fas fa-lock text-danger" style="font-size: 4rem;"></i>
                    <h2 class="mt-3">Access Denied</h2>
                    <p class="text-muted mt-2">You do not have permission to access this page.</p>
                    
                    @if(auth()->check())
                        <p class="text-sm text-muted">Your current role: <strong>{{ auth()->user()->role }}</strong></p>
                        <div class="mt-4">
                            @if(auth()->user()->role === 'org')
                                <a href="{{ route('org.dashboard') }}" class="btn btn-primary">
                                    <i class="fas fa-arrow-left"></i> Go to Your Dashboard
                                </a>
                            @else
                                <a href="{{ route('dashboard') }}" class="btn btn-primary">
                                    <i class="fas fa-arrow-left"></i> Go to Dashboard
                                </a>
                            @endif
                        </div>
                    @endif
                    
                    <div class="mt-3">
                        <form method="POST" action="{{ route('logout') }}" style="display: inline;">
                            @csrf
                            <button type="submit" class="btn btn-outline-secondary">
                                <i class="fas fa-sign-out-alt"></i> Logout
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
