@extends('layouts.app')
@section('title', 'Edit unit')
@section('content')
<h1 class="h3 mb-3">Edit unit — {{ $unit->building->name }}</h1>
<form method="POST" action="{{ route('units.update', $unit) }}" class="col-md-6">
    @csrf @method('PUT')
    <div class="mb-3"><label class="form-label">Unit reference</label><input name="unit_reference" class="form-control" value="{{ old('unit_reference', $unit->unit_reference) }}" required></div>
    <div class="mb-3"><label class="form-label">Description</label><input name="description" class="form-control" value="{{ old('description', $unit->description) }}"></div>
    <div class="mb-3"><label class="form-label">Active from</label><input type="date" name="active_from" class="form-control" value="{{ old('active_from', optional($unit->active_from)->format('Y-m-d')) }}"></div>
    <div class="mb-3"><label class="form-label">Active to</label><input type="date" name="active_to" class="form-control" value="{{ old('active_to', optional($unit->active_to)->format('Y-m-d')) }}"></div>
    <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="is_active" value="1" @checked(old('is_active', $unit->is_active)) id="active"><label for="active" class="form-check-label">Active</label></div>
    <button class="btn btn-primary">Update</button>
</form>
@endsection
