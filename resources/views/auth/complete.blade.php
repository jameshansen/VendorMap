@extends('layouts.app')

@section('title', 'Complete your profile')

@section('body')
@include('partials.site-header')

<main class="auth-page wide">
    <div class="auth-card">
        <h1>Almost there, {{ $user->name }}</h1>
        <p class="muted">Tell us about your business to finish your application. An admin reviews every account before booking opens.</p>

        @if ($errors->any())
            <div class="form-error">
                <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ route('register.complete.store') }}" class="stacked-form">
            @csrf
            @include('partials.vendor-fields')

            <label>Anything to help us verify you? <span class="muted">(optional)</span>
                <textarea name="application_note" rows="2" placeholder="e.g. links, references, what you sell">{{ old('application_note') }}</textarea>
            </label>

            <button type="submit" class="btn-primary">Submit application</button>
        </form>
    </div>
</main>
@endsection
