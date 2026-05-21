@props(['submission', 'title' => 'Submission progress'])

@if($submission)
<div class="card shadow-sm border-0 mb-4 submission-lifecycle-tracker-card" {{ $attributes }}>
    <div class="card-body">
        <h6 class="text-muted text-uppercase small mb-3 fw-semibold">{{ $title }}</h6>
        <x-submission-lifecycle-progress :submission="$submission" />
    </div>
</div>
@endif
