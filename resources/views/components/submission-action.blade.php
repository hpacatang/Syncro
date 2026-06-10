@props(['submission'])

@php
    $isOwnerOrg = auth()->user()->canSubmitPosts() && (int) $submission->user_id === (int) auth()->id();
    $isStaff    = auth()->user()->isAdmin() || auth()->user()->isPair();
    $wfStatus   = $submission->workflow_status;
    $statusBadge = match($wfStatus) {
        'submitted'         => 'badge bg-secondary',
        'under_peer_review' => 'badge bg-warning text-dark',
        'revised'           => 'badge bg-info text-dark',
        'approved'          => 'badge bg-success',
        'rejected'          => 'badge bg-danger',
        'posted'            => 'badge bg-primary',
        default             => 'badge bg-secondary',
    };
    $isAwaitingOrgReview = ($wfStatus === 'under_peer_review') && $submission->enhanced_caption;
@endphp

<div {{ $attributes->merge(['class' => 'card syncro-card-elevated border-0']) }}>
    <div class="card-header bg-white border-bottom">
        <h5 class="mb-0 fw-bold">Actions</h5>
    </div>
    <div class="card-body">

        @if($isOwnerOrg)

            
            <p class="text-muted small mb-3">
                Status: <span class="{{ $statusBadge }}">{{ ucwords(str_replace('_', ' ', $wfStatus)) }}</span>
            </p>

            @if($isAwaitingOrgReview)
                
                <div class="alert alert-warning py-2 mb-3">
                    <i class="bi bi-bell-fill me-1"></i>
                    <strong>Action required:</strong> PAIR has enhanced your caption. Please review and decide below.
                </div>

                
                <div class="mb-3">
                    <label class="form-label fw-semibold small text-muted text-uppercase">Original Caption</label>
                    <div class="bg-light p-2 rounded small">{{ $submission->original_caption }}</div>
                </div>
                <div class="mb-3">
                    <label class="form-label fw-semibold small text-success text-uppercase">Enhanced Caption</label>
                    <div class="bg-light p-2 rounded small">{{ $submission->enhanced_caption }}</div>
                </div>

                
                <div id="orgDecisionWrap-{{ $submission->id }}">
                    <div class="mb-2">
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="orgDecision-{{ $submission->id }}"
                                id="orgApprove-{{ $submission->id }}" value="approve" checked>
                            <label class="form-check-label text-success fw-semibold" for="orgApprove-{{ $submission->id }}">
                                ✓ Approve — caption is ready to post
                            </label>
                        </div>
                        <div class="form-check mt-1">
                            <input class="form-check-input" type="radio" name="orgDecision-{{ $submission->id }}"
                                id="orgReject-{{ $submission->id }}" value="reject">
                            <label class="form-check-label text-warning fw-semibold" for="orgReject-{{ $submission->id }}">
                                ⟲ Request revisions — send back to PAIR
                            </label>
                        </div>
                    </div>

                    <div id="orgNotes-{{ $submission->id }}" class="mb-2" style="display:none;">
                        <textarea
                            class="form-control form-control-sm"
                            rows="3"
                            id="orgNotesText-{{ $submission->id }}"
                            placeholder="What needs improvement? (required)"></textarea>
                    </div>

                    <button
                        type="button"
                        class="btn btn-success btn-sm w-100 mt-2"
                        id="orgSubmitBtn-{{ $submission->id }}"
                        onclick="submitOrgDecision({{ $submission->id }})">
                        <i class="bi bi-check-circle me-1"></i> Submit Decision
                    </button>
                    <div id="orgDecisionMsg-{{ $submission->id }}" class="small mt-2"></div>
                </div>

            @elseif($wfStatus === 'approved')
                <div class="alert alert-success py-2 mb-3">
                    <i class="bi bi-check-circle-fill me-1"></i>
                    Your caption has been <strong>approved</strong> and is ready to post!
                </div>
            @elseif($wfStatus === 'posted')
                <div class="alert alert-primary py-2 mb-3">
                    <i class="bi bi-megaphone-fill me-1"></i>
                    Your content has been <strong>posted</strong>.
                </div>
            @elseif($wfStatus === 'rejected')
                <div class="alert alert-danger py-2 mb-3">
                    <i class="bi bi-x-circle-fill me-1"></i>
                    This submission was <strong>not approved</strong>. You may resubmit with changes.
                </div>
                <a href="{{ route('org.submit') }}" class="btn btn-primary btn-sm w-100 mb-2">
                    <i class="bi bi-plus me-1"></i> New Submission
                </a>
            @elseif($wfStatus === 'revised')
                <div class="alert alert-info py-2 mb-3">
                    <i class="bi bi-arrow-repeat me-1"></i>
                    Your feedback has been <strong>sent to PAIR</strong> for revision. We'll notify you when it's ready.
                </div>
            @else
                <div class="alert alert-secondary py-2 mb-3">
                    <i class="bi bi-hourglass-split me-1"></i>
                    Your submission is being <strong>processed</strong> by the PAIR team.
                </div>
            @endif

            <a href="{{ route('org.submissions.review', $submission) }}"
               class="btn btn-outline-primary w-100 btn-sm {{ $isAwaitingOrgReview ? 'mt-1' : '' }}">
                <i class="bi bi-arrow-up-right-square me-1"></i> Open Full Review Page
            </a>

        @elseif($isStaff)
            <p class="text-muted small mb-3">Open the Enhance Caption workspace for this submission, or return to the queue.</p>
            <a href="{{ route('dashboard', ['enhance' => $submission->id]) }}" class="btn btn-primary btn-sm w-100 mb-2">
                <i class="bi bi-magic me-1"></i> Enhance Caption
            </a>
            <a href="{{ route('dashboard') }}" class="btn btn-outline-secondary btn-sm w-100 mb-2">
                <i class="bi bi-arrow-left me-1"></i> Back to Queue
            </a>
            <a href="{{ route('staff.caption-assist') }}" class="btn btn-outline-secondary btn-sm w-100">
                <i class="bi bi-cpu me-1"></i> AI Caption from Media
            </a>
        @endif

    </div>
</div>

@push('scripts')
<script>
(function () {
    document.querySelectorAll('[name^="orgDecision-"]').forEach(function (radio) {
        radio.addEventListener('change', function () {
            const id = this.name.replace('orgDecision-', '');
            const notesDiv = document.getElementById('orgNotes-' + id);
            if (notesDiv) notesDiv.style.display = this.value === 'reject' ? 'block' : 'none';
            const msg = document.getElementById('orgDecisionMsg-' + id);
            if (msg) msg.textContent = '';
        });
    });

    document.querySelectorAll('[id^="orgNotesText-"]').forEach(function (textarea) {
        textarea.addEventListener('input', function () {
            const id = this.id.replace('orgNotesText-', '');
            const msg = document.getElementById('orgDecisionMsg-' + id);
            if (msg && this.value.trim().length >= 10) {
                msg.textContent = '';
            }
        });
    });
})();

async function submitOrgDecision(submissionId) {
    const decision = document.querySelector('[name="orgDecision-' + submissionId + '"]:checked')?.value;
    const notes    = document.getElementById('orgNotesText-' + submissionId)?.value?.trim();
    const msg      = document.getElementById('orgDecisionMsg-' + submissionId);
    const btn      = document.getElementById('orgSubmitBtn-' + submissionId);
    const csrf     = document.querySelector('meta[name="csrf-token"]')?.content;

    if (decision === 'reject' && (!notes || notes.length < 10)) {
        if (msg) { msg.className = 'small text-danger'; msg.textContent = 'Please provide at least 10 characters of feedback.'; }
        return;
    }

    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1" role="status"></span>Submitting…';

    const endpoint = decision === 'approve'
        ? '/api/submissions/' + submissionId + '/org-review/approve'
        : '/api/submissions/' + submissionId + '/org-review/reject';

    try {
        const r = await fetch(endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify(decision === 'reject' ? { notes } : {})
        });
        const data = await r.json();
        if (data.success) {
            if (msg) { msg.className = 'small text-success fw-semibold'; msg.textContent = '✓ ' + data.message; }
            setTimeout(() => window.location.reload(), 1000);
        } else {
            if (msg) { msg.className = 'small text-danger'; msg.textContent = data.message || 'Failed. Please try again.'; }
            btn.disabled = false;
            btn.innerHTML = '<i class="bi bi-check-circle me-1"></i> Submit Decision';
        }
    } catch (err) {
        if (msg) { msg.className = 'small text-danger'; msg.textContent = err.message; }
        btn.disabled = false;
        btn.innerHTML = '<i class="bi bi-check-circle me-1"></i> Submit Decision';
    }
}
</script>
@endpush
