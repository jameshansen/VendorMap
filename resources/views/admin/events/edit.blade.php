@extends('layouts.admin')

@section('title', 'Edit event')

@section('content')
    <div class="page-head">
        <h1>Edit event</h1>
        <a class="btn-secondary" href="{{ route('admin.designer.show', $event) }}">Open designer</a>
    </div>
    @include('admin.events._form', [
        'action' => route('admin.events.update', $event),
        'method' => 'PUT',
        'submit' => 'Save changes',
    ])
@endsection
