@props(['submission'])

@php
    $workflow = $submission->workflow_status ?? null;
@endphp

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
