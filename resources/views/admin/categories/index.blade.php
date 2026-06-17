@extends('layouts.admin')

@section('title', 'Categories')

@section('content')
    <div class="page-head">
        <h1>Vendor categories</h1>
    </div>

    <p class="muted">These are the suggested product categories vendors can pick from
        when they sign up or edit their profile. Vendors may also add their own
        custom categories.</p>

    @if ($errors->any())
        <div class="form-error">
            <ul>@foreach ($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
        </div>
    @endif

    <div class="panel-block">
        <form method="POST" action="{{ route('admin.categories.store') }}" class="form-row" style="align-items:flex-end">
            @csrf
            <label style="flex:1">New category
                <input type="text" name="name" placeholder="e.g. Candles" required>
            </label>
            <button type="submit" class="btn-primary">Add category</button>
        </form>
    </div>

    @if ($categories->isEmpty())
        <p class="muted">No categories yet. Add the first one above.</p>
    @else
        <table class="data-table">
            <thead><tr><th>Name</th><th></th></tr></thead>
            <tbody>
                @foreach ($categories as $category)
                    <tr>
                        <td>{{ $category->name }}</td>
                        <td>
                            <div class="row-actions">
                                <form method="POST" action="{{ route('admin.categories.destroy', $category) }}"
                                      onsubmit="return confirm('Remove this category from the suggestion list?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="link-danger">Delete</button>
                                </form>
                            </div>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    @endif
@endsection
