@props(['submission', 'compact' => false, 'showStatusAlerts' => null])

@php
    use App\Submission\Enums\SubmissionLifecycleStatus;

    $current = $submission->lifecycle();
    $showStatusAlerts = $showStatusAlerts ?? auth()->user()?->canSubmitPosts() ?? false;
    $label = $current->label();
    
    // Style configurations for each lifecycle status badge (matching Picture 2 pill style)
    $pillColor = match ($current) {
        SubmissionLifecycleStatus::Rejected        => '#dc2626', // Red (exact match to Picture 2)
        SubmissionLifecycleStatus::Approved        => '#2563eb', // Vibrant Blue
        SubmissionLifecycleStatus::Posted          => '#7c3aed', // Purple
        SubmissionLifecycleStatus::UnderPeerReview => '#f97316', // Orange
        SubmissionLifecycleStatus::DeptApproved    => '#0891b2', // Cyan
        SubmissionLifecycleStatus::Revised         => '#eab308', // Amber/Yellow
        default                                    => '#16a34a', // Emerald Green (Submitted)
    };
@endphp

<div
    class="submission-lifecycle-status-wrap d-inline-flex flex-column align-items-center justify-content-center"
    data-lifecycle-progress
    data-submission-id="{{ $submission->id }}"
    data-workflow-status="{{ $current->value }}"
>
    @if($showStatusAlerts && ! $compact)
        @if($current === SubmissionLifecycleStatus::Rejected)
            <div class="alert alert-danger py-2 mb-2 small text-start">
                <strong>Rejected</strong> &mdash; this submission was rejected. Rejection is final and cannot be undone.
            </div>
        @elseif($current === SubmissionLifecycleStatus::Revised)
            <div class="alert alert-warning py-2 mb-2 small text-start">
                <strong>Revisions requested</strong> &mdash; the review team is updating the draft based on your feedback.
            </div>
        @elseif($current === SubmissionLifecycleStatus::UnderPeerReview)
            <div class="alert alert-under-review py-2 mb-2 small d-flex align-items-center gap-2 text-start">
                <span class="review-pulse-dot" aria-hidden="true"></span>
                <span><strong>Under Review</strong> &mdash; our team is reviewing your submission. You'll be notified once a decision is made.</span>
            </div>
        @endif
    @endif

    {{-- Single unified status badge/pill matching Picture 2 --}}
    <span
        class="badge fw-bold text-white shadow-sm {{ $compact ? 'px-3 py-1' : 'px-4 py-2 fs-6' }}"
        data-lifecycle-badge
        data-submission-id="{{ $submission->id }}"
        style="
            background-color: {{ $pillColor }};
            border-radius: 9999px;
            letter-spacing: 0.025em;
            display: inline-block;
            text-align: center;
            font-weight: 700;
        "
    >
        {{ $label }}
    </span>
</div>
