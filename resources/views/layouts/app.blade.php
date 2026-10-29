<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Syncro')</title>
    <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}">
    @vite(['resources/css/vendor-icons.css', 'resources/js/app.js'])
    <link rel="stylesheet" href="{{ asset('css/syncro.css') }}">
</head>
<body class="syncro-app">
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
@stack('scripts')
</body>
</html>
