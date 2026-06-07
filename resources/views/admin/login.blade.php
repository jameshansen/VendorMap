@extends('layouts.app')

@section('title', 'Admin sign in')

@section('body')
<main class="auth-page">
    <div class="auth-card">
        <h1>Admin sign in</h1>
        <p class="muted">Restricted area. Credentials are set in <code>config.php</code>.</p>

        @if ($errors->any())
            <div class="form-error">{{ $errors->first() }}</div>
        @endif

        <form method="POST" action="{{ route('admin.login.post') }}" class="stacked-form">
            @csrf
            <label>Username
                <input type="text" name="username" value="{{ old('username') }}" autofocus required>
            </label>
            <label>Password
                <input type="password" name="password" required>
            </label>
            <button type="submit" class="btn-primary">Sign in</button>
        </form>

        <p class="auth-alt"><a href="{{ route('home') }}">← Back to site</a></p>
    </div>
</main>
@endsection
