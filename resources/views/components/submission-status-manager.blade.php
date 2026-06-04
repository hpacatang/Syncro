@props(['submission'])

@php
    use App\Services\SubmissionLifecycleService;

    $isStaff = auth()->user()->isStaffReviewer();
    $allowed = $isStaff ? app(SubmissionLifecycleService::class)->allowedTransitions($submission, auth()->user()) : [];
@endphp

<div class="card syncro-card-elevated border-0" {{ $attributes }}>
    <div class="card-header bg-white border-bottom">
        <h5 class="mb-0 fw-bold">PAIR workflow steps</h5>
    </div>
    <div class="card-body">
        <p class="text-muted small mb-3">
            Current: <x-submission-workflow-badge :submission="$submission" />
        </p>

        @if($isStaff && count($allowed) > 0)
            <p class="small text-muted mb-2">Click the button for each finished step.</p>
            <div class="d-flex flex-wrap gap-2 mb-2" id="lifecycleSteps-{{ $submission->id }}">
                @foreach($allowed as $status)
                    @php
                        $btnClass = match ($status->value) {
                            'approved' => 'btn-outline-success',
                            'rejected' => 'btn-outline-danger',
                            'revised'  => 'btn-outline-warning',
                            'posted'   => 'btn-outline-primary',
                            default    => 'btn-outline-secondary',
                        };
                    @endphp
                    <button
                        type="button"
                        class="btn btn-sm {{ $btnClass }} lifecycle-step-btn"
                        data-submission-id="{{ $submission->id }}"
                        data-status="{{ $status->value }}"
                        data-needs-notes="{{ in_array($status->value, ['revised', 'rejected'], true) ? '1' : '0' }}"
                        data-is-rejection="{{ $status->value === 'rejected' ? '1' : '0' }}">
                        {{ $status->actionLabel() }}
                    </button>
                @endforeach
            </div>
            <div id="lifecycleMessage-{{ $submission->id }}" class="small"></div>
        @else
            <p class="small text-muted mb-0">
                Current workflow step is displayed above. Actionable buttons are restricted to authorized reviewers.
            </p>
        @endif
    </div>
</div>

{{-- Lifecycle action modal (rendered once per submission) --}}
<div class="modal fade" id="lifecycleModal-{{ $submission->id }}" tabindex="-1" aria-labelledby="lifecycleModalLabel-{{ $submission->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header border-bottom-0 pb-0">
                <h5 class="modal-title fw-bold" id="lifecycleModalLabel-{{ $submission->id }}">
                    Action Required
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body pt-2">
                <p class="text-muted mb-3" id="lifecycleModalDescription-{{ $submission->id }}">
                    Are you sure you want to perform this action?
                </p>
                <label for="lifecycleReason-{{ $submission->id }}" class="form-label fw-semibold" id="lifecycleReasonLabel-{{ $submission->id }}">
                    Feedback / Notes <span class="text-danger">*</span>
                </label>
                <textarea
                    id="lifecycleReason-{{ $submission->id }}"
                    class="form-control"
                    rows="4"
                    placeholder="Provide details..."
                    maxlength="5000"></textarea>
                <div id="lifecycleReasonError-{{ $submission->id }}" class="text-danger small mt-1" style="display:none;">
                    Notes are required (minimum 10 characters).
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn" id="lifecycleConfirmBtn-{{ $submission->id }}">
                    Confirm
                </button>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const submissionId = @json($submission->id);
    const msg          = document.getElementById('lifecycleMessage-' + submissionId);
    const csrf         = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    const actionModal  = document.getElementById('lifecycleModal-' + submissionId);
    const bsModal      = actionModal ? new bootstrap.Modal(actionModal) : null;
    const notesInput   = document.getElementById('lifecycleReason-' + submissionId);
    const notesError   = document.getElementById('lifecycleReasonError-' + submissionId);
    const confirmBtn   = document.getElementById('lifecycleConfirmBtn-' + submissionId);
    const modalTitle   = document.getElementById('lifecycleModalLabel-' + submissionId);
    const modalDesc    = document.getElementById('lifecycleModalDescription-' + submissionId);
    const notesLabel   = document.getElementById('lifecycleReasonLabel-' + submissionId);

    let pendingStatus  = null;

    /**
     * Core transition — fires the API and handles UI feedback.
     */
    async function doTransition(status, notes) {
        const allBtns = document.querySelectorAll('#lifecycleSteps-' + submissionId + ' .lifecycle-step-btn');
        allBtns.forEach(b => b.disabled = true);
        msg.className   = 'small text-muted';
        msg.textContent = 'Updating…';

        try {
            const r    = await fetch('/api/submissions/' + submissionId + '/transition', {
                method:      'POST',
                credentials: 'same-origin',
                headers:     { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body:        JSON.stringify({ status, notes }),
            });
            const data = await r.json();

            if (data.success) {
                msg.className   = 'small text-success fw-semibold';
                msg.textContent = data.message;
                setTimeout(() => window.location.reload(), 700);
            } else {
                msg.className   = 'small text-danger';
                msg.textContent = data.message || 'Transition failed.';
                allBtns.forEach(b => b.disabled = false);
            }
        } catch (e) {
            msg.className   = 'small text-danger';
            msg.textContent = e.message;
            allBtns.forEach(b => b.disabled = false);
        }
    }

    /**
     * Button click — open modal if notes are needed, otherwise fire directly.
     */
    document.querySelectorAll('#lifecycleSteps-' + submissionId + ' .lifecycle-step-btn').forEach(function (btn) {
        btn.addEventListener('click', function () {
            const status      = this.dataset.status;
            const needsNotes  = this.dataset.needsNotes === '1';

            if (needsNotes && bsModal) {
                pendingStatus = status;
                
                // Reset modal state
                if (notesInput) notesInput.value = '';
                if (notesError) notesError.style.display = 'none';
                if (confirmBtn) {
                    confirmBtn.disabled = false;
                    confirmBtn.className = 'btn';
                }

                // Customize modal content based on status
                if (status === 'rejected') {
                    if (modalTitle) modalTitle.textContent = 'Reject Submission #' + submissionId;
                    if (modalTitle) {
                        modalTitle.classList.add('text-danger');
                        modalTitle.classList.remove('text-warning');
                    }
                    if (modalDesc) modalDesc.textContent = 'Are you sure you want to reject this submission? This action will notify the organization and halt the review process.';
                    if (notesLabel) notesLabel.textContent = 'Rejection reason';
                    if (notesInput) notesInput.placeholder = 'Provide a clear explanation of what needs to change so the organization understands what to fix…';
                    if (confirmBtn) {
                        confirmBtn.textContent = 'Confirm Rejection';
                        confirmBtn.classList.add('btn-danger');
                    }
                } else if (status === 'revised') {
                    if (modalTitle) modalTitle.textContent = 'Request Revision #' + submissionId;
                    if (modalTitle) {
                        modalTitle.classList.add('text-warning');
                        modalTitle.classList.remove('text-danger');
                    }
                    if (modalDesc) modalDesc.textContent = 'Send this submission back to the organization with feedback on what changes are needed.';
                    if (notesLabel) notesLabel.textContent = 'Revision instructions';
                    if (notesInput) notesInput.placeholder = 'Explain what needs to be edited or corrected…';
                    if (confirmBtn) {
                        confirmBtn.textContent = 'Request Revision';
                        confirmBtn.classList.add('btn-warning');
                    }
                }

                bsModal.show();
                return;
            }

            // Non-notes transitions — fire immediately
            doTransition(status, null);
        });
    });

    /**
     * Confirm button inside the modal — validate then submit.
     */
    if (confirmBtn && actionModal) {
        confirmBtn.addEventListener('click', function () {
            const notes = notesInput ? notesInput.value.trim() : '';

            if (notes.length < 10) {
                if (notesError) notesError.style.display = 'block';
                if (notesInput) notesInput.focus();
                return;
            }

            if (notesError) notesError.style.display = 'none';
            confirmBtn.disabled = true;
            confirmBtn.textContent = 'Submitting…';

            bsModal.hide();
            doTransition(pendingStatus, notes);
        });

        // Clear error message as the user types
        if (notesInput) {
            notesInput.addEventListener('input', function () {
                if (this.value.trim().length >= 10) {
                    if (notesError) notesError.style.display = 'none';
                }
            });
        }
    }
})();
</script>
@endpush
