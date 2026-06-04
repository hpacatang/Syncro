@props(['submission'])

@php
    use App\Services\SubmissionLifecycleService;

    $allowed = auth()->user()->isStaffReviewer()
        ? app(SubmissionLifecycleService::class)->allowedTransitions($submission, auth()->user())
        : [];

    $canReject = false;
    foreach ($allowed as $status) {
        if ($status->value === 'rejected') {
            $canReject = true;
            break;
        }
    }
@endphp

<div class="syncro-queue-actions" data-submission-id="{{ $submission->id }}">
    <div class="syncro-queue-actions__group" role="group" aria-label="Submission actions">
        <a href="{{ route('dashboard.submissions.review', $submission) }}" class="btn btn-sm btn-primary syncro-queue-actions__btn" title="Review submission details">
            <i class="bi bi-eye" aria-hidden="true"></i>
            <span class="syncro-queue-actions__label">Review</span>
        </a>
        <button
            type="button"
            class="btn btn-sm btn-outline-primary syncro-queue-actions__btn generate-btn"
            data-bs-toggle="modal"
            data-bs-target="#generateModal"
            data-submission-id="{{ $submission->id }}"
            data-caption="{{ htmlspecialchars($submission->original_caption, ENT_QUOTES) }}"
            data-enhanced-caption="{{ htmlspecialchars($submission->enhanced_caption ?? '', ENT_QUOTES) }}"
            title="Generate AI caption evaluation">
            <i class="bi bi-magic" aria-hidden="true"></i>
            <span class="syncro-queue-actions__label">Evaluate</span>
        </button>
        <button
            type="button"
            class="btn btn-sm btn-outline-danger syncro-queue-actions__btn lifecycle-inline-step"
            data-submission-id="{{ $submission->id }}"
            data-status="rejected"
            data-needs-notes="1"
            @disabled(! $canReject)
            title="{{ $canReject ? 'Reject post' : 'Reject is not available for this submission status' }}">
            <i class="bi bi-x-lg" aria-hidden="true"></i>
            <span class="syncro-queue-actions__label">Reject</span>
        </button>
    </div>
    <span class="lifecycle-inline-msg syncro-queue-actions__msg" role="status"></span>
</div>

@once
@push('scripts')
<script>
document.addEventListener('click', async function (e) {
    const btn = e.target.closest('.lifecycle-inline-step');
    if (!btn || btn.disabled) return;

    const submissionId = btn.dataset.submissionId;
    const status = btn.dataset.status;
    const needsNotes = btn.dataset.needsNotes === '1';
    const wrap = btn.closest('.syncro-queue-actions');
    const msg = wrap?.querySelector('.lifecycle-inline-msg');
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    if (needsNotes) {
        const notes = window.prompt('Notes for this step (required, min 10 characters):');
        if (!notes || notes.trim().length < 10) {
            if (msg) { msg.textContent = 'Notes required (min 10 chars).'; msg.className = 'lifecycle-inline-msg syncro-queue-actions__msg is-error'; }
            return;
        }
        await applyQueueTransition(submissionId, status, notes, wrap, msg, csrf);
        return;
    }

    await applyQueueTransition(submissionId, status, null, wrap, msg, csrf);
});

async function applyQueueTransition(submissionId, status, notes, wrap, msg, csrf) {
    wrap?.querySelectorAll('button, a.btn').forEach(b => b.classList.add('disabled'));
    if (msg) { msg.textContent = 'Saving…'; msg.className = 'lifecycle-inline-msg syncro-queue-actions__msg'; }

    try {
        const r = await fetch('/api/submissions/' + submissionId + '/transition', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify({ status: status, notes: notes })
        });
        const data = await r.json();
        if (data.success) {
            if (msg) { msg.className = 'lifecycle-inline-msg syncro-queue-actions__msg is-success'; msg.textContent = 'Updated'; }
            setTimeout(() => window.location.reload(), 500);
        } else {
            if (msg) { msg.className = 'lifecycle-inline-msg syncro-queue-actions__msg is-error'; msg.textContent = data.message || 'Failed'; }
            wrap?.querySelectorAll('button, a.btn').forEach(b => b.classList.remove('disabled'));
        }
    } catch (err) {
        if (msg) { msg.className = 'lifecycle-inline-msg syncro-queue-actions__msg is-error'; msg.textContent = err.message; }
        wrap?.querySelectorAll('button, a.btn').forEach(b => b.classList.remove('disabled'));
    }
}
</script>
@endpush
@endonce
