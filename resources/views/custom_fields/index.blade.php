@extends('layouts.app')

@section('content')
<div class="container">
    <div class="d-flex justify-content-between mb-3">
        <h3>Custom Fields</h3>
        <a href="{{ route('custom-fields.create') }}" class="btn btn-primary">+ Add Custom Field</a>
    </div>

    @if(session('success'))
        <div class="alert alert-success">{{ session('success') }}</div>
    @endif

    <table class="table table-bordered">
        <thead>
            <tr>
                <th>Name</th>
                <th>Slug</th>
                <th>Type</th>
                <th>Options (if applicable)</th>
                <th width="150">Action</th>
            </tr>
        </thead>
        <tbody>
            @forelse($fields as $field)
                <tr>
                    <td>{{ $field->name }}</td>
                    <td>{{ $field->slug }}</td>
                    <td>{{ ucfirst($field->type) }}</td>
                    <td>
                        @if(is_array($field->options))
                            {{ implode(', ', $field->options) }}
                        @else
                            —
                        @endif
                    </td>
                    <td>
                        <a href="{{ route('custom-fields.edit', $field->id) }}" class="btn btn-sm btn-warning">Edit</a>

                        <form action="{{ route('custom-fields.destroy', $field->id) }}" method="POST" 
                              style="display:inline-block;">
                            @csrf
                            @method('DELETE')

                            <button class="btn btn-sm btn-danger" 
                                    onclick="return confirm('Delete this field?')">
                                Delete
                            </button>
                        </form>

                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center">No custom fields found.</td></tr>
            @endforelse
        </tbody>
    </table>
</div>
@endsection
