@props(['submission', 'size' => ''])

@php
    use App\Submission\Enums\SubmissionLifecycleStatus;

    $lifecycle = $submission->lifecycle();
    $pillColor = match ($lifecycle) {
        SubmissionLifecycleStatus::Rejected        => '#dc2626',
        SubmissionLifecycleStatus::Approved        => '#2563eb',
        SubmissionLifecycleStatus::Posted          => '#7c3aed',
        SubmissionLifecycleStatus::UnderPeerReview => '#f97316',
        SubmissionLifecycleStatus::DeptApproved    => '#0891b2',
        SubmissionLifecycleStatus::Revised         => '#eab308',
        default                                    => '#16a34a',
    };

    $padClass = match ($size) {
        'lg'    => 'px-4 py-2 fs-6',
        'sm'    => 'px-3 py-1 syncro-badge-sm',
        default => 'px-3 py-1',
    };
@endphp

<span
    {{ $attributes->merge([
        'class' => "badge fw-bold text-white shadow-sm rounded-pill {$padClass}",
        'data-lifecycle-badge' => '',
        'data-submission-id' => $submission->id,
    ]) }}
    style="background-color: {{ $pillColor }}; letter-spacing: 0.025em; text-align: center; font-weight: 700;"
>
    {{ $lifecycle->label() }}
</span>
