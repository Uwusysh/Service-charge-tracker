<!DOCTYPE html>
<html lang="en-GB">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'Service Charge Tracker') — {{ config('app.name', 'SetK') }}</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Source+Sans+3:wght@400;500;600;700&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
    <noscript><link href="https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,500;9..144,600;9..144,700&family=Source+Sans+3:wght@400;500;600;700&display=swap" rel="stylesheet"></noscript>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="{{ asset('css/setk.css') }}?v=2" rel="stylesheet">
</head>
<body class="setk-body">
@php
    $user = auth()->user();
@endphp
<div class="setk-shell">
    <nav class="navbar navbar-expand-lg setk-topbar">
        <div class="container-fluid" style="width:min(1180px, calc(100% - 2rem)); margin:0 auto;">
            <a class="navbar-brand setk-brand py-2" href="{{ auth()->check() ? route('portfolio.index') : route('login') }}">
                SetK
                <span>Service charge tracker</span>
            </a>

            @auth
                <button class="navbar-toggler border-0 text-white" type="button" data-bs-toggle="collapse" data-bs-target="#setkNav" aria-controls="setkNav" aria-expanded="false" aria-label="Toggle navigation">
                    <span class="navbar-toggler-icon" style="filter:invert(1);"></span>
                </button>
                <div class="collapse navbar-collapse" id="setkNav">
                    <div class="navbar-nav setk-nav mx-lg-4 my-2 my-lg-0 gap-lg-1">
                        <a class="nav-link {{ request()->routeIs('portfolio.index') ? 'active' : '' }}" href="{{ route('portfolio.index') }}">Portfolio</a>
                        <a class="nav-link {{ request()->routeIs('clients.*') ? 'active' : '' }}" href="{{ route('clients.index') }}">Clients</a>
                        <a class="nav-link {{ request()->routeIs('buildings.*') || request()->routeIs('units.*') || request()->routeIs('schedules.*') || request()->routeIs('years.*') ? 'active' : '' }}" href="{{ route('buildings.index') }}">Buildings</a>
                        <a class="nav-link {{ request()->routeIs('budgets.*') ? 'active' : '' }}" href="{{ route('budgets.index') }}">Budgets</a>
                        <a class="nav-link {{ request()->routeIs('invoices.*') || request()->routeIs('invoice-files.*') ? 'active' : '' }}" href="{{ route('invoices.index') }}">Invoices</a>
                        <a class="nav-link {{ request()->routeIs('monitors.*') ? 'active' : '' }}" href="{{ route('monitors.index') }}">Monitor</a>
                        <a class="nav-link {{ request()->routeIs('suppliers.*') ? 'active' : '' }}" href="{{ route('suppliers.index') }}">Suppliers</a>
                        @if($user && $user->isAdministrator())
                            <a class="nav-link {{ request()->routeIs('users.*') ? 'active' : '' }}" href="{{ route('users.index') }}">Users</a>
                        @endif
                    </div>
                    <div class="setk-user ms-lg-auto mt-2 mt-lg-0">
                        <div class="text-end d-none d-md-block">
                            <div class="fw-semibold text-white">{{ $user->name }}</div>
                        </div>
                        <span class="setk-user-role">{{ str_replace('_', ' ', $user->role) }}</span>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button class="btn btn-sm btn-outline-light" type="submit">Logout</button>
                        </form>
                    </div>
                </div>
            @endauth
        </div>
    </nav>

    <main class="setk-main">
        @if(session('success'))
            <div class="alert alert-success mb-3">{{ session('success') }}</div>
        @endif
        @if(session('warning'))
            <div class="alert alert-warning mb-3">{{ session('warning') }}</div>
        @endif
        @if(isset($errors) && $errors->any())
            <div class="alert alert-danger mb-3">
                <strong>Please fix the following:</strong>
                <ul class="mb-0 mt-1">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
        @endif

        @yield('content')
    </main>

    <footer class="footer-note">
        Management information only — not a service-charge demand or statutory year-end account.
    </footer>
</div>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
