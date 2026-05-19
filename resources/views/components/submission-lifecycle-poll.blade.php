@props(['submissionIds' => '', 'intervalMs' => 10000])

<script>
(function () {
    const ids = @json($submissionIds);
    const intervalMs = @json((int) $intervalMs);
    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
    let since = null;
    let initialPollDone = false;

    const badgeLabel = {
        pending: 'Pending',
        submitted: 'Submitted',
        under_peer_review: 'Under Peer Review',
        awaiting_org_approval: 'Awaiting Org Approval',
        approved: 'Approved',
        rejected: 'Rejected',
        revised: 'Revised',
        posted: 'Posted',
    };

    const badgeClass = {
        pending: 'secondary',
        submitted: 'info',
        under_peer_review: 'warning',
        awaiting_org_approval: 'primary',
        approved: 'success',
        rejected: 'danger',
        revised: 'warning',
        posted: 'dark',
    };

    function updateDom(item) {
        document.querySelectorAll('[data-lifecycle-badge][data-submission-id="' + item.id + '"]').forEach(function (el) {
            el.className = 'badge bg-' + (item.badge_class || badgeClass[item.workflow_status] || 'secondary');
            if (el.classList.contains('fs-6')) {
                el.classList.add('fs-6', 'px-3', 'py-2');
            }
            el.textContent = item.lifecycle_label || badgeLabel[item.workflow_status] || item.workflow_status;
        });

        document.querySelectorAll('[data-submission-row="' + item.id + '"]').forEach(function (row) {
            row.setAttribute('data-workflow-status', item.workflow_status);
        });

        window.dispatchEvent(new CustomEvent('submission-lifecycle-updated', { detail: item }));
    }

    async function poll() {
        try {
            let url = '/api/submissions/lifecycle-updates';
            const params = new URLSearchParams();
            if (since) {
                params.set('since', since);
            }
            if (ids) {
                params.set('ids', ids);
            }
            if (params.toString()) {
                url += '?' + params.toString();
            }

            const r = await fetch(url, {
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf || '' }
            });

            if (!r.ok) return;

            const payload = await r.json();
            if (!payload.success || !Array.isArray(payload.data)) return;

            if (payload.server_time) {
                since = payload.server_time;
            }

            if (!initialPollDone) {
                initialPollDone = true;
            }

            payload.data.forEach(updateDom);
        } catch (e) {
            console.debug('Lifecycle poll:', e.message);
        }
    }

    poll();
    setInterval(poll, intervalMs);
})();
</script>
