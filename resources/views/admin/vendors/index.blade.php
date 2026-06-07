@extends('layouts.admin')

@section('title', 'Vendors')

@section('content')
    <h1>Vendors</h1>

    <section class="panel-block">
        <h2>Pending approval ({{ $pending->count() }})</h2>
        @if ($pending->isEmpty())
            <p class="muted">Nothing waiting. 🎉</p>
        @else
            <div class="vendor-cards">
                @foreach ($pending as $vendor)
                    <article class="vendor-card">
                        <header>
                            <h3>{{ $vendor->business_name }}</h3>
                            <span class="muted">applied {{ $vendor->created_at->diffForHumans() }}</span>
                        </header>
                        <dl class="vendor-meta">
                            <div><dt>Contact</dt><dd>{{ $vendor->contact_name ?: '—' }}</dd></div>
                            <div><dt>Email</dt><dd>{{ $vendor->email ?: $vendor->user?->email ?: '—' }}</dd></div>
                            <div><dt>Phone</dt><dd>{{ $vendor->phone ?: '—' }}</dd></div>
                            <div><dt>Address</dt><dd>{{ $vendor->address ?: '—' }}</dd></div>
                            <div><dt>Website</dt><dd>{{ $vendor->website ?: '—' }}</dd></div>
                        </dl>
                        @if (!empty($vendor->socials))
                            <p class="vendor-socials">
                                @foreach ($vendor->socials as $platform => $handle)
                                    @if ($handle)<span class="tag">{{ $platform }}: {{ $handle }}</span>@endif
                                @endforeach
                            </p>
                        @endif
                        @if ($vendor->application_note)
                            <blockquote>{{ $vendor->application_note }}</blockquote>
                        @endif
                        <div class="vendor-actions">
                            <form method="POST" action="{{ route('admin.vendors.approve', $vendor) }}">
                                @csrf
                                <button type="submit" class="btn-primary">Approve</button>
                            </form>
                            <form method="POST" action="{{ route('admin.vendors.reject', $vendor) }}"
                                  onsubmit="return confirm('Reject this vendor?')">
                                @csrf
                                <button type="submit" class="link-danger">Reject</button>
                            </form>
                        </div>
                    </article>
                @endforeach
            </div>
        @endif
    </section>

    <section class="panel-block">
        <h2>Approved ({{ $approved->count() }})</h2>
        @if ($approved->isEmpty())
            <p class="muted">None yet.</p>
        @else
            <ul class="plain-list">
                @foreach ($approved as $vendor)
                    <li>
                        <strong>{{ $vendor->business_name }}</strong>
                        <span class="muted">{{ $vendor->contact_name }} · {{ $vendor->email ?: $vendor->user?->email }}</span>
                    </li>
                @endforeach
            </ul>
        @endif
    </section>

    @if ($rejected->isNotEmpty())
        <section class="panel-block">
            <h2>Rejected ({{ $rejected->count() }})</h2>
            <ul class="plain-list">
                @foreach ($rejected as $vendor)
                    <li>
                        <strong>{{ $vendor->business_name }}</strong>
                        <form method="POST" action="{{ route('admin.vendors.approve', $vendor) }}" class="inline-form">
                            @csrf
                            <button type="submit" class="link-muted">Re-approve</button>
                        </form>
                    </li>
                @endforeach
            </ul>
        </section>
    @endif
@endsection
