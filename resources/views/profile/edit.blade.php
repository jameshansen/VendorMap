@extends('layouts.app')

@section('title', 'My profile')

@section('body')
@include('partials.site-header')

<main class="auth-page wide">
    <div class="auth-card">
        <div class="page-head">
            <h1>My profile</h1>
            <span class="badge badge-{{ $vendor->status }}">{{ ucfirst($vendor->status) }}</span>
        </div>

        @if (session('status'))
            <div class="flash">{{ session('status') }}</div>
        @endif
        @if ($errors->any())
            <div class="form-error">
                <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
            </div>
        @endif

        <form method="POST" action="{{ route('profile.update') }}" class="stacked-form">
            @csrf
            @method('PUT')
            @include('partials.vendor-fields')
            <div class="form-actions">
                <button type="submit" class="btn-primary">Save changes</button>
            </div>
        </form>
    </div>

    <div class="auth-card">
        <h2>My bookings</h2>
        @if ($bookings->isEmpty())
            <p class="muted">You haven't booked any tables yet.</p>
        @else
            <ul class="plain-list">
                @foreach ($bookings as $t)
                    <li>
                        <strong>{{ $t->event?->name }}</strong> — table {{ $t->label }}
                        <span class="badge badge-{{ $t->status }}">{{ $t->status }}</span>
                        <span class="badge {{ $t->paid ? 'badge-paid' : 'badge-unpaid' }}">{{ $t->paid ? 'Paid' : 'Unpaid' }}</span>
                        <span class="muted">${{ number_format((float) $t->price, 2) }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </div>
</main>
@endsection
