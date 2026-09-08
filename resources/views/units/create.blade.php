@extends('layouts.app')
@section('title', 'Add unit')
@section('content')
<h1 class="h3 mb-3">Add unit — {{ $building->name }}</h1>
<form method="POST" action="{{ route('units.store', $building) }}" class="col-md-6">
    @csrf
    <div class="mb-3"><label class="form-label">Unit reference</label><input name="unit_reference" class="form-control" value="{{ old('unit_reference') }}" required></div>
    <div class="mb-3"><label class="form-label">Description</label><input name="description" class="form-control" value="{{ old('description') }}"></div>
    <div class="mb-3"><label class="form-label">Active from</label><input type="date" name="active_from" class="form-control" value="{{ old('active_from') }}"></div>
    <div class="mb-3"><label class="form-label">Active to</label><input type="date" name="active_to" class="form-control" value="{{ old('active_to') }}"></div>
    <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="is_active" value="1" checked id="active"><label for="active" class="form-check-label">Active</label></div>
    <button class="btn btn-primary">Save</button>
</form>
@endsection
