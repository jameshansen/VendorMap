@extends('layouts.admin')

@section('title', 'Dashboard')

@section('content')
    <h1>Dashboard</h1>

    <div class="stat-grid">
        <a class="stat-card" href="{{ route('admin.events.index') }}">
            <span class="stat-num">{{ $eventCount }}</span>
            <span class="stat-label">Events</span>
        </a>
        <a class="stat-card" href="{{ route('admin.vendors.index') }}">
            <span class="stat-num">{{ $pendingCount }}</span>
            <span class="stat-label">Pending approvals</span>
        </a>
        <a class="stat-card" href="{{ route('admin.vendors.index') }}">
            <span class="stat-num">{{ $vendorCount }}</span>
            <span class="stat-label">Approved vendors</span>
        </a>
    </div>

    @if ($recentPending->isNotEmpty())
        <section class="panel-block">
            <h2>Awaiting approval</h2>
            <ul class="plain-list">
                @foreach ($recentPending as $vendor)
                    <li>
                        <strong>{{ $vendor->business_name }}</strong>
                        <span class="muted">{{ $vendor->contact_name }} · {{ $vendor->email }}</span>
                    </li>
                @endforeach
            </ul>
            <a class="btn-link" href="{{ route('admin.vendors.index') }}">Review all →</a>
        </section>
    @endif
@endsection
