@props(['submission'])

@php
    $workflow = $submission->workflow_status ?? null;
@endphp

<span data-lifecycle-badge data-submission-id="{{ $submission->id }}">
@if($workflow === 'pending_submission')
    <span class="badge bg-secondary">Submitted</span>
@elseif($workflow === 'pending_pair_review')
    <span class="badge bg-warning text-dark">Under Review</span>
@elseif($workflow === 'pending_org_approval')
    <span class="badge bg-info text-dark">Awaiting Org Approval</span>
@elseif($workflow === 'approved')
    <span class="badge bg-success">Approved</span>
@elseif($workflow === 'rejected')
    <span class="badge bg-danger">Rejected</span>
@elseif($workflow === 'posted')
    <span class="badge bg-primary">Posted</span>
@elseif($workflow === 'pending')
    <span class="badge bg-secondary">Pending</span>
@elseif($workflow === 'submitted')
    <span class="badge bg-info text-dark">Submitted</span>
@elseif($workflow === 'under_peer_review')
    <span class="badge bg-warning text-dark">Under Peer Review</span>
@elseif($workflow === 'awaiting_org_approval')
    <span class="badge bg-info text-dark">Awaiting Org Approval</span>
@elseif($workflow === 'revised')
    <span class="badge bg-warning text-dark">Revised</span>
@else
    @php
        $badgeColor = match($submission->status ?? 'pending') {
            'approved' => 'success',
            'under_review' => 'warning',
            default => 'secondary',
        };
    @endphp
    <span class="badge bg-{{ $badgeColor }}">
        {{ ucfirst(str_replace('_', ' ', $submission->status ?? 'pending')) }}
    </span>
@endif
</span>
