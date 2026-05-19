@extends('layouts.app')

@section('content')
<div class="container-fluid py-4">
    <div class="mb-4">
        <h2 class="mb-0"><i class="bi bi-clipboard-data me-2"></i> Audit Log Details</h2>
    </div>

    <div class="card">
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6">
                    <h6 class="text-muted mb-2">Timestamp</h6>
                    <p class="mb-3">{{ $auditLog->created_at->format('M d, Y H:i:s') }}</p>

                    <h6 class="text-muted mb-2">User</h6>
                    <p class="mb-3">
                        @if($auditLog->user)
                            <span class="badge bg-info">{{ $auditLog->user->name }}</span>
                        @else
                            <span class="text-muted">System</span>
                        @endif
                    </p>

                    <h6 class="text-muted mb-2">Action Type</h6>
                    <p class="mb-3">
                        <span class="badge bg-primary">{{ ucfirst(str_replace('_', ' ', $auditLog->action_type)) }}</span>
                    </p>
                </div>
                <div class="col-md-6">
                    <h6 class="text-muted mb-2">IP Address</h6>
                    <p class="mb-3"><code>{{ $auditLog->ip_address ?? '-' }}</code></p>

                    <h6 class="text-muted mb-2">Model</h6>
                    <p class="mb-3">
                        @if($auditLog->model_type)
                            <code>{{ $auditLog->model_type }} (ID: {{ $auditLog->model_id }})</code>
                        @else
                            <span class="text-muted">-</span>
                        @endif
                    </p>
                </div>
            </div>

            <hr>

            <h6 class="text-muted mb-2">Description</h6>
            <p class="mb-3">{{ $auditLog->description ?? 'No description' }}</p>

            @if($auditLog->user_agent)
                <h6 class="text-muted mb-2">User Agent</h6>
                <p class="mb-3"><small><code>{{ $auditLog->user_agent }}</code></small></p>
            @endif

            @if($auditLog->changes)
                <h6 class="text-muted mb-2">Changes</h6>
                <div class="bg-light p-3 rounded mb-3">
                    <pre class="mb-0"><code>{{ json_encode($auditLog->changes, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES) }}</code></pre>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection
