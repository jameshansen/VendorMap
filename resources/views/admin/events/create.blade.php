@extends('layouts.admin')

@section('title', 'New event')

@section('content')
    <h1>New event</h1>
    @include('admin.events._form', [
        'action' => route('admin.events.store'),
        'method' => 'POST',
        'submit' => 'Create event',
    ])
@endsection
