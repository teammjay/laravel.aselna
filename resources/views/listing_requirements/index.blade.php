@extends('adminlte::page')

@section('content_header')
    <h1>Listing Requirements</h1>
@stop

@section('content')
    <div class="pt-2">
        <a href="{{ route('listing-requirements.create') }}" class="btn btn-primary mb-3">Add New Requirement</a>

        @if (session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif

        <table class="table table-bordered table-hover">
            <thead>
            <tr>
                <th>Category</th>
                <th>Type</th>
                <th>Table</th>
                <th>Column</th>
                <th>Key Column</th>
                <th>Key Value</th>
                <th>Value Column</th>
                <th>Actions</th>
            </tr>
            </thead>
            <tbody>
            @foreach ($requirements as $req)
                <tr>
                    <td>{{ $req->category }}</td>
                    <td>{{ $req->type }}</td>
                    <td>{{ $req->table }}</td>
                    <td>{{ $req->column }}</td>
                    <td>{{ $req->key_column }}</td>
                    <td>{{ $req->key_value }}</td>
                    <td>{{ $req->value_column }}</td>
                    <td>
                        <a href="{{ route('listing-requirements.edit', $req) }}" class="btn btn-sm btn-warning">Edit</a>
                        <form action="{{ route('listing-requirements.destroy', $req) }}" method="POST" style="display:inline-block;">
                            @csrf @method('DELETE')
                            <button class="btn btn-sm btn-danger" onclick="return confirm('Delete this requirement?')">Delete</button>
                        </form>
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
@endsection
