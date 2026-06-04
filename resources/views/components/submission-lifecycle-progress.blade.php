@props(['submission', 'compact' => false])

@php
    use App\Submission\Enums\SubmissionLifecycleStatus;

    $current = $submission->lifecycle();
    $steps = SubmissionLifecycleStatus::progressSteps();
    $currentIndex = $current->progressIndex();
    $isRejected       = $current === SubmissionLifecycleStatus::Rejected;
    $isRevised        = $current === SubmissionLifecycleStatus::Revised;
    $isUnderReview    = $current === SubmissionLifecycleStatus::UnderPeerReview;
    $activeTheme      = $isRejected ? 'rejected' : ($isRevised ? 'revised' : $current->progressTheme());
@endphp

<div
    class="submission-lifecycle-progress {{ $compact ? 'submission-lifecycle-progress--compact' : '' }}"
    data-lifecycle-progress
    data-submission-id="{{ $submission->id }}"
    data-workflow-status="{{ $current->value }}"
    data-progress-index="{{ $currentIndex }}"
    data-active-theme="{{ $activeTheme }}"
>
    @if($isRejected && ! $compact)
        <div class="alert alert-danger py-2 mb-2 small">
            <strong>Rejected</strong> — this submission will not proceed unless staff reopens it.
        </div>
    @elseif($isRevised && ! $compact)
        <div class="alert alert-warning py-2 mb-2 small">
            <strong>Revisions requested</strong> — PAIR is updating the caption based on your feedback.
        </div>
    @elseif($isUnderReview && ! $compact)
        <div class="alert alert-under-review py-2 mb-2 small d-flex align-items-center gap-2">
            <span class="review-pulse-dot" aria-hidden="true"></span>
            <span><strong>Under PAIR Review</strong> — our team is reviewing your submission. You'll be notified once a decision is made.</span>
        </div>
    @endif

    <div class="lifecycle-progress__track">
        @foreach($steps as $index => $step)
            @php
                $theme = $step->progressTheme();
                $done = ! $isRejected && $currentIndex > $index;
                $active = ! $isRejected && (
                    ($isRevised && $theme === 'under_peer_review')
                    || ($currentIndex === $index && ! $isRevised)
                );
                $stateClass = $active ? 'is-active' : ($done ? 'is-done' : 'is-upcoming');
            @endphp
            <div class="lifecycle-step lifecycle-step--{{ $theme }} {{ $stateClass }}" data-step="{{ $step->value }}">
                <div class="lifecycle-step__dot" style="--step-color: {{ $step->progressColor() }}"></div>
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
</div>
