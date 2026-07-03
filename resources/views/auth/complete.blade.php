@extends('layouts.app')

@section('title', 'Complete your profile')

@section('body')
@include('partials.site-header')

<main class="auth-page wide">
    <div class="auth-card">
        <h1>Almost there, {{ $user->name }}</h1>
        <p class="muted">Tell us about your business to finish your application. An admin reviews every account before booking opens.</p>

        @php $topKeys = collect($errors->keys())->reject(fn ($k) => $k === 'application_note'); @endphp
        @if ($topKeys->isNotEmpty())
            <div class="form-error">
                <ul>@foreach ($topKeys as $key)@foreach ($errors->get($key) as $msg)<li>{{ $msg }}</li>@endforeach @endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ route('register.complete.store') }}" class="stacked-form">
            @csrf
            @include('partials.vendor-fields')

            @error('application_note')
                <div class="notice-green" role="alert">{{ $message }}</div>
            @enderror
            <label>Anything else to help us verify you?
                <textarea name="application_note" rows="2" placeholder="e.g. links, references, what you sell">{{ old('application_note') }}</textarea>
            </label>

            <button type="submit" class="btn-primary">Submit application</button>
        </form>
    </div>
</main>
@endsection
