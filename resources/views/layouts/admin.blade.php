<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Admin') · {{ config('vendormap.name', 'VendorMap') }} Admin</title>
    @vite(['resources/css/app.css'])
    @yield('head')
</head>
<body>
    <div class="admin-shell">
        <header class="admin-nav">
            <a class="admin-brand" href="{{ route('admin.dashboard') }}">
                <span class="dot"></span> {{ config('vendormap.name', 'VendorMap') }} Admin
            </a>
            <nav>
                <a href="{{ route('admin.dashboard') }}" @class(['active' => request()->routeIs('admin.dashboard')])>Dashboard</a>
                <a href="{{ route('admin.events.index') }}" @class(['active' => request()->routeIs('admin.events.*')])>Events</a>
                <a href="{{ route('admin.vendors.index') }}" @class(['active' => request()->routeIs('admin.vendors.*')])>Vendors</a>
                <a href="{{ route('admin.categories.index') }}" @class(['active' => request()->routeIs('admin.categories.*')])>Categories</a>
                <a href="{{ route('admin.conditions.edit') }}" @class(['active' => request()->routeIs('admin.conditions.*')])>Conditions</a>
            </nav>
            <form method="POST" action="{{ route('admin.logout') }}" class="admin-logout">
                @csrf
                <a href="{{ route('home') }}" class="muted-link">View site</a>
                <button type="submit" class="ghost">Sign out</button>
            </form>
        </header>

        <main class="admin-main">
            @if (session('status'))
                <div class="flash">{{ session('status') }}</div>
            @endif
            @yield('content')
        </main>
    </div>
</body>
</html>
