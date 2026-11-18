<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Syncro')</title>
    <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}">
    <script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>
    @vite(['resources/css/vendor-icons.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/syncro.css') }}">
</head>
<body class="syncro-app">
<div id="syncroNavigationProgress" class="syncro-navigation-progress" role="progressbar" aria-label="Loading page" aria-valuemin="0" aria-valuemax="100" hidden></div>
@auth
    @php
        $isOrg = auth()->user()->isOrg();
        $isDepartment = auth()->user()->isDepartment();
        $dashRoute = $isOrg ? 'org.dashboard' : 'dashboard';
        $subRoute = $isOrg ? 'org.submissions' : 'dashboard.submissions';
        $notifRoute = $isOrg ? 'org.notifications' : 'dashboard.notifications';
        $workspaceItems = [
            ['route' => $dashRoute, 'match' => [$dashRoute], 'icon' => 'bi-grid-1x2-fill', 'label' => 'Dashboard'],
            ['route' => $subRoute, 'match' => [$subRoute, 'dashboard.submissions', 'org.submissions', 'org.submit'], 'icon' => 'bi-folder2-open', 'label' => $isOrg ? 'My submissions' : ($isDepartment ? 'Review submissions' : 'Submission queue')],
            ['route' => $notifRoute, 'match' => [$notifRoute], 'icon' => 'bi-bell', 'label' => 'Notifications'],
        ];
        $adminItems = [];
        if (auth()->user()->role === 'super_admin') {
            $adminItems[] = ['route' => 'audit-logs.index', 'match' => ['audit-logs.*'], 'icon' => 'bi-journal-text', 'label' => 'Audit logs'];
            $adminItems[] = ['route' => 'departments.index', 'match' => ['departments.*'], 'icon' => 'bi-building', 'label' => 'Departments'];
            $adminItems[] = ['route' => 'users.index', 'match' => ['users.*'], 'icon' => 'bi-people', 'label' => 'Users'];
        }
        if (auth()->user()->isAdmin()) {
            $adminItems[] = ['route' => 'account-requests.index', 'match' => ['account-requests.*'], 'icon' => 'bi-person-check', 'label' => 'Account requests'];
        }
        $departmentItems = [];
        if (auth()->user()->isDepartment()) {
            $departmentItems[] = ['route' => 'department.users.index', 'match' => ['department.users.*'], 'icon' => 'bi-people', 'label' => 'Department users'];
            $departmentItems[] = ['route' => 'department.submit', 'match' => ['department.submit'], 'icon' => 'bi-send', 'label' => 'Submit to PAIR'];
        }
        $navSections = [['label' => 'Workspace', 'items' => $workspaceItems]];
        if ($adminItems !== []) {
            $navSections[] = ['label' => 'Administration', 'items' => $adminItems];
        }
        if ($departmentItems !== []) {
            $navSections[] = ['label' => 'Department', 'items' => $departmentItems];
        }
    @endphp

    <div class="syncro-shell">
        <aside id="sidebar" class="syncro-rail" aria-label="Main navigation">
            <a href="{{ route($dashRoute) }}" class="syncro-rail__brand" aria-label="Syncro home">
                <i class="bi bi-arrow-repeat" aria-hidden="true"></i><span>SYNCRO</span>
            </a>
            <nav class="syncro-rail__nav">
                @foreach($navSections as $section)
                    <span class="syncro-rail__section-label">{{ $section['label'] }}</span>
                    @foreach($section['items'] as $item)
                        @php
                            $active = false;
                            foreach ($item['match'] as $pattern) {
                                if (request()->routeIs($pattern)) {
                                    $active = true;
                                    break;
                                }
                            }
                        @endphp
                        <a
                            href="{{ route($item['route']) }}"
                            class="syncro-rail__link {{ $active ? 'is-active' : '' }}"
                            title="{{ $item['label'] }}"
                            @if($active) aria-current="page" @endif
                        >
                            <span class="syncro-rail__link-bg" aria-hidden="true"></span>
                            <i class="bi {{ $item['icon'] }} syncro-rail__icon"></i>
                            <span class="syncro-rail__label">{{ $item['label'] }}</span>
                        </a>
                    @endforeach

                    @if($loop->first && auth()->user()->isStaffReviewer())
                        {{-- Calendar menu toggle button --}}
                        <button
                            type="button"
                            class="syncro-rail__link is-active"
                            id="sidebarCalendarToggle"
                            aria-expanded="true"
                            title="Calendar"
                            style="background: transparent; border: none; width: 100%; text-align: left; cursor: pointer;"
                        >
                            <span class="syncro-rail__link-bg" aria-hidden="true"></span>
                            <i class="bi bi-calendar3 syncro-rail__icon"></i>
                            <span class="syncro-rail__label">Calendar</span>
                            <i class="bi bi-chevron-down ms-auto pe-1" id="calChevron" style="font-size: 0.72rem; opacity: 0.75; transition: transform 0.25s ease; transform: rotate(180deg);"></i>
                        </button>

                        {{-- Collapsible Calendar Container (Visible by default) --}}
                        <div id="sidebarCalendarContainer" class="syncro-rail-calendar-container">
                            <div class="syncro-rail-calendar px-1 pb-1" id="pairSideCalendar">
                                <div class="syncro-rail-calendar__header">
                                    <button type="button" class="syncro-rail-calendar__nav" id="calPrevBtn" aria-label="Previous month" title="Previous month">
                                        <i class="bi bi-chevron-left"></i>
                                    </button>
                                    <span class="syncro-rail-calendar__month" id="calMonthLabel"></span>
                                    <button type="button" class="syncro-rail-calendar__nav" id="calNextBtn" aria-label="Next month" title="Next month">
                                        <i class="bi bi-chevron-right"></i>
                                    </button>
                                </div>
                                <div class="syncro-rail-calendar__grid" id="calGrid" role="grid" aria-label="Scheduled posts calendar">
                                    {{-- Populated by JS --}}
                                </div>
                            </div>
                        </div>
                    @endif
                @endforeach
            </nav>

            <div class="syncro-rail__footer">
                <a href="{{ route('profile') }}" class="syncro-rail__profile" title="My profile">
                    @if(Auth::user()->profilePhotoUrl())
                        <img src="{{ Auth::user()->profilePhotoUrl() }}" class="syncro-rail__avatar-image" alt="">
                    @else
                        <span class="syncro-rail__avatar">{{ strtoupper(substr(Auth::user()->displayName(), 0, 1)) }}</span>
                    @endif
                    <span class="syncro-rail__profile-text"><strong>{{ Auth::user()->displayName() }}</strong><small>My profile</small></span>
                </a>
            </div>
        </aside>

        <div class="syncro-stage">
            <header class="syncro-topbar">
                <button type="button" class="btn syncro-topbar__menu d-lg-none" id="sidebarToggle" aria-label="Open menu">
                    <i class="bi bi-list"></i>
                </button>
                <div class="syncro-topbar__title">
                    <h1 class="syncro-topbar__heading mb-0">@yield('page-title', 'Syncro')</h1>
                    @hasSection('page-subtitle')
                        <p class="syncro-topbar__subtitle mb-0">@yield('page-subtitle')</p>
                    @endif
                </div>
                <div class="syncro-topbar__actions">
                    <x-notifications />
                    <div class="dropdown">
                        <button class="btn syncro-topbar__user dropdown-toggle" type="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <span class="syncro-topbar__user-name">{{ Auth::user()->displayName() }}</span>
                            <span class="syncro-topbar__user-role">{{ Auth::user()->roleLabel() }}</span>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end shadow">
                            <li class="px-3 py-2 border-bottom">
                                <div class="fw-semibold">{{ Auth::user()->displayName() }}</div>
                                <div class="small text-muted">{{ Auth::user()->roleLabel() }}</div>
                            </li>
                            <li><a class="dropdown-item" href="{{ route('profile') }}"><i class="bi bi-person-circle me-2"></i> My profile</a></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item">Sign out</button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>
            </header>

            <main class="syncro-main" id="main-content">
                @if(\App\Support\BackNavigation::shouldShow())
                    <div class="syncro-back-bar">
                        <x-back-button />
                    </div>
                @endif
                @yield('content')
            </main>
        </div>
    </div>

    <div class="syncro-rail-backdrop d-lg-none" id="sidebarBackdrop" hidden></div>
@else
    <main class="syncro-main syncro-main--guest" id="main-content">
        @yield('content')
    </main>
@endauth

<script>
(function () {
    var progress = document.getElementById('syncroNavigationProgress');
    if (!progress) return;

    document.addEventListener('click', function (event) {
        var link = event.target.closest('a[href]');
        if (!link || link.target || link.hasAttribute('download')
            || link.dataset.noNavProgress !== undefined
            || event.defaultPrevented
            || event.button !== 0
            || event.metaKey || event.ctrlKey || event.shiftKey || event.altKey) return;

        var destination = new URL(link.href, window.location.href);
        if (destination.origin !== window.location.origin
            || (destination.pathname === window.location.pathname
                && destination.search === window.location.search
                && destination.hash)) return;

        progress.hidden = false;
        progress.classList.add('is-active');
    });

    document.addEventListener('submit', function (event) {
        if (event.defaultPrevented || event.target.matches('[data-no-nav-progress]')) return;
        progress.hidden = false;
        progress.classList.add('is-active');
    });

    window.addEventListener('pageshow', function () {
        progress.classList.remove('is-active');
        progress.hidden = true;
    });
})();
</script>
<script>
(function () {
    var toggle = document.getElementById('sidebarToggle');
    var sidebar = document.getElementById('sidebar');
    var backdrop = document.getElementById('sidebarBackdrop');
    if (!toggle || !sidebar) return;

    function closeSidebar() {
        sidebar.classList.remove('is-open');
        if (backdrop) backdrop.hidden = true;
        document.body.classList.remove('syncro-nav-open');
    }

    function openSidebar() {
        sidebar.classList.add('is-open');
        if (backdrop) backdrop.hidden = false;
        document.body.classList.add('syncro-nav-open');
    }

    toggle.addEventListener('click', function () {
        sidebar.classList.contains('is-open') ? closeSidebar() : openSidebar();
    });

    backdrop?.addEventListener('click', closeSidebar);
})();
</script>
<script>
(function () {
    var timers = new WeakMap();

    document.addEventListener('input', function (event) {
        var search = event.target.closest('[data-debounce-search-field]');
        if (!search) return;

        var form = search.form;
        if (!form || search.value === search.dataset.submittedValue) return;

        window.clearTimeout(timers.get(form));
        var delay = Number(form.dataset.debounceSearch) || 450;
        timers.set(form, window.setTimeout(function () {
            search.dataset.submittedValue = search.value;
            form.requestSubmit();
        }, delay));
    });

    document.addEventListener('submit', function (event) {
        if (event.target.matches('[data-debounce-search]')) {
            window.clearTimeout(timers.get(event.target));
        }
    }, true);
})();
</script>
<script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>
@auth
@if(auth()->user()->isStaffReviewer())
{{-- ===== Calendar Day Detail Modal ===== --}}
<div class="modal fade" id="calDayModal" tabindex="-1" aria-labelledby="calDayModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius:14px;overflow:hidden;">
            <div class="modal-header border-0 text-white" style="background-color:#1a4d2e;padding:1rem 1.4rem;">
                <h5 class="modal-title fw-bold mb-0 d-flex align-items-center gap-2" id="calDayModalLabel">
                    <i class="bi bi-calendar3"></i>
                    <span id="calDayModalTitle">Scheduled Posts</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body bg-light p-3" id="calDayModalBody" style="min-height:140px;">
                <div class="text-center py-5 text-muted" id="calDayModalLoading">
                    <span class="spinner-border spinner-border-sm me-2 text-success" role="status"></span> Loading scheduled posts…
                </div>
            </div>
            <div class="modal-footer border-top bg-white" style="padding:0.75rem 1.4rem;">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    const toggleBtn  = document.getElementById('sidebarCalendarToggle');
    const container  = document.getElementById('sidebarCalendarContainer');
    const chevron    = document.getElementById('calChevron');
    const grid       = document.getElementById('calGrid');
    const monthLbl   = document.getElementById('calMonthLabel');
    const prevBtn    = document.getElementById('calPrevBtn');
    const nextBtn    = document.getElementById('calNextBtn');

    if (!grid || !monthLbl) return;

    const MONTH_NAMES = ['JAN','FEB','MAR','APR','MAY','JUN','JUL','AUG','SEP','OCT','NOV','DEC'];
    const DAY_NAMES   = ['SU','MO','TU','WE','TH','FR','SA'];

    let cur = new Date();
    let viewYear  = cur.getFullYear();
    let viewMonth = cur.getMonth() + 1; // 1-based
    let dotCache  = {};

    function pad(n) { return String(n).padStart(2, '0'); }

    // 1. Build calendar grid SYNCHRONOUSLY so it renders instantly with zero blank wait
    function buildGrid(year, month) {
        monthLbl.textContent = MONTH_NAMES[month - 1] + ' ' + year;
        grid.innerHTML = '';

        DAY_NAMES.forEach(d => {
            const el = document.createElement('div');
            el.className = 'syncro-rail-calendar__day-name';
            el.textContent = d;
            grid.appendChild(el);
        });

        const firstDay = new Date(year, month - 1, 1).getDay();
        const daysInMonth = new Date(year, month, 0).getDate();

        for (let i = 0; i < firstDay; i++) {
            const el = document.createElement('div');
            el.className = 'syncro-rail-calendar__day empty';
            el.setAttribute('aria-hidden', 'true');
            grid.appendChild(el);
        }

        for (let d = 1; d <= daysInMonth; d++) {
            const el = document.createElement('div');
            const thisDayStr = year + '-' + pad(month) + '-' + pad(d);
            const isToday = (year === cur.getFullYear() && (month - 1) === cur.getMonth() && d === cur.getDate());

            el.className = 'syncro-rail-calendar__day' + (isToday ? ' is-today' : '');
            el.dataset.dayNum = String(d);
            el.dataset.calDate = thisDayStr;
            el.setAttribute('role', 'gridcell');
            el.setAttribute('title', MONTH_NAMES[month - 1] + ' ' + d + ', ' + year);
            el.setAttribute('tabindex', '0');

            const numSpan = document.createElement('span');
            numSpan.className = 'syncro-rail-calendar__num';
            numSpan.textContent = d;
            el.appendChild(numSpan);

            el.addEventListener('click', openDayModal);
            el.addEventListener('keydown', function (ev) {
                if (ev.key === 'Enter' || ev.key === ' ') {
                    ev.preventDefault();
                    openDayModal({ currentTarget: el });
                }
            });

            grid.appendChild(el);
        }
    }

    // 2. Fetch scheduled post dots asynchronously and apply markers
    async function applyDots(year, month) {
        const key = year + '-' + month;
        let dots = dotCache[key];
        if (!dots) {
            try {
                const res = await fetch('/api/submissions/calendar?year=' + year + '&month=' + month, {
                    credentials: 'same-origin',
                    headers: { 'Accept': 'application/json' }
                });
                if (res.ok) {
                    const data = await res.json();
                    dots = data.days || {};
                    dotCache[key] = dots;
                }
            } catch (err) {
                dots = {};
            }
        }
        dots = dots || {};

        if (year !== viewYear || month !== viewMonth) return;

        const today = new Date();
        today.setHours(0, 0, 0, 0);

        Object.keys(dots).forEach(d => {
            const info = dots[d];
            const count = (typeof info === 'object' && info !== null) ? info.count : Number(info);
            if (count > 0) {
                const dayEl = grid.querySelector('[data-day-num="' + d + '"]');
                if (dayEl) {
                    dayEl.classList.add('has-posts');
                    let dot = dayEl.querySelector('.syncro-rail-calendar__dot');
                    if (!dot) {
                        dot = document.createElement('span');
                        dot.className = 'syncro-rail-calendar__dot';
                        dot.setAttribute('aria-hidden', 'true');
                        dayEl.appendChild(dot);
                    }

                    const thisDate = new Date(year, month - 1, parseInt(d, 10));
                    thisDate.setHours(0, 0, 0, 0);

                    const isPast = thisDate < today || ((typeof info === 'object' && info !== null) ? Boolean(info.is_past) : false);
                    const isPosted = (typeof info === 'object' && info !== null) ? (Boolean(info.is_posted) || Boolean(info.has_posted)) : false;

                    if (isPast || isPosted) {
                        dot.classList.add('syncro-rail-calendar__dot--red');
                    } else {
                        dot.classList.remove('syncro-rail-calendar__dot--red');
                    }

                    const statusDesc = (isPast || isPosted) ? 'posted / past' : 'scheduled';
                    dayEl.setAttribute('title', MONTH_NAMES[month - 1] + ' ' + d + ', ' + year + ' (' + count + ' ' + statusDesc + ')');
                }
            }
        });
    }

    function renderCalendar() {
        buildGrid(viewYear, viewMonth);
        applyDots(viewYear, viewMonth);
    }

    // Toggle calendar in sidebar
    if (toggleBtn && container) {
        function setCalendarOpen(open) {
            if (open) {
                container.style.display = 'block';
                toggleBtn.classList.add('is-active');
                if (chevron) chevron.style.transform = 'rotate(180deg)';
                toggleBtn.setAttribute('aria-expanded', 'true');
            } else {
                container.style.display = 'none';
                toggleBtn.classList.remove('is-active');
                if (chevron) chevron.style.transform = 'rotate(0deg)';
                toggleBtn.setAttribute('aria-expanded', 'false');
            }
        }

        toggleBtn.addEventListener('click', function (e) {
            e.preventDefault();
            const isCurrentlyOpen = container.style.display !== 'none';
            setCalendarOpen(!isCurrentlyOpen);
        });

        // Always ensure calendar is open by default
        setCalendarOpen(true);
    }

    // Initial render
    renderCalendar();

    // Month Navigation
    if (prevBtn) {
        prevBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            viewMonth--;
            if (viewMonth < 1) { viewMonth = 12; viewYear--; }
            renderCalendar();
        });
    }
    if (nextBtn) {
        nextBtn.addEventListener('click', function (e) {
            e.stopPropagation();
            viewMonth++;
            if (viewMonth > 12) { viewMonth = 1; viewYear++; }
            renderCalendar();
        });
    }

    // --- Modal ---
    const modalEl   = document.getElementById('calDayModal');
    const modalBody = document.getElementById('calDayModalBody');
    const modalTitle = document.getElementById('calDayModalTitle');
    const loadingEl  = document.getElementById('calDayModalLoading');

    function getModal() {
        if (!modalEl) return null;
        if (typeof bootstrap !== 'undefined' && bootstrap.Modal) {
            try { return bootstrap.Modal.getOrCreateInstance(modalEl); } catch (e) {}
        }
        return null;
    }

    async function openDayModal(e) {
        const dateStr = e.currentTarget.dataset.calDate;
        if (!dateStr) return;

        const parts = dateStr.split('-');
        const monthIdx = parseInt(parts[1], 10) - 1;
        const displayDate = MONTH_NAMES[monthIdx] + ' ' + parseInt(parts[2], 10) + ', ' + parts[0];

        if (modalTitle) modalTitle.textContent = 'Scheduled Posts — ' + displayDate;
        if (modalBody) {
            modalBody.innerHTML = '';
            if (loadingEl) {
                const l = loadingEl.cloneNode(true);
                l.style.display = '';
                modalBody.appendChild(l);
            }
        }

        const bsModal = getModal();
        if (bsModal) bsModal.show();

        try {
            const res = await fetch('/api/submissions/calendar/day?date=' + dateStr, {
                credentials: 'same-origin',
                headers: { 'Accept': 'application/json' }
            });
            const data = await res.json();

            if (!modalBody) return;
            modalBody.innerHTML = '';

            if (!data.success || !data.posts || data.posts.length === 0) {
                modalBody.innerHTML =
                    '<div class="text-center py-5 text-muted">' +
                        '<i class="bi bi-calendar-x fs-1 text-secondary opacity-50 d-block mb-3"></i>' +
                        '<h6 class="fw-semibold text-dark mb-1">No posts scheduled for this day</h6>' +
                        '<p class="small text-muted mb-0">' + displayDate + ' has no scheduled publications yet.</p>' +
                    '</div>';
                return;
            }

            const wrap = document.createElement('div');
            wrap.className = 'p-1';

            data.posts.forEach(function (post) {
                const card = document.createElement('div');
                card.className = 'card border-0 shadow-sm mb-3';
                card.style.borderRadius = '10px';
                card.style.overflow = 'hidden';
                card.style.backgroundColor = '#ffffff';

                card.innerHTML =
                    '<div class="card-header bg-white border-bottom d-flex justify-content-between align-items-center py-2 px-3">' +
                        '<div class="d-flex align-items-center gap-2 flex-wrap">' +
                            '<span class="badge bg-light text-dark border">' + escHtml(post.content_type) + '</span>' +
                            '<span class="badge bg-success bg-opacity-75 text-white">' + escHtml(post.workflow_status) + '</span>' +
                            '<span class="text-muted small">#' + escHtml(post.id) + '</span>' +
                        '</div>' +
                        '<div class="text-end">' +
                            '<a href="' + escHtml(post.review_url) + '" class="btn btn-sm btn-primary d-inline-flex align-items-center py-1 px-3" style="font-size:0.82rem;">' +
                                '<i class="bi bi-eye me-1"></i> View' +
                            '</a>' +
                            '<div class="text-muted small mt-1" style="font-size:0.72rem;">' +
                                '<i class="bi bi-clock-history me-1"></i>Last Updated: <strong>' + escHtml(post.updated_at || '—') + '</strong>' +
                            '</div>' +
                        '</div>' +
                    '</div>' +
                    '<div class="card-body p-3">' +
                        '<p class="mb-3 fw-semibold text-dark" style="font-size:0.92rem; white-space:pre-line;">' + escHtml(post.caption) + '</p>' +
                        '<div class="row g-2 pt-2 border-top small">' +
                            '<div class="col-sm-6">' +
                                '<span class="text-muted d-block"><i class="bi bi-calendar-event text-primary me-1"></i>Scheduled Time &amp; Date:</span>' +
                                '<span class="fw-bold text-dark ps-3">' + escHtml(post.scheduled_at || '—') + '</span>' +
                            '</div>' +
                            '<div class="col-sm-6">' +
                                '<span class="text-muted d-block"><i class="bi bi-person-check text-success me-1"></i>Assigned Publisher:</span>' +
                                '<span class="fw-bold text-dark ps-3">' + escHtml(post.assigned_publisher || '—') + '</span>' +
                            '</div>' +
                            '<div class="col-sm-6">' +
                                '<span class="text-muted d-block"><i class="bi bi-person text-secondary me-1"></i>Schedule Creator:</span>' +
                                '<span class="fw-bold text-dark ps-3">' + escHtml(post.schedule_creator || '—') + '</span>' +
                            '</div>' +
                            '<div class="col-sm-6">' +
                                '<span class="text-muted d-block"><i class="bi bi-pencil-square text-info me-1"></i>Last Updated:</span>' +
                                '<span class="fw-bold text-dark ps-3">' + escHtml(post.updated_at || '—') + '</span>' +
                            '</div>' +
                        '</div>' +
                    '</div>';

                wrap.appendChild(card);
            });

            modalBody.appendChild(wrap);
        } catch (err) {
            if (modalBody) {
                modalBody.innerHTML = '<div class="text-center py-4 text-danger small"><i class="bi bi-exclamation-triangle me-1"></i>Failed to load posts. Please try again.</div>';
            }
        }
    }

    function escHtml(str) {
        if (!str) return '';
        return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
    }
})();
</script>
@endif
@endauth

@stack('scripts')
</body>
</html>
