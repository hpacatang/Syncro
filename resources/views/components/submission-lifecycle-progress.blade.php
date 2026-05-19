@props(['submission'])

@php
    use App\Enums\SubmissionLifecycleStatus;

    $current = $submission->lifecycle();
    $steps = SubmissionLifecycleStatus::progressSteps();
    $currentIndex = $current->progressIndex();
    $isRejected = $current === SubmissionLifecycleStatus::Rejected;
    $isRevised = $current === SubmissionLifecycleStatus::Revised;
@endphp

<div class="submission-lifecycle-progress" data-lifecycle-progress data-submission-id="{{ $submission->id }}">
    @if($isRejected)
        <div class="alert alert-danger py-2 mb-2 small">
            <strong>Rejected</strong> — this submission will not proceed unless staff reopens it.
        </div>
    @elseif($isRevised)
        <div class="alert alert-warning py-2 mb-2 small">
            <strong>Revisions requested</strong> — PAIR is updating the caption based on your feedback.
        </div>
    @endif

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-1 mb-2">
        @foreach($steps as $index => $step)
            @php
                $done = $currentIndex > $index;
                $active = $currentIndex === $index;
                $dotClass = $done ? 'bg-success' : ($active ? 'bg-primary' : 'bg-light border');
                $labelClass = $active ? 'fw-bold text-primary' : ($done ? 'text-success' : 'text-muted');
            @endphp
            <div class="text-center flex-fill" style="min-width: 4.5rem;">
                <div
                    class="rounded-circle mx-auto mb-1 {{ $dotClass }}"
                    style="width: 12px; height: 12px;"
                    data-step="{{ $step->value }}"
                ></div>
                <small class="{{ $labelClass }}" style="font-size: 0.65rem; line-height: 1.1;">
                    {{ $step->label() }}
                </small>
            </div>
            @if(!$loop->last)
                <div class="flex-grow-0 text-muted opacity-25 d-none d-md-block">›</div>
            @endif
        @endforeach
    </div>

    <div class="text-center">
        <x-submission-workflow-badge :submission="$submission" size="lg" />
    </div>
</div>
