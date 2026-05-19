@props(['submission'])

@php
    use App\Enums\SubmissionLifecycleStatus;
    use App\Services\SubmissionLifecycleService;

    $isStaff = auth()->user()->isAdmin() || auth()->user()->isPair();
    $lifecycle = app(SubmissionLifecycleService::class);
    $allowed = $isStaff ? $lifecycle->allowedTransitions($submission, auth()->user()) : [];
    $current = $submission->lifecycle();
@endphp

@if($isStaff && count($allowed) > 0)
<div class="card syncro-card-elevated border-0" {{ $attributes }}>
    <div class="card-header bg-white border-bottom">
        <h5 class="mb-0 fw-bold"><i class="bi bi-arrow-left-right me-1"></i> Status management</h5>
    </div>
    <div class="card-body">
        <p class="text-muted small mb-2">
            Current: <x-submission-workflow-badge :submission="$submission" />
        </p>
        <div class="mb-3">
            <label for="lifecycleTransitionSelect-{{ $submission->id }}" class="form-label small fw-semibold">Move to</label>
            <select id="lifecycleTransitionSelect-{{ $submission->id }}" class="form-select form-select-sm">
                <option value="">— Select status —</option>
                @foreach($allowed as $status)
                    <option value="{{ $status->value }}">{{ $status->label() }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3" id="lifecycleNotesWrap-{{ $submission->id }}" style="display: none;">
            <label class="form-label small">Notes (optional)</label>
            <textarea id="lifecycleNotes-{{ $submission->id }}" class="form-control form-control-sm" rows="2" placeholder="Reason for status change…"></textarea>
        </div>
        <button type="button" class="btn btn-primary btn-sm" id="lifecycleApplyBtn-{{ $submission->id }}" disabled>
            Apply transition
        </button>
        <div id="lifecycleMessage-{{ $submission->id }}" class="small mt-2"></>
    </div>
</div>

@push('scripts')
<script>
(function () {
    const submissionId = @json($submission->id);
    const select = document.getElementById('lifecycleTransitionSelect-' + submissionId);
    const notesWrap = document.getElementById('lifecycleNotesWrap-' + submissionId);
    const notes = document.getElementById('lifecycleNotes-' + submissionId);
    const btn = document.getElementById('lifecycleApplyBtn-' + submissionId);
    const msg = document.getElementById('lifecycleMessage-' + submissionId);
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    select?.addEventListener('change', function () {
        btn.disabled = !this.value;
        notesWrap.style.display = this.value === 'revised' || this.value === 'rejected' ? 'block' : 'none';
    });

    btn?.addEventListener('click', async function () {
        const status = select.value;
        if (!status) return;

        if (status === 'revised' && (notes?.value?.trim().length || 0) < 10) {
            msg.className = 'small mt-2 text-danger';
            msg.textContent = 'Revised status requires at least 10 characters of notes.';
            return;
        }

        btn.disabled = true;
        msg.className = 'small mt-2 text-muted';
        msg.textContent = 'Updating…';

        try {
            const r = await fetch('/api/submissions/' + submissionId + '/transition', {
                method: 'POST',
                credentials: 'same-origin',
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
                body: JSON.stringify({ status: status, notes: notes?.value || null })
            });
            const data = await r.json();
            if (data.success) {
                msg.className = 'small mt-2 text-success';
                msg.textContent = data.message;
                window.dispatchEvent(new CustomEvent('submission-lifecycle-updated', { detail: data.data }));
                setTimeout(() => window.location.reload(), 600);
            } else {
                msg.className = 'small mt-2 text-danger';
                msg.textContent = data.message || 'Transition failed.';
                btn.disabled = false;
            }
        } catch (e) {
            msg.className = 'small mt-2 text-danger';
            msg.textContent = e.message;
            btn.disabled = false;
        }
    });
})();
</script>
@endpush
@endif
