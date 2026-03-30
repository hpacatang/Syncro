@extends('layouts.app')

@section('content')
<div class="container mt-4">
    <h2 class="mb-4"><i class="bi bi-bell me-2"></i> Notifications</h2>

    <div class="row">
        <div class="col-md-8 mx-auto">
            @forelse($notifications as $notification)
                <div class="card mb-3 shadow-sm border-0 border-start border-4 border-primary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center mb-2">
                            <h5 class="card-title text-primary mb-0">
                                <i class="bi bi-info-circle me-1"></i> Notification Alert
                            </h5>
                            <small class="text-muted">{{ $notification->created_at->diffForHumans() }}</small>
                        </div>
                        <p class="card-text text-secondary mb-0">
                            {{ $notification->data['message'] ?? 'You have a new system alert regarding a submission.' }}
                        </p>
                    </div>
                </div>
            @empty
                <div class="card shadow-sm border-0 bg-light text-center py-5">
                    <div class="card-body">
                        <i class="bi bi-bell-slash text-muted" style="font-size: 3rem;"></i>
                        <h5 class="text-muted mt-3">You're all caught up!</h5>
                        <p class="text-muted">No new notifications right now.</p>
                    </div>
                </div>
            @endforelse
        </div>
    </div>
</div>
@endsection
