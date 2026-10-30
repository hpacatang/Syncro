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
        @elseif($submission->workflow_status === 'posted')
            @php
                $isTimeReached = $submission->isPublicationTimeReached();
            @endphp
            <div class="p-3 bg-light rounded border border-success border-opacity-25 mb-2">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 mb-2">
                    <div>
                        <div class="fw-bold text-success mb-1 d-flex align-items-center">
                            <i class="bi bi-check-circle-fill me-2 fs-5"></i> Published &amp; Posted
                        </div>
                        <div class="small text-muted d-flex align-items-center flex-wrap">
                            <span class="me-1">Date &amp; Time:</span>
                            @if($submission->publicationDateTime())
                                <span id="displayedPostedAt-{{ $submission->id }}" class="text-dark fw-semibold d-inline-flex align-items-center">
                                    <i class="bi bi-calendar-check me-1 text-success"></i><x-formatted-date :at="$submission->publicationDateTime()" format="M d, Y \a\t h:i A" />
                                </span>
                            @else
                                <span id="displayedPostedAt-{{ $submission->id }}" class="text-muted ms-1">Not recorded</span>
                            @endif
                        </div>
                    </div>
                </div>

                @if($isTimeReached)
                    <div class="pt-2 border-top d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <span class="badge bg-secondary py-1 px-2 d-inline-flex align-items-center">
                            <i class="bi bi-lock-fill me-1"></i> Published &amp; Locked
                        </span>
                        <span class="small text-muted fst-italic">
                            Publication time has arrived &mdash; post is live, view only, and cannot be undone.
                        </span>
                    </div>
                @else
                    <div class="pt-2 border-top d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <span class="badge bg-warning text-dark py-1 px-2 d-inline-flex align-items-center">
                            <i class="bi bi-clock-history me-1"></i> Pending Auto-Publish
                        </span>
                        @if($isStaff)
                            <button type="button" class="btn btn-sm btn-outline-success d-inline-flex align-items-center" id="editPostedDateBtn-{{ $submission->id }}" title="Change or edit publication date and time">
                                <i class="bi bi-pencil-square me-1"></i> Edit Date / Time
                            </button>
                        @endif
                    </div>
                @endif
            </div>
            <p class="small text-muted mb-0">
                <i class="bi bi-lock-fill me-1"></i> Once published and posted, this submission is view only and cannot be undone.
            </p>
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

{{-- Calendar & Date/Time Modal for Marking as Posted or Editing Published Date --}}
<div class="modal fade" id="postingCalendarModal-{{ $submission->id }}" tabindex="-1" aria-labelledby="postingCalendarModalLabel-{{ $submission->id }}" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 14px; overflow: hidden; border: 1.5px solid #143a22;">
            <div class="modal-header border-0" style="background-color: #143a22; padding: 1.1rem 1.4rem;">
                <h5 class="modal-title fw-bold text-white mb-0 d-flex align-items-center" id="postingCalendarModalLabel-{{ $submission->id }}" style="font-size: 1.2rem;">
                    <i class="bi bi-calendar-event me-2"></i>Schedule &amp; Post Submission #{{ $submission->id }}
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-white" style="padding: 1.4rem;">
                <p class="text-secondary small mb-3" id="postingModalDescription-{{ $submission->id }}">
                    Select the publication date and time. When the scheduled date and time arrives, it will automatically post to social media and become locked against further edits.
                </p>

                <div class="alert alert-light border small text-muted py-2 px-3 mb-3 d-flex align-items-start gap-2" style="background-color: #f8fafc; border-radius: 8px;">
                    <i class="bi bi-broadcast text-success fs-5 mt-0"></i>
                    <div>
                        <strong class="text-dark">Social Media Auto-Publish:</strong> When your <code>FACEBOOK_PAGE_ACCESS_TOKEN</code> and <code>FACEBOOK_PAGE_ID</code> are configured in <code>.env</code>, Syncro will automatically dispatch the post directly to your Facebook Page when its time arrives.
                    </div>
                </div>

                {{-- Quick helper buttons --}}
                <div class="d-flex align-items-center flex-wrap gap-2 mb-3">
                    <span class="small text-muted fw-semibold me-1">Quick picks:</span>
                    <button type="button" class="btn btn-sm btn-outline-secondary py-1 px-2 small d-inline-flex align-items-center" id="useCurrentTimeBtn-{{ $submission->id }}" style="font-size: 0.82rem;">
                        <i class="bi bi-clock me-1"></i>Now (Current Time)
                    </button>
                    @if($submission->scheduled_post_at)
                        <button type="button" class="btn btn-sm btn-outline-primary py-1 px-2 small d-inline-flex align-items-center" id="useDesiredTimeBtn-{{ $submission->id }}" style="font-size: 0.82rem;" data-desired-date="{{ $submission->scheduled_post_at->format('Y-m-d\TH:i') }}">
                            <i class="bi bi-calendar-check me-1"></i>Desired: {{ $submission->scheduled_post_at->format('M d, h:i A') }}
                        </button>
                    @endif
                </div>

                <div class="mb-3">
                    <label for="postingDateTime-{{ $submission->id }}" class="form-label fw-bold text-dark mb-1">
                        Publication Date &amp; Time <span class="text-danger">*</span>
                    </label>
                    <input
                        type="datetime-local"
                        id="postingDateTime-{{ $submission->id }}"
                        class="form-control form-control-lg fw-semibold"
                        value="{{ $submission->posted_at ? $submission->posted_at->format('Y-m-d\TH:i') : ($submission->scheduled_post_at ? $submission->scheduled_post_at->format('Y-m-d\TH:i') : now()->format('Y-m-d\TH:i')) }}"
                        style="border: 1.5px solid #28a745; border-radius: 8px; font-size: 1.05rem;"
                        required>
                    <div class="form-text small text-muted">
                        Click the calendar/clock icon inside the input to choose the date and time.
                    </div>
                </div>

                <div class="mb-2">
                    <label for="postingNotes-{{ $submission->id }}" class="form-label fw-semibold text-dark small mb-1">
                        Post Link or Reviewer Notes <span class="text-muted fw-normal">(Optional)</span>
                    </label>
                    <input
                        type="text"
                        id="postingNotes-{{ $submission->id }}"
                        class="form-control"
                        placeholder="e.g. Published on FB Page: https://facebook.com/..."
                        style="border-radius: 6px; font-size: 0.92rem;"
                        maxlength="5000">
                </div>

                <div id="postingDateError-{{ $submission->id }}" class="alert alert-danger py-2 px-3 small mt-2 mb-0" style="display:none;"></div>
            </div>
            <div class="modal-footer border-0" style="background-color: #143a22; padding: 0.9rem 1.4rem; display: flex; justify-content: flex-end; gap: 0.75rem;">
                <button type="button" class="btn d-inline-flex align-items-center" data-bs-dismiss="modal" style="background: transparent; border: 1.5px solid #2f6b43; color: #ffffff; border-radius: 6px; padding: 0.42rem 1.35rem; font-weight: 500;">
                    Cancel
                </button>
                <button type="button" class="btn d-inline-flex align-items-center" id="postingDateConfirmBtn-{{ $submission->id }}" style="background-color: #28a745; border: none; color: #ffffff; border-radius: 6px; padding: 0.42rem 1.35rem; font-weight: 600;">
                    <i class="bi bi-check-lg me-1"></i> Confirm &amp; Post
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

    // --- Posting Calendar / Date & Time Modal Handlers ---
    const postModalEl     = document.getElementById('postingCalendarModal-' + submissionId);
    const postDateInput   = document.getElementById('postingDateTime-' + submissionId);
    const postNotesInput  = document.getElementById('postingNotes-' + submissionId);
    const postConfirmBtn  = document.getElementById('postingDateConfirmBtn-' + submissionId);
    const postModalTitle  = document.getElementById('postingCalendarModalLabel-' + submissionId);
    const postModalDesc   = document.getElementById('postingModalDescription-' + submissionId);
    const postDateError   = document.getElementById('postingDateError-' + submissionId);
    const useCurrentBtn   = document.getElementById('useCurrentTimeBtn-' + submissionId);
    const useDesiredBtn   = document.getElementById('useDesiredTimeBtn-' + submissionId);
    const editPostedBtn   = document.getElementById('editPostedDateBtn-' + submissionId);

    let isEditingExistingPost = false;

    function getPostingModal() {
        if (!postModalEl) return null;
        if (typeof bootstrap !== 'undefined' && bootstrap && bootstrap.Modal) {
            try { return bootstrap.Modal.getOrCreateInstance(postModalEl); } catch (e) {}
        }
        return null;
    }

    function hidePostingModal() {
        const bs = getPostingModal();
        if (bs) {
            try { bs.hide(); } catch (e) {}
        }
        if (postModalEl) {
            postModalEl.classList.remove('show');
            postModalEl.style.display = 'none';
            postModalEl.setAttribute('aria-hidden', 'true');
            postModalEl.removeAttribute('aria-modal');
        }
        document.body.classList.remove('modal-open');
        const backdrop = document.getElementById('postingBackdrop-' + submissionId);
        if (backdrop) backdrop.remove();
    }

    function showPostingModal(isEdit) {
        isEditingExistingPost = !!isEdit;
        if (postDateError) {
            postDateError.style.display = 'none';
            postDateError.textContent = '';
        }
        if (postModalTitle) {
            postModalTitle.innerHTML = isEditingExistingPost
                ? '<i class="bi bi-calendar-check me-2"></i>Edit Publication Date &amp; Time #' + submissionId
                : '<i class="bi bi-calendar-event me-2"></i>Schedule &amp; Post Submission #' + submissionId;
        }
        if (postModalDesc) {
            postModalDesc.textContent = isEditingExistingPost
                ? 'Update the recorded publication date and time in case there was an error or schedule adjustment.'
                : 'Select the exact publication date and time when this content was or will be published. You can adjust this later if needed.';
        }
        if (postConfirmBtn) {
            postConfirmBtn.disabled = false;
            postConfirmBtn.innerHTML = isEditingExistingPost
                ? '<i class="bi bi-save me-1"></i> Save Updated Date/Time'
                : '<i class="bi bi-check-lg me-1"></i> Confirm &amp; Post';
        }

        const bs = getPostingModal();
        if (bs) {
            bs.show();
            return;
        }

        if (postModalEl) {
            postModalEl.classList.add('show');
            postModalEl.style.display = 'block';
            postModalEl.removeAttribute('aria-hidden');
            postModalEl.setAttribute('aria-modal', 'true');
            document.body.classList.add('modal-open');
            let backdrop = document.getElementById('postingBackdrop-' + submissionId);
            if (!backdrop) {
                backdrop = document.createElement('div');
                backdrop.id = 'postingBackdrop-' + submissionId;
                backdrop.className = 'modal-backdrop fade show';
                document.body.appendChild(backdrop);
                backdrop.addEventListener('click', hidePostingModal);
            }
        }
    }

    if (postModalEl) {
        postModalEl.querySelectorAll('[data-bs-dismiss="modal"]').forEach(btn => {
            btn.addEventListener('click', hidePostingModal);
        });
    }

    if (useCurrentBtn && postDateInput) {
        useCurrentBtn.addEventListener('click', function () {
            const now = new Date();
            const pad = n => String(n).padStart(2, '0');
            postDateInput.value = `${now.getFullYear()}-${pad(now.getMonth() + 1)}-${pad(now.getDate())}T${pad(now.getHours())}:${pad(now.getMinutes())}`;
            if (postDateError) postDateError.style.display = 'none';
        });
    }

    if (useDesiredBtn && postDateInput) {
        useDesiredBtn.addEventListener('click', function () {
            const desired = this.dataset.desiredDate;
            if (desired) {
                postDateInput.value = desired;
                if (postDateError) postDateError.style.display = 'none';
            }
        });
    }

    if (editPostedBtn) {
        editPostedBtn.addEventListener('click', function (e) {
            e.preventDefault();
            showPostingModal(true);
        });
    }

    if (postConfirmBtn) {
        postConfirmBtn.addEventListener('click', async function (e) {
            e.preventDefault();
            const chosenDate = postDateInput ? postDateInput.value.trim() : '';
            if (!chosenDate) {
                if (postDateError) {
                    postDateError.textContent = 'Please choose a valid date and time using the calendar.';
                    postDateError.style.display = 'block';
                }
                return;
            }

            postConfirmBtn.disabled = true;
            postConfirmBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span>Saving…';
            if (postDateError) postDateError.style.display = 'none';

            const notes = postNotesInput ? postNotesInput.value.trim() : null;

            try {
                const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                const r = await fetch('/api/submissions/' + submissionId + '/transition', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: {
                        'Content-Type': 'application/json',
                        'Accept': 'application/json',
                        'X-CSRF-TOKEN': csrf
                    },
                    body: JSON.stringify({
                        status: 'posted',
                        posted_at: chosenDate,
                        scheduled_post_at: chosenDate,
                        notes: notes
                    }),
                });

                const data = await r.json();

                if (data.success) {
                    hidePostingModal();
                    if (msg) {
                        msg.className = 'small text-success fw-semibold mt-2';
                        msg.textContent = '✓ ' + (data.message || 'Status updated.');
                    }
                    setTimeout(() => window.location.reload(), 400);
                } else {
                    const err = data.message || 'Failed to save date and time.';
                    if (postDateError) {
                        postDateError.textContent = err;
                        postDateError.style.display = 'block';
                    } else {
                        alert(err);
                    }
                    postConfirmBtn.disabled = false;
                    postConfirmBtn.innerHTML = isEditingExistingPost
                        ? '<i class="bi bi-save me-1"></i> Save Updated Date/Time'
                        : '<i class="bi bi-check-lg me-1"></i> Confirm &amp; Post';
                }
            } catch (e) {
                const err = e.message || 'Failed to save date and time.';
                if (postDateError) {
                    postDateError.textContent = err;
                    postDateError.style.display = 'block';
                } else {
                    alert(err);
                }
                postConfirmBtn.disabled = false;
                postConfirmBtn.innerHTML = isEditingExistingPost
                    ? '<i class="bi bi-save me-1"></i> Save Updated Date/Time'
                    : '<i class="bi bi-check-lg me-1"></i> Confirm &amp; Post';
            }
        });
    }

    document.querySelectorAll('#lifecycleSteps-' + submissionId + ' .lifecycle-step-btn').forEach(function (btn) {
        btn.addEventListener('click', function (e) {
            e.preventDefault();
            const status     = this.dataset.status;
            const needsNotes = this.dataset.needsNotes === '1';

            if (status === 'posted') {
                showPostingModal(false);
                return;
            }

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
