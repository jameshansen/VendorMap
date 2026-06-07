<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'Booking Platform')</title>

    {{-- Vite compiles and serves our CSS/JS. In dev it hot-reloads;
         in production it loads the built files from public/build. --}}
    @vite(['resources/css/app.css'])
    @yield('head')
</head>
<body>
    @yield('body')
</body>
</html>
