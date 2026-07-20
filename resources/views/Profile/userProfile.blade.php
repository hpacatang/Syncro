@extends('layouts.app')

@section('content')
<div class="container">
    <div class="mb-4">
        <h2 class="mb-0"><i class="bi bi-person-circle me-2"></i> {{ $user->displayName() }}</h2>
        <p class="text-muted mb-0">User profile and account settings</p>
    </div>

    <div>
        
    </div>
</div>
@endsection
