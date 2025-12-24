@extends('layouts.app')

@section('content')
<div class="container">
    <h3>Edit Custom Field</h3>

    <form action="{{ route('custom-fields.update', $field->id) }}" method="POST">
        @csrf
        @method('PUT')

        @include('custom_fields._form')

        <button type="submit" class="btn btn-primary mt-3">Update Field</button>
    </form>
</div>
@endsection
