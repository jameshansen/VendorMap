@extends('layouts.admin')

@section('title', 'Vendors')

@section('content')
    <h1>Vendors</h1>

    @if ($guidance)
        <section class="panel-block ai-guidance">
            <h2>✨ AI Vendor Guidance</h2>
            <p>{{ $guidance['text'] }}</p>
            <p class="ai-guidance-meta">Generated {{ \Illuminate\Support\Carbon::parse($guidance['generated_at'])->diffForHumans() }} · refreshes automatically when the vendor list changes</p>
        </section>
    @endif

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
                        @if (!empty($vendor->categories))
                            <p class="vendor-categories">
                                @foreach ($vendor->categories as $category)
                                    <span class="tag">{{ $category }}</span>
                                @endforeach
                            </p>
                        @endif
                        <dl class="vendor-meta">
                            <div><dt>Contact</dt><dd>{{ $vendor->contact_name ?: '—' }}</dd></div>
                            <div><dt>Email</dt><dd>{{ $vendor->email ?: $vendor->user?->email ?: '—' }}</dd></div>
                            @if ($vendor->user && $vendor->user->email !== $vendor->email)
                                <div><dt>Login account</dt><dd>{{ $vendor->user->email }}</dd></div>
                            @endif
                            <div><dt>Phone</dt><dd>{{ $vendor->phone ?: '—' }}</dd></div>
                            <div><dt>Address</dt><dd>{{ $vendor->address ?: '—' }}</dd></div>
                            <div><dt>Website</dt><dd>{{ $vendor->website ?: '—' }}</dd></div>
                            <div><dt>Categories</dt><dd>{{ !empty($vendor->categories) ? implode(', ', $vendor->categories) : '—' }}</dd></div>
                            <div><dt>Submitted</dt><dd>{{ $vendor->created_at?->format('D j M Y, g:ia') ?? '—' }}</dd></div>
                        </dl>
                        @if (!empty($vendor->socials))
                            <p class="vendor-socials">
                                @foreach ($vendor->socials as $platform => $handle)
                                    @if ($handle)<span class="tag">{{ \App\Support\VendorProfile::SOCIALS[$platform] ?? $platform }}: {{ $handle }}</span>@endif
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
        @php $bookedCount = $approved->filter(fn ($v) => $tablesByVendor->has($v->id))->count(); @endphp
        <div class="panel-head">
            <h2>Approved ({{ $approved->count() }})</h2>
            @if ($events->count() > 1)
                <form method="GET" class="event-filter">
                    <label for="event">Bookings for</label>
                    <select name="event" id="event" class="venue-select" onchange="this.form.submit()">
                        @foreach ($events as $e)
                            <option value="{{ $e->id }}" @selected($event && $e->id === $event->id)>{{ $e->name }}</option>
                        @endforeach
                    </select>
                </form>
            @endif
        </div>

        @if ($approved->isEmpty())
            <p class="muted">None yet.</p>
        @else
            @if ($event)
                <p class="muted">{{ $bookedCount }} of {{ $approved->count() }} approved vendors have a table at
                    <strong>{{ $event->name }}</strong>; {{ $approved->count() - $bookedCount }} have not booked.</p>
            @else
                <p class="muted">No events yet, so nobody has booked a table.</p>
            @endif

            <table class="data-table">
                <thead>
                    <tr>
                        <th>Business</th>
                        <th>Contact</th>
                        <th>Email</th>
                        <th>Categories</th>
                        <th>Approved</th>
                        <th>{{ $event ? 'Booking · ' . $event->name : 'Booking' }}</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($approved as $vendor)
                        @php $tables = $tablesByVendor->get($vendor->id); @endphp
                        <tr>
                            <td><strong>{{ $vendor->business_name }}</strong></td>
                            <td>{{ $vendor->contact_name ?: '—' }}</td>
                            <td>{{ $vendor->email ?: $vendor->user?->email ?: '—' }}</td>
                            <td>{{ !empty($vendor->categories) ? implode(', ', $vendor->categories) : '—' }}</td>
                            <td class="muted">{{ $vendor->approved_at?->format('j M Y') ?? '—' }}</td>
                            <td>
                                @forelse ($tables ?? [] as $table)
                                    <span class="badge badge-{{ $table->status }}">{{ $table->label }} · {{ $table->status }}</span>
                                @empty
                                    <span class="badge badge-available">Not booked</span>
                                @endforelse
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @endif
    </section>

    @if ($rejected->isNotEmpty())
        <section class="panel-block">
            <h2>Rejected ({{ $rejected->count() }})</h2>
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Business</th>
                        <th>Contact</th>
                        <th>Email</th>
                        <th>Categories</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($rejected as $vendor)
                        <tr>
                            <td><strong>{{ $vendor->business_name }}</strong></td>
                            <td>{{ $vendor->contact_name ?: '—' }}</td>
                            <td>{{ $vendor->email ?: $vendor->user?->email ?: '—' }}</td>
                            <td>{{ !empty($vendor->categories) ? implode(', ', $vendor->categories) : '—' }}</td>
                            <td>
                                <form method="POST" action="{{ route('admin.vendors.approve', $vendor) }}" class="inline-form">
                                    @csrf
                                    <button type="submit" class="link-muted">Re-approve</button>
                                </form>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </section>
    @endif
@endsection
