@props(['pollInterval' => 45])

@auth
    @php
        $notifListRoute = auth()->user()->canSubmitPosts() ? route('org.notifications') : route('dashboard.notifications');
        $initialUnread = auth()->user()->unreadNotifications()->count();
    @endphp

    <div class="dropdown syncro-notif-wrap">
        <button
            type="button"
            class="btn syncro-notif-btn position-relative"
            id="syncroNotifToggle"
            data-bs-toggle="dropdown"
            aria-expanded="false"
            aria-label="Notifications">
            <i class="bi bi-bell"></i>
            <span id="syncroNotifBadge" class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger {{ $initialUnread === 0 ? 'd-none' : '' }}" data-count="{{ $initialUnread }}">
                {{ $initialUnread > 99 ? '99+' : $initialUnread }}
            </span>
        </button>
        <ul class="dropdown-menu dropdown-menu-end shadow syncro-notif-dropdown" aria-labelledby="syncroNotifToggle">
            <li class="px-3 py-2 border-bottom d-flex justify-content-between align-items-center">
                <span class="fw-semibold small">Notifications</span>
                <button type="button" class="btn btn-link btn-sm p-0 small" id="syncroNotifMarkAll">Mark all read</button>
            </li>
            <li class="p-0 border-0">
                <div id="syncroNotifList">
                    <div class="text-center text-muted small py-3">Loading…</div>
                </div>
            </li>
            <li class="border-top"><a class="dropdown-item text-center small" href="{{ $notifListRoute }}">View all</a></li>
        </ul>
    </div>
@endauth

@auth
    @once
        @push('scripts')
            <script>
                (function () {
                    const pollMs = {{ (int) $pollInterval }} * 1000;
                    const csrf = document.querySelector('meta[name="csrf-token"]')?.getAttribute('content');
                    const badge = document.getElementById('syncroNotifBadge');
                    const list = document.getElementById('syncroNotifList');
                    const markAll = document.getElementById('syncroNotifMarkAll');
                    const toggle = document.getElementById('syncroNotifToggle');
                    const readBase = @json(url('/notifications'));

                    function setBadge(count) {
                        if (!badge) return;
                        const n = Math.max(0, parseInt(count, 10) || 0);
                        badge.dataset.count = String(n);
                        if (n === 0) {
                            badge.classList.add('d-none');
                            return;
                        }
                        badge.classList.remove('d-none');
                        badge.textContent = n > 99 ? '99+' : String(n);
                    }

                    async function refreshCount() {
                        try {
                            const r = await fetch(@json(route('notifications.unread-count')), { credentials: 'same-origin' });
                            const j = await r.json();
                            setBadge(j.count);
                        } catch (e) {  }
                    }

                    function escapeHtml(s) {
                        return String(s).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
                    }

                    function renderList(items) {
                        if (!list) return;
                        if (!items.length) {
                            list.innerHTML = '<div class="text-center text-muted small py-3">No notifications</div>';
                            return;
                        }
                        list.innerHTML = items.map(function (n) {
                            const unread = !n.read;
                            const cls = unread ? 'syncro-notif-item--unread' : '';
                            const href = n.url || '#';
                            return '<a class="dropdown-item small ' + cls + '" href="' + href + '" data-notif-id="' + n.id + '">' +
                                '<div class="fw-semibold">' + escapeHtml(n.title) + '</div>' +
                                '<div class="text-muted text-truncate" style="max-width:280px">' + escapeHtml(n.message) + '</div>' +
                                '</a>';
                        }).join('');
                    }

                    async function loadRecent() {
                        try {
                            const r = await fetch(@json(route('notifications.recent')), { credentials: 'same-origin' });
                            const j = await r.json();
                            renderList(j.notifications || []);
                        } catch (e) {
                            if (list) list.innerHTML = '<div class="text-danger small px-3 py-2">Could not load</div>';
                        }
                    }

                    document.addEventListener('click', async function (e) {
                        const a = e.target.closest('a[data-notif-id]');
                        if (!a) return;

                        const href = a.getAttribute('href');
                        if (!href || href === '#') return;

                        e.preventDefault();
                        e.stopPropagation();

                        const id = a.getAttribute('data-notif-id');
                        if (id && csrf) {
                            try {
                                await fetch(readBase + '/' + encodeURIComponent(id) + '/read', {
                                    method: 'POST',
                                    credentials: 'same-origin',
                                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
                                });
                                await refreshCount();
                            } catch (err) {  }
                        }

                        window.location.assign(href);
                    });

                    if (markAll) {
                        markAll.addEventListener('click', async function (ev) {
                            ev.preventDefault();
                            if (!csrf) return;
                            try {
                                await fetch(@json(route('notifications.read-all')), {
                                    method: 'POST',
                                    credentials: 'same-origin',
                                    headers: { 'X-CSRF-TOKEN': csrf, 'Accept': 'application/json' }
                                });
                                setBadge(0);
                                await loadRecent();
                            } catch (err) {  }
                        });
                    }

                    if (toggle) {
                        toggle.addEventListener('shown.bs.dropdown', function () {
                            loadRecent();
                            refreshCount();
                        });
                    }

                    setInterval(refreshCount, pollMs);
                    document.addEventListener('visibilitychange', function () {
                        if (document.visibilityState === 'visible') refreshCount();
                    });
                })();
</script>
        @endpush
    @endonce
@endauth
