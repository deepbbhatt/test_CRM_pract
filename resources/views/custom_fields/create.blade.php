@extends('layouts.app')

@section('content')
<div class="container">
    <h3>Add Custom Field</h3>

    <form action="{{ route('custom-fields.store') }}" method="POST">
        @csrf

        @include('custom_fields._form')

        <button type="submit" class="btn btn-success mt-3">Save Field</button>
    </form>
</div>
@endsection
