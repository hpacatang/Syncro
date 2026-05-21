@props(['submission'])

@php
    use App\Services\SubmissionLifecycleService;

    $isStaff = auth()->user()->isAdmin() || auth()->user()->isPair();
    $allowed = $isStaff ? app(SubmissionLifecycleService::class)->allowedTransitions($submission, auth()->user()) : [];
@endphp

@if($isStaff && count($allowed) > 0)
<div class="submission-status-inline d-flex flex-wrap gap-1 align-items-center" data-submission-id="{{ $submission->id }}">
    <select class="form-select form-select-sm lifecycle-inline-select" style="min-width: 10rem;" aria-label="Change status">
        <option value="">Status…</option>
        @foreach($allowed as $status)
            <option value="{{ $status->value }}">{{ $status->label() }}</option>
        @endforeach
    </select>
    <button type="button" class="btn btn-sm btn-outline-primary lifecycle-inline-apply" title="Apply status">Apply</button>
    <span class="lifecycle-inline-msg small text-muted"></span>
</div>

@once
@push('scripts')
<script>
document.addEventListener('click', async function (e) {
    const btn = e.target.closest('.lifecycle-inline-apply');
    if (!btn) return;

    const wrap = btn.closest('.submission-status-inline');
    const select = wrap?.querySelector('.lifecycle-inline-select');
    const msg = wrap?.querySelector('.lifecycle-inline-msg');
    const submissionId = wrap?.dataset.submissionId;
    const status = select?.value;
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');

    if (!status || !submissionId) {
        if (msg) msg.textContent = 'Pick a status first.';
        return;
    }

    btn.disabled = true;
    if (msg) msg.textContent = 'Saving…';

    try {
        const r = await fetch('/api/submissions/' + submissionId + '/transition', {
            method: 'POST',
            credentials: 'same-origin',
            headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf },
            body: JSON.stringify({ status: status })
        });
        const data = await r.json();
        if (data.success) {
            if (msg) { msg.className = 'lifecycle-inline-msg small text-success'; msg.textContent = 'Updated'; }
            window.dispatchEvent(new CustomEvent('submission-lifecycle-updated', { detail: data.data }));
            setTimeout(() => window.location.reload(), 500);
        } else {
            if (msg) { msg.className = 'lifecycle-inline-msg small text-danger'; msg.textContent = data.message || 'Failed'; }
            btn.disabled = false;
        }
    } catch (err) {
        if (msg) { msg.className = 'lifecycle-inline-msg small text-danger'; msg.textContent = err.message; }
        btn.disabled = false;
    }
});
</script>
@endpush
@endonce
@endif
