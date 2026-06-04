@props(['submission'])

@php
    use App\Services\SubmissionLifecycleService;

    $isStaff = auth()->user()->isStaffReviewer();
    $allowed = $isStaff ? app(SubmissionLifecycleService::class)->allowedTransitions($submission, auth()->user()) : [];
@endphp

@if($isStaff && count($allowed) > 0)
<div class="card syncro-card-elevated border-0" {{ $attributes }}>
    <div class="card-header bg-white border-bottom">
        <h5 class="mb-0 fw-bold"><i class="bi bi-check2-square me-1"></i> PAIR workflow steps</h5>
    </div>
    <div class="card-body">
        <p class="text-muted small mb-3">
            Current: <x-submission-workflow-badge :submission="$submission" />
        </p>
        <p class="small text-muted mb-2">Click the button for each finished step.</p>
        <div class="d-flex flex-wrap gap-2 mb-2" id="lifecycleSteps-{{ $submission->id }}">
            @foreach($allowed as $status)
                <button
                    type="button"
                    class="btn btn-sm btn-outline-primary lifecycle-step-btn"
                    data-submission-id="{{ $submission->id }}"
                    data-status="{{ $status->value }}"
                    data-needs-notes="{{ in_array($status->value, ['revised', 'rejected'], true) ? '1' : '0' }}">
                    {{ $status->actionLabel() }}
                </button>
            @endforeach
        </div>
        <div id="lifecycleMessage-{{ $submission->id }}" class="small"></div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const submissionId = @json($submission->id);
    const msg = document.getElementById('lifecycleMessage-' + submissionId);
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    document.querySelectorAll('#lifecycleSteps-' + submissionId + ' .lifecycle-step-btn').forEach(function (btn) {
        btn.addEventListener('click', async function () {
            const status = this.dataset.status;
            let notes = null;
            if (this.dataset.needsNotes === '1') {
                notes = window.prompt('Notes for this step (required, min 10 characters):');
                if (!notes || notes.trim().length < 10) {
                    msg.className = 'small text-danger';
                    msg.textContent = 'Notes are required for this step.';
                    return;
                }
            }

            document.querySelectorAll('#lifecycleSteps-' + submissionId + ' .lifecycle-step-btn').forEach(b => b.disabled = true);
            msg.className = 'small text-muted';
            msg.textContent = 'Updating…';

            try {
                const r = await fetch('/api/submissions/' + submissionId + '/transition', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify({ status: status, notes: notes })
                });
                const data = await r.json();
                if (data.success) {
                    msg.className = 'small text-success';
                    msg.textContent = data.message;
                    setTimeout(() => window.location.reload(), 600);
                } else {
                    msg.className = 'small text-danger';
                    msg.textContent = data.message || 'Transition failed.';
                    document.querySelectorAll('#lifecycleSteps-' + submissionId + ' .lifecycle-step-btn').forEach(b => b.disabled = false);
                }
            } catch (e) {
                msg.className = 'small text-danger';
                msg.textContent = e.message;
                document.querySelectorAll('#lifecycleSteps-' + submissionId + ' .lifecycle-step-btn').forEach(b => b.disabled = false);
            }
        });
    });
})();
</script>
@endpush
@endif
