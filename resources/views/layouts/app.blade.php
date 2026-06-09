<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Syncro')</title>
    <!-- <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css"> -->
     <link rel="stylesheet" href="{{ asset('css/bootstrap.min.css') }}">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">
    <link rel="stylesheet" href="{{ asset('css/syncro.css') }}">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="syncro-app">
@auth
    @php
        $isSubmitter = auth()->user()->canSubmitPosts();
        $dashRoute = $isSubmitter ? 'org.dashboard' : 'dashboard';
        $subRoute = $isSubmitter ? 'org.submit' : 'dashboard.submissions';
        $notifRoute = $isSubmitter ? 'org.notifications' : 'dashboard.notifications';
        $navItems = [
            ['route' => $dashRoute, 'match' => [$dashRoute], 'icon' => 'bi-grid-1x2-fill', 'label' => 'Dashboard'],
            ['route' => $subRoute, 'match' => [$subRoute, 'dashboard.submissions', 'org.submissions', 'org.submit'], 'icon' => 'bi-folder2-open', 'label' => 'Submissions'],
            ['route' => $notifRoute, 'match' => [$notifRoute], 'icon' => 'bi-bell', 'label' => 'Notifications'],
        ];
        if (auth()->user()->role === 'super_admin') {
            $navItems[] = ['route' => 'audit-logs.index', 'match' => ['audit-logs.*'], 'icon' => 'bi-journal-text', 'label' => 'Audit logs'];
            $navItems[] = ['route' => 'users.index', 'match' => ['users.*'], 'icon' => 'bi-people', 'label' => 'Users'];
        }
    @endphp

    <div class="syncro-shell">
        <aside id="sidebar" class="syncro-rail" aria-label="Main navigation">
            <nav class="syncro-rail__nav">
                @foreach($navItems as $item)
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
                        <span class="visually-hidden">{{ $item['label'] }}</span>
                    </a>
                @endforeach
            </nav>

            <div class="syncro-rail__footer">
                <a href="{{ route($dashRoute) }}" class="syncro-rail__avatar" title="{{ Auth::user()->displayName() }}">
                    {{ strtoupper(substr(Auth::user()->displayName(), 0, 1)) }}
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

<!-- <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script> -->
<script src="{{ asset('js/bootstrap.bundle.min.js') }}"></script>
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
@stack('scripts')
</body>
</html>
