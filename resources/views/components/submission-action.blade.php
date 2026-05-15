@props(['submission'])

@php
    $isOwnerOrg = auth()->user()->isOrg() && (int) $submission->user_id === (int) auth()->id();
    $canOrgReview = $isOwnerOrg && $submission->workflow_status === 'pending_org_approval';
    $isStaff = auth()->user()->isAdmin() || auth()->user()->isPair();
@endphp

<div {{ $attributes->merge(['class' => 'card syncro-card-elevated border-0']) }}>
    <div class="card-header bg-white border-bottom">
        <h5 class="mb-0 fw-bold">Actions</h5>
    </div>
    <div class="card-body">
        @if($canOrgReview)
            <p class="text-muted small mb-3">PAIR updated your caption. Approve to finalize, or send feedback for another pass.</p>
            <div class="d-flex flex-wrap gap-2 mb-3">
                <button type="button" class="btn btn-success" id="submissionActionApprove">
                    <i class="bi bi-check-lg me-1"></i> Approve caption
                </button>
            </div>
            <div class="mb-2">
                <label for="submissionActionRejectNotes" class="form-label small fw-semibold">Request changes (min. 10 characters)</label>
                <textarea id="submissionActionRejectNotes" class="form-control" rows="3" placeholder="What should PAIR adjust?"></textarea>
            </div>
            <button type="button" class="btn btn-outline-warning" id="submissionActionReject">
                <i class="bi bi-arrow-return-left me-1"></i> Send back to PAIR
            </button>
        @elseif($isOwnerOrg)
            <p class="text-muted mb-0">No actions for this status. Current step: <strong>{{ str_replace('_', ' ', $submission->workflow_status) }}</strong>.</p>
        @elseif($isStaff)
            <p class="text-muted small mb-3">Use <strong>Evaluate</strong> on the dashboard to generate captions, or caption assist for new uploads.</p>
            <a href="{{ route('dashboard') }}" class="btn btn-primary btn-sm me-2">Back to queue</a>
            <a href="{{ route('staff.caption-assist') }}" class="btn btn-outline-secondary btn-sm">AI caption from media</a>
        @endif
    </div>
</div>

@if($canOrgReview)
    @push('scripts')
    <script>
    (function () {
        const submissionId = @json($submission->id);
        const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

        document.getElementById('submissionActionApprove')?.addEventListener('click', async function () {
            this.disabled = true;
            try {
                const r = await fetch('/api/submissions/' + submissionId + '/org-review/approve', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify({})
                });
                const data = await r.json();
                if (data.success) window.location.reload();
                else { alert(data.message || 'Failed'); this.disabled = false; }
            } catch (e) {
                alert(e.message);
                this.disabled = false;
            }
        });

        document.getElementById('submissionActionReject')?.addEventListener('click', async function () {
            const notes = document.getElementById('submissionActionRejectNotes')?.value?.trim() || '';
            if (notes.length < 10) {
                alert('Please enter at least 10 characters of feedback for PAIR.');
                return;
            }
            this.disabled = true;
            try {
                const r = await fetch('/api/submissions/' + submissionId + '/org-review/reject', {
                    method: 'POST',
                    credentials: 'same-origin',
                    headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                    body: JSON.stringify({ notes: notes })
                });
                const data = await r.json();
                if (data.success) window.location.reload();
                else { alert(data.message || 'Failed'); this.disabled = false; }
            } catch (e) {
                alert(e.message);
                this.disabled = false;
            }
        });
    })();
    </script>
    @endpush
@endif
