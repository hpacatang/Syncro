@props(['submissionIds' => '', 'intervalMs' => 10000])

<script>
(function () {
    const ids = @json($submissionIds);
    const intervalMs = @json((int) $intervalMs);
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    let since = null;

    const stepColors = {
        pending: '#6c757d',
        submitted: '#22c55e',
        under_peer_review: '#f97316',
        approved: '#2563eb',
        rejected: '#ef4444',
        revised: '#f97316',
        posted: '#7c3aed',
    };

    const badgeLabel = {
        pending: 'Pending',
        submitted: 'Submitted',
        under_peer_review: 'Under Review',
        approved: 'Approved',
        rejected: 'Rejected',
        revised: 'Revised',
        posted: 'Posted',
    };

    function updateProgressBar(item) {
        const status = item.workflow_status || item.lifecycle_status;
        const progressIndex = typeof item.progress_index === 'number' ? item.progress_index : -1;

        document.querySelectorAll('[data-lifecycle-progress][data-submission-id="' + item.id + '"]').forEach(function (root) {
            root.setAttribute('data-workflow-status', status);
            root.setAttribute('data-progress-index', String(progressIndex));

            const isCompact = root.classList.contains('submission-lifecycle-progress--compact');
            const track = root.querySelector('.lifecycle-progress__track');
            const badgeWrap = root.querySelector('.lifecycle-progress__badge-wrap');
            let rejBlock = root.querySelector('.lifecycle-progress__rejected');

            if (status === 'rejected') {
                if (track) track.style.display = 'none';
                if (badgeWrap) badgeWrap.style.display = 'none';

                if (!rejBlock) {
                    rejBlock = document.createElement('div');
                    rejBlock.className = 'lifecycle-progress__rejected d-flex flex-column align-items-center justify-content-center ' + (isCompact ? 'py-1' : 'py-3') + ' text-center';
                    rejBlock.innerHTML = '<span class="badge bg-danger ' + (isCompact ? 'px-2 py-1' : 'fs-6 px-3 py-2 mb-2') + '"><i class="bi bi-x-circle me-1"></i> Rejected</span>' +
                        (isCompact ? '' : '<p class="text-danger small mb-0 fw-semibold">This submission was rejected and will not proceed.</p>');
                    root.appendChild(rejBlock);
                } else {
                    rejBlock.style.display = '';
                }
                return;
            }

            if (rejBlock) rejBlock.style.display = 'none';
            if (track) track.style.display = '';
            if (badgeWrap) badgeWrap.style.display = '';

            const steps = root.querySelectorAll('.lifecycle-step');
            steps.forEach(function (stepEl, index) {
                stepEl.classList.remove('is-done', 'is-active', 'is-upcoming');
                if (progressIndex > index) {
                    stepEl.classList.add('is-done');
                } else if (progressIndex === index) {
                    stepEl.classList.add('is-active');
                } else {
                    stepEl.classList.add('is-upcoming');
                }
            });
        });
    }

    function updateDom(item) {
        const status = item.workflow_status || item.lifecycle_status;
        const color = item.progress_color || stepColors[status] || '#6c757d';
        const label = item.lifecycle_label || badgeLabel[status] || status;

        document.querySelectorAll('[data-lifecycle-badge][data-submission-id="' + item.id + '"]').forEach(function (el) {
            const badge = el.classList.contains('badge') ? el : el.querySelector('.badge');
            const target = badge || el;
            target.textContent = label;
            target.style.backgroundColor = color;
            target.style.borderColor = color;
            if (!target.classList.contains('text-dark') && (status === 'submitted' || status === 'revised')) {
                target.classList.add('text-dark');
            }
        });

        document.querySelectorAll('[data-submission-row="' + item.id + '"]').forEach(function (row) {
            row.setAttribute('data-workflow-status', status);
        });

        updateProgressBar(item);
        window.dispatchEvent(new CustomEvent('submission-lifecycle-updated', { detail: item }));
    }

    async function poll() {
        try {
            let url = '/api/submissions/lifecycle-updates';
            const params = new URLSearchParams();
            if (since) params.set('since', since);
            if (ids) params.set('ids', ids);
            if (params.toString()) url += '?' + params.toString();

            const r = await fetch(url, {
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf || '' }
            });
            if (!r.ok) return;

            const payload = await r.json();
            if (!payload.success || !Array.isArray(payload.data)) return;

            if (payload.server_time) since = payload.server_time;
            payload.data.forEach(updateDom);
        } catch (e) {
            console.debug('Lifecycle poll:', e.message);
        }
    }

    poll();
    setInterval(poll, intervalMs);
})();
</script>
