@extends('layouts.app')
@section('title', 'New schedule')
@section('content')
<h1 class="h3 mb-3">New schedule — {{ $building->name }}</h1>
<form method="POST" action="{{ route('schedules.store', $building) }}" class="col-md-7">
    @csrf
    <div class="mb-3"><label class="form-label">Name</label><input name="name" class="form-control" value="{{ old('name') }}" required></div>
    <div class="mb-3">
        <label class="form-label">Allocation method</label>
        <select name="allocation_method" class="form-select">
            <option value="equal" @selected(old('allocation_method','equal')==='equal')>Equal</option>
            <option value="manual" @selected(old('allocation_method')==='manual')>Manual</option>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Balancing unit</label>
        <select name="balancing_unit_id" class="form-select">
            <option value="">—</option>
            @foreach($building->units as $unit)
                <option value="{{ $unit->id }}">{{ $unit->unit_reference }}</option>
            @endforeach
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Participating units</label>
        @foreach($building->units as $unit)
            <div class="form-check">
                <input class="form-check-input" type="checkbox" name="unit_ids[]" value="{{ $unit->id }}" id="u{{ $unit->id }}" checked>
                <label class="form-check-label" for="u{{ $unit->id }}">{{ $unit->unit_reference }}</label>
            </div>
        @endforeach
    </div>
    <button class="btn btn-primary">Save</button>
</form>
@endsection
