@props(['submission'])

@php
    $isOwnerOrg = auth()->user()->canSubmitPosts() && (int) $submission->user_id === (int) auth()->id();
    $isStaff = auth()->user()->isAdmin() || auth()->user()->isPair();
@endphp

<div {{ $attributes->merge(['class' => 'card syncro-card-elevated border-0']) }}>
    <div class="card-header bg-white border-bottom">
        <h5 class="mb-0 fw-bold">Actions</h5>
    </div>
    <div class="card-body">
        @if($isOwnerOrg)
            <p class="text-muted mb-0">No actions for this status. Current step: <strong>{{ str_replace('_', ' ', $submission->workflow_status) }}</strong>.</p>
        @elseif($isStaff)
            <p class="text-muted small mb-3">Open the Enhance Caption workspace for this submission, or return to the queue.</p>
            <a href="{{ route('dashboard', ['enhance' => $submission->id]) }}" class="btn btn-primary btn-sm me-2">
                <i class="bi bi-magic me-1"></i> Enhance caption
            </a>
            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm me-2">Back to queue</a>
            <a href="{{ route('staff.caption-assist') }}" class="btn btn-outline-secondary btn-sm">AI caption from media</a>
        @endif
    </div>
</div>
