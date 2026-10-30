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

            const badge = root.querySelector('[data-lifecycle-badge]');
            if (badge) {
                const color = item.progress_color || stepColors[status] || '#16a34a';
                const label = item.lifecycle_label || badgeLabel[status] || status;
                badge.textContent = label;
                badge.style.backgroundColor = color;
            }
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

        document.querySelectorAll('.syncro-queue-actions[data-submission-id="' + item.id + '"]').forEach(function (wrap) {
            const btn = wrap.querySelector('.syncro-queue-actions__btn');
            const label = wrap.querySelector('.syncro-queue-actions__label');
            if (btn && label) {
                if (status === 'rejected') {
                    btn.classList.remove('btn-primary');
                    btn.classList.add('btn-outline-primary');
                    btn.title = 'View submission';
                    label.textContent = 'View';
                } else {
                    btn.classList.remove('btn-outline-primary');
                    btn.classList.add('btn-primary');
                    btn.title = 'Review submission details';
                    label.textContent = 'Review';
                }
            }
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
