@php
    $authUser = auth()->user();
    $authVendor = $authUser?->vendor;
@endphp
<header class="site-header">
    <a class="site-brand" href="{{ route('home') }}">
        <img src="img/icon.png" style="height: 32px" /> {{ config('vendormap.name', 'VendorMap') }}
    </a>

    <nav class="site-nav">
        @auth
            @if ($authVendor && ! $authVendor->isApproved())
                <span class="pending-chip" title="An admin is reviewing your account">Pending approval</span>
            @endif
            <a href="{{ route('profile.edit') }}">My profile</a>
            <span class="who">{{ $authUser->name }}</span>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="ghost">Sign out</button>
            </form>
        @else
            <a href="{{ route('login') }}">Sign in</a>
            <a href="{{ route('register') }}" class="btn-primary sm">Create account</a>
        @endauth
    </nav>
</header>
