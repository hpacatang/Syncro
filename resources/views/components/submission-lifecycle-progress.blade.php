@props(['submission', 'compact' => false, 'showStatusAlerts' => null])

@php
    use App\Submission\Enums\SubmissionLifecycleStatus;

    $current = $submission->lifecycle();
    $steps = SubmissionLifecycleStatus::progressSteps();
    $currentIndex = $current->progressIndex();
    $isRejected       = $current === SubmissionLifecycleStatus::Rejected;
    $isRevised        = $current === SubmissionLifecycleStatus::Revised;
    $isUnderReview    = $current === SubmissionLifecycleStatus::UnderPeerReview;
    $activeTheme      = $isRejected ? 'rejected' : ($isRevised ? 'revised' : $current->progressTheme());
    $showStatusAlerts = $showStatusAlerts ?? auth()->user()?->canSubmitPosts() ?? false;
@endphp

<div
    class="submission-lifecycle-progress {{ $compact ? 'submission-lifecycle-progress--compact' : '' }}"
    data-lifecycle-progress
    data-submission-id="{{ $submission->id }}"
    data-workflow-status="{{ $current->value }}"
    data-progress-index="{{ $currentIndex }}"
    data-active-theme="{{ $activeTheme }}"
>
    @if($showStatusAlerts && ! $compact)
        @if($isRejected)
            <div class="alert alert-danger py-2 mb-2 small">
                <strong>Rejected</strong> &mdash; this submission was rejected. Rejection is final and cannot be undone.
            </div>
        @elseif($isRevised)
            <div class="alert alert-warning py-2 mb-2 small">
                <strong>Revisions requested</strong> &mdash; the review team is updating the draft based on your feedback.
            </div>
        @elseif($isUnderReview)
            <div class="alert alert-under-review py-2 mb-2 small d-flex align-items-center gap-2">
                <span class="review-pulse-dot" aria-hidden="true"></span>
                <span><strong>Under Review</strong> &mdash; our team is reviewing your submission. You'll be notified once a decision is made.</span>
            </div>
        @endif
    @endif

    @if($isRejected)
        <div class="lifecycle-progress__rejected d-flex flex-column align-items-center justify-content-center {{ $compact ? 'py-1' : 'py-3' }} text-center">
            <span class="badge bg-danger {{ $compact ? 'px-2 py-1' : 'fs-6 px-3 py-2 mb-2' }}">
                <i class="bi bi-x-circle me-1"></i> Rejected
            </span>
            @if(! $compact)
                <p class="text-danger small mb-0 fw-semibold">
                    This submission was rejected and will not proceed.
                </p>
            @endif
        </div>
    @else
        <div class="lifecycle-progress__track">
            @foreach($steps as $index => $step)
                @php
                    $theme = $step->progressTheme();
                    $done = $currentIndex > $index;
                    $active = $currentIndex === $index;
                    $stateClass = $active ? 'is-active' : ($done ? 'is-done' : 'is-upcoming');
                    $accent = $active ? $current : $step;
                @endphp
                <div
                    class="lifecycle-step lifecycle-step--{{ $theme }} {{ $stateClass }}"
                    data-step="{{ $step->value }}"
                    style="--step-color: {{ $accent->progressColor() }}; --step-glow-rgb: {{ $accent->progressGlowRgb() }};"
                >
                    <div class="lifecycle-step__dot"></div>
                    <small class="lifecycle-step__label">{{ $step->label() }}</small>
                </div>
                @if(! $loop->last)
                    <span class="lifecycle-progress__connector d-none d-md-inline" aria-hidden="true">›</span>
                @endif
            @endforeach
        </div>

        @if(! $compact)
            <div class="lifecycle-progress__badge-wrap">
                <x-submission-workflow-badge :submission="$submission" size="lg" />
            </div>
        @endif
    @endif
</div>
