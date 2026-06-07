@extends('layouts.app')

@section('title', 'Create your vendor account')

@section('body')
@include('partials.site-header')

<main class="auth-page wide">
    <div class="auth-card">
        <h1>Create your vendor account</h1>
        <p class="muted">Tell us about your business. An admin reviews every application before you can book.</p>

        @if (App\Http\Controllers\Auth\GoogleController::enabled())
            <a href="{{ route('google.redirect') }}" class="btn-google">
                <svg viewBox="0 0 48 48" width="18" height="18"><path fill="#EA4335" d="M24 9.5c3.5 0 6.6 1.2 9 3.6l6.7-6.7C35.6 2.4 30.1 0 24 0 14.6 0 6.4 5.4 2.5 13.3l7.8 6c1.9-5.6 7.1-9.8 13.7-9.8z"/><path fill="#4285F4" d="M46.5 24.5c0-1.6-.1-3.1-.4-4.5H24v9h12.7c-.5 3-2.2 5.5-4.7 7.2l7.3 5.7c4.3-3.9 6.7-9.7 6.7-17.4z"/><path fill="#FBBC05" d="M10.3 28.3c-.5-1.4-.7-2.9-.7-4.3s.3-3 .7-4.3l-7.8-6C.9 16.9 0 20.3 0 24s.9 7.1 2.5 10.3l7.8-6z"/><path fill="#34A853" d="M24 48c6.5 0 11.9-2.1 15.9-5.8l-7.3-5.7c-2 1.4-4.7 2.3-8.6 2.3-6.6 0-12.2-4.2-14.1-9.8l-7.8 6C6.4 42.6 14.6 48 24 48z"/></svg>
                Sign up with Google
            </a>
            <div class="or-divider"><span>or</span></div>
        @endif

        @if ($errors->any())
            <div class="form-error">
                <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ route('register') }}" class="stacked-form">
            @csrf

            {{-- Honeypot: must stay empty. Hidden from real users. --}}
            <div class="hp" aria-hidden="true">
                <label>Company URL <input type="text" name="{{ App\Support\BotGuard::HONEYPOT }}" tabindex="-1" autocomplete="off"></label>
            </div>

            @include('partials.vendor-fields')

            <div class="form-row">
                <label>Email
                    <input type="email" name="email" value="{{ old('email') }}" required>
                </label>
            </div>
            <div class="form-row">
                <label>Password
                    <input type="password" name="password" required>
                </label>
                <label>Confirm password
                    <input type="password" name="password_confirmation" required>
                </label>
            </div>

            <label>Anything to help us verify you? <span class="muted">(optional)</span>
                <textarea name="application_note" rows="2" placeholder="e.g. links, references, what you sell">{{ old('application_note') }}</textarea>
            </label>

            @php $siteKey = config('vendormap.recaptcha.site_key'); @endphp
            @if ($siteKey)
                <div class="g-recaptcha" data-sitekey="{{ $siteKey }}"></div>
            @endif

            <button type="submit" class="btn-primary">Create account</button>
        </form>

        <p class="auth-alt">Already have an account? <a href="{{ route('login') }}">Sign in</a></p>
    </div>
</main>

@if (config('vendormap.recaptcha.site_key'))
    <script src="https://www.google.com/recaptcha/api.js" async defer></script>
@endif
@endsection
