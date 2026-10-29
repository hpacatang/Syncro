@props(['submission'])

@php
    use App\Services\SubmissionLifecycleService;

    $isStaff  = auth()->user()->isStaffReviewer();
    $isPair   = auth()->user()->isPair();
    $allowed  = $isStaff ? app(SubmissionLifecycleService::class)->allowedTransitions($submission, auth()->user()) : [];

    // Separate into ordered groups for consistent button layout
    $primaryActions = array_filter($allowed, fn($s) => in_array($s->value, ['approved', 'posted', 'under_peer_review', 'dept_approved'], true));
    $revisionAction = array_filter($allowed, fn($s) => $s->value === 'revised');
    $rejectAction   = array_filter($allowed, fn($s) => $s->value === 'rejected');
@endphp

<div class="card syncro-card-elevated border-0" {{ $attributes }}>
    <div class="card-header bg-white border-bottom">
        <h5 class="mb-0 fw-bold">Review workflow steps</h5>
    </div>
    <div class="card-body">
        <p class="text-muted small mb-3">
            Current: <x-submission-workflow-badge :submission="$submission" />
        </p>

        @if($isStaff && count($allowed) > 0)
            <p class="small text-muted mb-2">Click the button for each finished step.</p>
            <div class="d-flex align-items-center flex-wrap gap-2 mb-2" id="lifecycleSteps-{{ $submission->id }}">

                {{-- Primary actions: Approve, Post, Start Review, Dept Approve --}}
                @foreach($primaryActions as $status)
                    @php
                        $btnClass = match ($status->value) {
                            'approved'      => 'btn-outline-success',
                            'posted'        => 'btn-outline-primary',
                            'dept_approved' => 'btn-outline-info',
                            default         => 'btn-outline-secondary',
                        };
                    @endphp
                    <button
                        type="button"
                        class="btn btn-sm {{ $btnClass }} lifecycle-step-btn"
                        data-submission-id="{{ $submission->id }}"
                        data-status="{{ $status->value }}"
                        data-needs-notes="0"
                        data-is-rejection="0">
                        {{ $status->actionLabel() }}
                    </button>
                @endforeach

                {{-- Revision action --}}
                @foreach($revisionAction as $status)
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-warning lifecycle-step-btn"
                        data-submission-id="{{ $submission->id }}"
                        data-status="{{ $status->value }}"
                        data-needs-notes="1"
                        data-is-rejection="0">
                        {{ $status->actionLabel() }}
                    </button>
                @endforeach

                {{-- Reject action — inline, adjacent to other buttons, hidden for PAIR --}}
                @foreach($rejectAction as $status)
                    <button
                        type="button"
                        class="btn btn-sm btn-outline-danger lifecycle-step-btn"
                        data-submission-id="{{ $submission->id }}"
                        data-status="{{ $status->value }}"
                        data-needs-notes="1"
                        data-is-rejection="1">
                        {{ $status->actionLabel() }}
                    </button>
                @endforeach

            </div>
            <div id="lifecycleMessage-{{ $submission->id }}" class="small"></div>
        @elseif($submission->workflow_status === 'rejected')
            <p class="small text-muted mb-0">
                <i class="bi bi-lock-fill me-1"></i> This submission was rejected. A rejection is final &mdash; this record is view only.
            </p>
        @else
            <p class="small text-muted mb-0">
                Current workflow step is displayed above. Actionable buttons are restricted to authorized reviewers.
            </p>
        @endif
    </div>
</div>

<div class="modal fade" id="lifecycleModal-{{ $submission->id }}" tabindex="-1" aria-labelledby="lifecycleModalLabel-{{ $submission->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 14px; overflow: hidden; border: 1.5px solid #143a22;">
            <div class="modal-header border-0" id="lifecycleModalHeader-{{ $submission->id }}" style="background-color: #143a22; padding: 1.1rem 1.4rem;">
                <h5 class="modal-title fw-bold text-white mb-0" id="lifecycleModalLabel-{{ $submission->id }}" style="font-size: 1.25rem;">
                    Reject Submission #{{ $submission->id }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-white" style="padding: 1.4rem 1.4rem 1.2rem 1.4rem;">
                <p class="text-secondary mb-3" id="lifecycleModalDescription-{{ $submission->id }}" style="font-size: 0.96rem; line-height: 1.45;">
                    Are you sure you want to reject this submission? Once rejected, this action CANNOT BE UNDONE.
                </p>
                <label for="lifecycleReason-{{ $submission->id }}" class="form-label fw-bold text-dark mb-2" id="lifecycleReasonLabel-{{ $submission->id }}" style="font-size: 1.05rem;">
                    Rejection reason
                </label>
                <textarea
                    id="lifecycleReason-{{ $submission->id }}"
                    class="form-control"
                    rows="4"
                    placeholder="Provide a clear explanation of why it is rejected..."
                    style="border: 1.5px solid #28a745; border-radius: 8px; padding: 0.75rem; box-shadow: none; font-size: 0.95rem;"
                    maxlength="5000"></textarea>
                <div id="lifecycleReasonError-{{ $submission->id }}" class="text-danger small mt-2 fw-semibold" style="display:none;">
                    Notes are required (minimum 10 characters).
                </div>
            </div>
            <div class="modal-footer border-0" id="lifecycleModalFooter-{{ $submission->id }}" style="background-color: #143a22; padding: 0.9rem 1.4rem; display: flex; justify-content: flex-end; gap: 0.75rem;">
                <button type="button" class="btn" data-bs-dismiss="modal" style="background: transparent; border: 1.5px solid #2f6b43; color: #ffffff; border-radius: 6px; padding: 0.42rem 1.35rem; font-weight: 500;">
                    Cancel
                </button>
                <button type="button" class="btn" id="lifecycleConfirmBtn-{{ $submission->id }}" style="background-color: #dc3545; border: none; color: #ffffff; border-radius: 6px; padding: 0.42rem 1.35rem; font-weight: 500;">
                    Confirm Rejection
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
    const actionModal  = document.getElementById('lifecycleModal-' + submissionId);
    const notesInput   = document.getElementById('lifecycleReason-' + submissionId);
    const notesError   = document.getElementById('lifecycleReasonError-' + submissionId);
    const confirmBtn   = document.getElementById('lifecycleConfirmBtn-' + submissionId);
    const modalTitle   = document.getElementById('lifecycleModalLabel-' + submissionId);
    const modalDesc    = document.getElementById('lifecycleModalDescription-' + submissionId);
    const notesLabel   = document.getElementById('lifecycleReasonLabel-' + submissionId);

    let pendingStatus  = null;

    function getModal() {
        if (!actionModal) return null;
        if (typeof bootstrap !== 'undefined' && bootstrap && bootstrap.Modal) {
            try {
                return bootstrap.Modal.getOrCreateInstance(actionModal);
            } catch (e) {
                return null;
            }
        }
        return null;
    }

    function hideStatusModal() {
        const bsModal = getModal();
        if (bsModal) {
            try { bsModal.hide(); } catch (e) {}
        }
        if (actionModal) {
            actionModal.classList.remove('show');
            actionModal.style.display = 'none';
            actionModal.setAttribute('aria-hidden', 'true');
            actionModal.removeAttribute('aria-modal');
        }
        document.body.classList.remove('modal-open');
        const backdrop = document.getElementById('lifecycleBackdrop-' + submissionId);
        if (backdrop) backdrop.remove();
    }

    function showStatusModal(status) {
        pendingStatus = status;

        if (notesInput) {
            notesInput.value = '';
        }
        if (notesError) {
            notesError.style.display = 'none';
        }

        if (status === 'rejected') {
            if (modalTitle) {
                modalTitle.textContent = 'Reject Submission #' + submissionId;
                modalTitle.className = 'modal-title fw-bold text-white mb-0';
            }
            if (modalDesc) modalDesc.textContent = 'Are you sure you want to reject this submission? Once rejected, this action CANNOT BE UNDONE.';
            if (notesLabel) notesLabel.textContent = 'Rejection reason';
            if (notesInput) notesInput.placeholder = 'Provide a clear explanation of why it is rejected…';
            if (confirmBtn) {
                confirmBtn.disabled = false;
                confirmBtn.textContent = 'Confirm Rejection';
                confirmBtn.style.backgroundColor = '#dc3545';
                confirmBtn.style.border = 'none';
                confirmBtn.style.color = '#ffffff';
            }
        } else if (status === 'revised') {
            if (modalTitle) {
                modalTitle.textContent = 'Request Revision #' + submissionId;
                modalTitle.className = 'modal-title fw-bold text-white mb-0';
            }
            if (modalDesc) modalDesc.textContent = 'Send this submission back to the organization with feedback on what changes are needed.';
            if (notesLabel) notesLabel.textContent = 'Revision instructions';
            if (notesInput) notesInput.placeholder = 'Explain what needs to be edited or corrected…';
            if (confirmBtn) {
                confirmBtn.disabled = false;
                confirmBtn.textContent = 'Request Revision';
                confirmBtn.style.backgroundColor = '#d97706';
                confirmBtn.style.border = 'none';
                confirmBtn.style.color = '#ffffff';
            }
        }

        const bsModal = getModal();
        if (bsModal) {
            bsModal.show();
            return;
        }

        // Direct DOM fallback
        if (actionModal) {
            actionModal.classList.add('show');
            actionModal.style.display = 'block';
            actionModal.removeAttribute('aria-hidden');
            actionModal.setAttribute('aria-modal', 'true');
            document.body.classList.add('modal-open');
            let backdrop = document.getElementById('lifecycleBackdrop-' + submissionId);
            if (!backdrop) {
                backdrop = document.createElement('div');
                backdrop.id = 'lifecycleBackdrop-' + submissionId;
                backdrop.className = 'modal-backdrop fade show';
                document.body.appendChild(backdrop);
                backdrop.addEventListener('click', hideStatusModal);
            }
        }
    }

    if (actionModal) {
        actionModal.querySelectorAll('[data-bs-dismiss="modal"]').forEach(btn => {
            btn.addEventListener('click', hideStatusModal);
        });
    }

    if (notesInput) {
        notesInput.addEventListener('input', function () {
            if (this.value.trim().length >= 10 && notesError) {
                notesError.style.display = 'none';
            }
        });
    }

    async function doTransition(status, notes) {
        const allBtns = document.querySelectorAll('#lifecycleSteps-' + submissionId + ' .lifecycle-step-btn');
        allBtns.forEach(b => {
            b.disabled = true;
            if (b.dataset.status === status) {
                b.dataset.origHtml = b.innerHTML;
                b.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span>' + b.textContent.trim();
            }
        });
        if (msg) {
            msg.className   = 'small text-muted mt-2';
            msg.textContent = 'Updating status…';
        }

        try {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
            const r    = await fetch('/api/submissions/' + submissionId + '/transition', {
                method:      'POST',
                credentials: 'same-origin',
                headers:     {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrf
                },
                body:        JSON.stringify({ status: status, notes: notes }),
            });
            const data = await r.json();

            if (data.success) {
                if (msg) {
                    msg.className   = 'small text-success fw-semibold mt-2';
                    msg.textContent = '✓ ' + (data.message || 'Status updated.');
                }
                setTimeout(() => window.location.reload(), 600);
            } else {
                const err = data.message || 'Transition failed.';
                if (msg) {
                    msg.className   = 'small text-danger mt-2';
                    msg.textContent = err;
                }
                alert(err);
                allBtns.forEach(b => {
                    b.disabled = false;
                    if (b.dataset.origHtml) b.innerHTML = b.dataset.origHtml;
                });
            }
        } catch (e) {
            const err = e.message || 'Failed to update status.';
            if (msg) {
                msg.className   = 'small text-danger mt-2';
                msg.textContent = err;
            }
            alert(err);
            allBtns.forEach(b => {
                b.disabled = false;
                if (b.dataset.origHtml) b.innerHTML = b.dataset.origHtml;
            });
        }
    }

    document.querySelectorAll('#lifecycleSteps-' + submissionId + ' .lifecycle-step-btn').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const status     = this.dataset.status;
            const needsNotes = this.dataset.needsNotes === '1';

            if (needsNotes) {
                showStatusModal(status);
                return;
            }

            doTransition(status, null);
        });
    });

    if (confirmBtn && actionModal) {
        confirmBtn.addEventListener('click', function (e) {
            e.preventDefault();
            const notes = notesInput ? notesInput.value.trim() : '';

            if (notes.length < 10) {
                if (notesError) notesError.style.display = 'block';
                if (notesInput) notesInput.focus();
                return;
            }

            if (notesError) notesError.style.display = 'none';
            confirmBtn.disabled = true;
            confirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span>Submitting…';

            hideStatusModal();
            doTransition(pendingStatus, notes);
        });
    }
})();
</script>
@endpush
