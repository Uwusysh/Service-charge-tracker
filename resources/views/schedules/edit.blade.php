@extends('layouts.app')
@section('title', 'Edit schedule')
@section('content')
<h1 class="h3 mb-3">Edit schedule</h1>
<form method="POST" action="{{ route('schedules.update', $schedule) }}" class="col-md-6">
    @csrf @method('PUT')
    <div class="mb-3"><label class="form-label">Name</label><input name="name" class="form-control" value="{{ old('name', $schedule->name) }}" required></div>
    <div class="mb-3">
        <label class="form-label">Allocation method</label>
        <select name="allocation_method" class="form-select">
            <option value="equal" @selected(old('allocation_method', $schedule->allocation_method)==='equal')>Equal</option>
            <option value="manual" @selected(old('allocation_method', $schedule->allocation_method)==='manual')>Manual</option>
        </select>
    </div>
    <div class="mb-3">
        <label class="form-label">Balancing unit</label>
        <select name="balancing_unit_id" class="form-select">
            <option value="">—</option>
            @foreach($schedule->building->units as $unit)
                <option value="{{ $unit->id }}" @selected(old('balancing_unit_id', $schedule->balancing_unit_id)==$unit->id)>{{ $unit->unit_reference }}</option>
            @endforeach
        </select>
    </div>
    <button class="btn btn-primary">Update</button>
</form>
@endsection
