<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Syncro</title>
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css">
    <link rel="stylesheet"
        href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.1/font/bootstrap-icons.css">

    <style>
        body { padding-top: 56px; } /* space for fixed navbar */
        #sidebar {
            width: 220px;
            top: 56px; /* align under navbar */
            left: 0;
            height: calc(100vh - 56px);
            overflow-y: auto;
            transition: transform .2s ease;
        }
        #main-content {
            margin-left: 220px;
            padding: 1.25rem;
        }
        @media (max-width: 767.98px) {
            #sidebar { transform: translateX(-100%); position: fixed; z-index: 1040; }
            #sidebar.show { transform: translateX(0); }
            #main-content { margin-left: 0; }
        }
    </style>
</head>
<body>
    <!-- Top navbar -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary fixed-top">
        <div class="container-fluid">
            <button class="btn btn-outline-light d-lg-none me-2" id="sidebarToggle" aria-label="Toggle sidebar">
                <i class="bi bi-list"></i>
            </button>

            <a class="navbar-brand" href="{{ url('/') }}">Syncro</a>

            <div class="collapse navbar-collapse justify-content-end">
                <ul class="navbar-nav mb-2 mb-lg-0 align-items-center">
                    <li class="nav-item me-2 d-none d-lg-block">
                        <a class="nav-link text-light" href="#"><i class="bi bi-bell"></i></a>
                    </li>
                    @auth
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle text-light" href="#" id="userMenu" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="bi bi-person-circle"></i> {{ Auth::user()->name }}
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end" aria-labelledby="userMenu">
                            <li><a class="dropdown-item" href="#">Account</a></li>
                            <li><a class="dropdown-item" href="#">Settings</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form method="POST" action="{{ route('logout') }}">
                                    @csrf
                                    <button type="submit" class="dropdown-item">Logout</button>
                                </form>
                            </li>
                        </ul>
                    </li>
                    @else
                    <li class="nav-item"><a class="nav-link text-light" href="{{ route('login') }}">Login</a></li>
                    @endauth
                </ul>
            </div>
        </div>
    </nav>

    <!-- Sidebar -->
    <div id="sidebar" class="bg-light border-end position-fixed d-lg-block">
        <div class="list-group list-group-flush">
            <a href="#" class="list-group-item list-group-item-action py-3">
                <i class="bi bi-speedometer2 me-2"></i> Dashboard
            </a>
            <a href="#" class="list-group-item list-group-item-action py-3">
                <i class="bi bi-bell me-2"></i> Notifications
            </a>
            <a href="#" class="list-group-item list-group-item-action py-3">
                <i class="bi bi-clipboard-data me-2"></i> Audit Logs
            </a>
            <!-- add more feature links here -->
        </div>
    </div>

    <!-- Main content area -->
    <main id="main-content">
        @yield('content')
    </main>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        (function () {
            var toggle = document.getElementById('sidebarToggle');
            var sidebar = document.getElementById('sidebar');
            if (!toggle || !sidebar) return;
            toggle.addEventListener('click', function () {
                sidebar.classList.toggle('show');
            });
        })();
    </script>
    @stack('scripts')
</body>
</html>