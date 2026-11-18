@props(['submission', 'compact' => false, 'showStatusAlerts' => null])

@php
    use App\Submission\Enums\SubmissionLifecycleStatus;

    if (! $submission) {
        return;
    }

    $current = $submission->lifecycle();
    $wf = $submission->workflow_status ?? 'submitted';
    $showStatusAlerts = $showStatusAlerts ?? auth()->user()?->canSubmitPosts() ?? false;
    $label = $current->label();

    $pillColor = match ($current) {
        SubmissionLifecycleStatus::Rejected        => '#dc2626',
        SubmissionLifecycleStatus::Approved        => '#2563eb',
        SubmissionLifecycleStatus::Posted          => '#7c3aed',
        SubmissionLifecycleStatus::UnderPeerReview => '#f97316',
        SubmissionLifecycleStatus::DeptApproved    => '#0891b2',
        SubmissionLifecycleStatus::Revised         => '#eab308',
        default                                    => '#16a34a',
    };

    // Helper to extract action timestamp from pair_feedback if present
    $feedbackText = (string) $submission->pair_feedback;
    $findFeedbackTime = function (array $keywords) use ($feedbackText) {
        foreach ($keywords as $kw) {
            if (preg_match('/\[Review team[^\•\]]*\•\s*([^\]]+)\]\s*\n[^\n]*' . preg_quote($kw, '/') . '/i', $feedbackText, $m)) {
                try {
                    return \Carbon\Carbon::parse(trim($m[1]));
                } catch (\Throwable $e) {}
            }
        }
        return null;
    };

    // Step 1: Submitted
    $submittedAt = $submission->created_at;
    $submittedDate = $submittedAt?->format('M d, Y');
    $submittedTime = $submittedAt?->format('h:i A');

    // Step 2: Under Review
    $isUnderReviewReached = !in_array($wf, ['submitted', 'pending'], true);
    $reviewAt = $findFeedbackTime(['Under review', 'review', 'saved', 'draft'])
        ?? $submission->enhanced_at
        ?? $submission->updated_at
        ?? $submission->created_at;
    $reviewDate = $reviewAt?->format('M d, Y') ?? 'Oct 30, 2026';
    $reviewTime = $reviewAt?->format('h:i A') ?? '10:51 AM';

    // Step 3: Approved
    $isApprovedReached = in_array($wf, ['approved', 'posted'], true);
    $approvedAt = $findFeedbackTime(['Approve', 'approved'])
        ?? $submission->updated_at
        ?? $submission->created_at;
    $approvedDate = $approvedAt?->format('M d, Y') ?? 'Oct 30, 2026';
    $approvedTime = $approvedAt?->format('h:i A') ?? '10:51 AM';

    // Step 4: Posted
    $isPostedReached = ($wf === 'posted');
    $isApprovedActive = ($wf === 'approved');
    $postedAt = $submission->posted_at
        ?? $findFeedbackTime(['Mark as posted', 'posted', 'publish'])
        ?? $submission->updated_at;
    $postedDate = $postedAt?->format('M d, Y') ?? 'Oct 30, 2026';
    $postedTime = $postedAt?->format('h:i A') ?? '10:51 AM';

    $isRejected = ($wf === 'rejected');
    $isRevised  = ($wf === 'revised');
@endphp

<div
    class="submission-lifecycle-progress-wrap w-100"
    data-lifecycle-progress
    data-submission-id="{{ $submission->id }}"
    data-workflow-status="{{ $wf }}"
>
    @if($showStatusAlerts && ! $compact)
        @if($isRejected)
            <div class="alert alert-danger py-2 mb-3 small text-start">
                <strong>Rejected</strong> &mdash; this submission was rejected. Rejection is final and cannot be undone.
            </div>
        @elseif($isRevised)
            <div class="alert alert-warning py-2 mb-3 small text-start">
                <strong>Revisions requested</strong> &mdash; the review team is updating the draft based on your feedback.
            </div>
        @elseif($wf === 'under_peer_review')
            <div class="alert alert-under-review py-2 mb-3 small d-flex align-items-center gap-2 text-start">
                <span class="review-pulse-dot" aria-hidden="true"></span>
                <span><strong>Under Review</strong> &mdash; our team is reviewing your submission. You'll be notified once a decision is made.</span>
            </div>
        @endif
    @endif

    @if($compact)
        {{-- Single unified status badge/pill for compact table cells --}}
        <span
            class="badge fw-bold text-white shadow-sm px-3 py-1"
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
    @else
        {{-- Full Horizontal Stepper Timeline (matching PAIR exactly) --}}
        <div class="syncro-stepper-timeline">
            <!-- Connecting Green Line -->
            <div class="syncro-stepper-line" aria-hidden="true"></div>

            <!-- Stepper Nodes Grid -->
            <div class="d-flex justify-content-between align-items-start position-relative syncro-stepper-steps">

                <!-- Step 1: Submitted -->
                <div class="syncro-stepper-item">
                    <div class="syncro-stepper-circle" style="background-color: #16a34a !important; color: #ffffff;">
                        <i class="bi bi-check text-white fw-bold" style="font-size: 1.25rem; line-height: 1;"></i>
                    </div>
                    <div class="syncro-stepper-title">Submitted</div>
                    <div class="syncro-stepper-subtitle text-muted fst-italic">
                        Pending
                    </div>
                </div>

                <!-- Step 2: Under Review -->
                <div class="syncro-stepper-item">
                    @if($isRejected)
                        <div class="syncro-stepper-circle" style="background-color: #dc2626 !important; color: #ffffff;">
                            <i class="bi bi-x text-white fw-bold" style="font-size: 1.25rem; line-height: 1;"></i>
                        </div>
                        <div class="syncro-stepper-title text-danger">Rejected</div>
                        <div class="syncro-stepper-subtitle">
                            <div>{{ $reviewDate }}</div>
                            <div><i class="bi bi-clock me-1"></i>{{ $reviewTime }}</div>
                        </div>
                    @elseif($isRevised)
                        <div class="syncro-stepper-circle" style="background-color: #eab308 !important; color: #ffffff;">
                            <i class="bi bi-arrow-repeat text-white fw-bold" style="font-size: 1rem; line-height: 1;"></i>
                        </div>
                        <div class="syncro-stepper-title" style="color: #d97706;">Revised</div>
                        <div class="syncro-stepper-subtitle">
                            <div>{{ $reviewDate }}</div>
                            <div><i class="bi bi-clock me-1"></i>{{ $reviewTime }}</div>
                        </div>
                    @elseif($isUnderReviewReached)
                        <div class="syncro-stepper-circle" style="background-color: #ea580c !important; color: #ffffff;">
                            <i class="bi bi-check text-white fw-bold" style="font-size: 1.25rem; line-height: 1;"></i>
                        </div>
                        <div class="syncro-stepper-title">Under Review</div>
                        <div class="syncro-stepper-subtitle">
                            <div>{{ $reviewDate }}</div>
                            <div><i class="bi bi-clock me-1"></i>{{ $reviewTime }}</div>
                        </div>
                    @else
                        <div class="syncro-stepper-circle syncro-stepper-circle--upcoming">
                            <span class="syncro-stepper-dot"></span>
                        </div>
                        <div class="syncro-stepper-title">Under Review</div>
                        <div class="syncro-stepper-subtitle text-muted fst-italic">Pending</div>
                    @endif
                </div>

                <!-- Step 3: Approved -->
                <div class="syncro-stepper-item">
                    @if($isApprovedReached)
                        <div class="syncro-stepper-circle" style="background-color: #2563eb !important; color: #ffffff;">
                            <i class="bi bi-check text-white fw-bold" style="font-size: 1.25rem; line-height: 1;"></i>
                        </div>
                        <div class="syncro-stepper-title">Approved</div>
                        <div class="syncro-stepper-subtitle">
                            <div>{{ $approvedDate }}</div>
                            <div><i class="bi bi-clock me-1"></i>{{ $approvedTime }}</div>
                        </div>
                    @else
                        <div class="syncro-stepper-circle syncro-stepper-circle--upcoming">
                            <span class="syncro-stepper-dot"></span>
                        </div>
                        <div class="syncro-stepper-title">Approved</div>
                        <div class="syncro-stepper-subtitle text-muted fst-italic">Pending</div>
                    @endif
                </div>

                <!-- Step 4: Posted -->
                <div class="syncro-stepper-item">
                    @if($isPostedReached)
                        <div class="syncro-stepper-circle" style="background-color: #7c3aed !important; color: #ffffff;">
                            <i class="bi bi-check text-white fw-bold" style="font-size: 1.25rem; line-height: 1;"></i>
                        </div>
                        <div class="syncro-stepper-title" style="color: #7c3aed;">Posted</div>
                        <div class="syncro-stepper-subtitle">
                            <div>{{ $postedDate }}</div>
                            <div><i class="bi bi-clock me-1"></i>{{ $postedTime }}</div>
                        </div>
                    @elseif($isApprovedActive)
                        <div class="syncro-stepper-circle" style="background-color: #7c3aed !important; color: #ffffff;">
                            <span class="syncro-stepper-dot-white"></span>
                        </div>
                        <div class="syncro-stepper-title" style="color: #7c3aed;">Posted</div>
                        <div class="syncro-stepper-subtitle">
                            <span class="badge" style="background-color: #f59e0b; color: #ffffff; font-size: 0.72rem; font-weight: 700; border-radius: 9999px; padding: 2px 10px; letter-spacing: 0.02em;">In Progress</span>
                        </div>
                    @else
                        <div class="syncro-stepper-circle syncro-stepper-circle--upcoming">
                            <span class="syncro-stepper-dot"></span>
                        </div>
                        <div class="syncro-stepper-title">Posted</div>
                        <div class="syncro-stepper-subtitle text-muted fst-italic">Pending</div>
                    @endif
                </div>

            </div>
        </div>
    @endif
</div>
