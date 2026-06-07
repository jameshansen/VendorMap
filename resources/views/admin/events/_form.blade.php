@php
    $dt = fn ($v) => $v?->format('Y-m-d\TH:i');
@endphp

@if ($errors->any())
    <div class="form-error">
        <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
    </div>
@endif

<form method="POST" action="{{ $action }}" class="stacked-form">
    @csrf
    @if ($method === 'PUT') @method('PUT') @endif

    <label>Name
        <input type="text" name="name" value="{{ old('name', $event->name) }}" required>
    </label>

    <label>Venue
        <select name="venue_id">
            <option value="">+ New venue (blank hall, edit in designer)</option>
            @foreach ($venues as $v)
                <option value="{{ $v->id }}" @selected((int) old('venue_id', $event->venue_id) === $v->id)>{{ $v->name }}</option>
            @endforeach
        </select>
    </label>

    <label>Description
        <textarea name="description" rows="3">{{ old('description', $event->description) }}</textarea>
    </label>

    <div class="form-row">
        <label>Status
            <select name="status">
                @foreach (['draft', 'published', 'closed'] as $s)
                    <option value="{{ $s }}" @selected(old('status', $event->status) === $s)>{{ ucfirst($s) }}</option>
                @endforeach
            </select>
        </label>
        <label class="check">
            <input type="hidden" name="is_public" value="0">
            <input type="checkbox" name="is_public" value="1" @checked(old('is_public', $event->is_public))>
            Show on the public site
        </label>
    </div>

    <div class="form-row">
        <label>Starts <input type="datetime-local" name="starts_at" value="{{ old('starts_at', $dt($event->starts_at)) }}"></label>
        <label>Ends <input type="datetime-local" name="ends_at" value="{{ old('ends_at', $dt($event->ends_at)) }}"></label>
    </div>

    <div class="form-row">
        <label>Registration opens <input type="datetime-local" name="registration_opens_at" value="{{ old('registration_opens_at', $dt($event->registration_opens_at)) }}"></label>
        <label>Registration closes <input type="datetime-local" name="registration_closes_at" value="{{ old('registration_closes_at', $dt($event->registration_closes_at)) }}"></label>
    </div>

    <label>Cancellation allowed until
        <input type="datetime-local" name="cancellation_deadline" value="{{ old('cancellation_deadline', $dt($event->cancellation_deadline)) }}">
    </label>

    <div class="form-actions">
        <a class="btn-link" href="{{ route('admin.events.index') }}">Cancel</a>
        <button type="submit" class="btn-primary">{{ $submit }}</button>
    </div>
</form>
