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
<div class="modal fade" id="transitionNotesModal" tabindex="-1" aria-labelledby="transitionNotesModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content shadow-lg border-0">
            <div class="modal-header bg-light border-bottom">
                <h5 class="modal-title fw-bold" id="transitionNotesModalLabel">Provide Notes</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <div class="mb-3">
                    <label id="transitionNotesLabel" for="transitionNotesInput" class="form-label fw-semibold">Notes for this step (required, min 10 characters):</label>
                    <textarea class="form-control" id="transitionNotesInput" rows="4" placeholder="Enter notes here..."></textarea>
                    <div class="invalid-feedback">
                        Notes are required and must be at least 10 characters long.
                    </div>
                </div>
            </div>
            <div class="modal-footer border-top bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="transitionNotesSubmitBtn">Confirm</button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    let notesModal = null;
    let currentSubmissionId = null;
    let currentStatus = null;
    let currentWrap = null;
    let currentMsg = null;
    let currentCsrf = null;

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
            currentSubmissionId = submissionId;
            currentStatus = status;
            currentWrap = wrap;
            currentMsg = msg;
            currentCsrf = csrf;

            if (!notesModal) {
                const modalEl = document.getElementById('transitionNotesModal');
                if (modalEl) {
                    notesModal = new bootstrap.Modal(modalEl);
                }
            }

            // Customize title based on action
            let actionName = status === 'rejected' ? 'Reject' : (status === 'revised' ? 'Send back for revision' : 'Transition');
            const titleEl = document.getElementById('transitionNotesModalLabel');
            if (titleEl) titleEl.textContent = actionName + ' Submission';

            const submitBtn = document.getElementById('transitionNotesSubmitBtn');
            if (submitBtn) {
                submitBtn.textContent = 'Confirm ' + actionName;
                if (status === 'rejected') {
                    submitBtn.className = 'btn btn-danger';
                } else {
                    submitBtn.className = 'btn btn-warning';
                }
            }

            const inputEl = document.getElementById('transitionNotesInput');
            if (inputEl) {
                inputEl.value = '';
                inputEl.classList.remove('is-invalid');
            }

            if (notesModal) {
                notesModal.show();
            }
            return;
        }

        await applyQueueTransition(submissionId, status, null, wrap, msg, csrf);
    });

    const inputEl = document.getElementById('transitionNotesInput');
    if (inputEl) {
        inputEl.addEventListener('input', function () {
            if (this.value.trim().length >= 10) {
                this.classList.remove('is-invalid');
            }
        });
    }

    const submitBtn = document.getElementById('transitionNotesSubmitBtn');
    if (submitBtn) {
        submitBtn.addEventListener('click', async function () {
            const inputEl = document.getElementById('transitionNotesInput');
            if (!inputEl) return;

            const notes = inputEl.value.trim();
            if (notes.length < 10) {
                inputEl.classList.add('is-invalid');
                return;
            }

            inputEl.classList.remove('is-invalid');
            if (notesModal) {
                notesModal.hide();
            }

            await applyQueueTransition(currentSubmissionId, currentStatus, notes, currentWrap, currentMsg, currentCsrf);
        });
    }

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
})();
</script>
@endpush
@endonce
