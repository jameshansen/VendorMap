@extends('layouts.admin')

@section('title', 'Vendor conditions')

@section('head')
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.css">
@endsection

@section('content')
    <div class="page-head">
        <h1>Vendor conditions</h1>
    </div>

    <p class="muted">This document is shown to vendors when they book a table — they
        must agree to it before confirming. It supports Markdown formatting.</p>

    @if ($errors->any())
        <div class="form-error">
            <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="panel-block">
        <form method="POST" action="{{ route('admin.conditions.update') }}">
            @csrf
            @method('PUT')
            <textarea id="conditions-editor" name="content">{{ $content }}</textarea>
            <div class="form-actions" style="margin-top:1rem">
                <button type="submit" class="btn-primary">Save conditions</button>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/easymde/dist/easymde.min.js"></script>
    <script>
        new EasyMDE({
            element: document.getElementById('conditions-editor'),
            spellChecker: false,
            status: false,
            autoDownloadFontAwesome: true,
            toolbar: ['bold', 'italic', 'heading', '|', 'quote', 'unordered-list', 'ordered-list',
                      '|', 'link', '|', 'preview', 'side-by-side', 'guide'],
        });
    </script>
@endsection
