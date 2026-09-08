@extends('layouts.app')
@section('title', 'New budget')
@section('content')
<x-page-header title="New budget" lede="Create a draft annual service-charge budget for a building and year." />

<div class="panel" style="max-width:640px">
    <form method="POST" action="{{ route('budgets.store') }}">
        @csrf
        <div class="mb-3">
            <label class="form-label">Building</label>
            <select name="building_id" class="form-select" required>
                @foreach($buildings as $building)
                    <option value="{{ $building->id }}" @selected(old('building_id', $selectedBuildingId)==$building->id)>{{ $building->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3">
            <label class="form-label">Service charge year</label>
            <select name="service_charge_year_id" class="form-select" required>
                @foreach($years as $year)
                    <option value="{{ $year->id }}">{{ $year->building->name }} — {{ $year->displayLabel() }}</option>
                @endforeach
            </select>
        </div>
        <div class="mb-3"><label class="form-label">Title</label><input name="title" class="form-control" value="{{ old('title') }}" required placeholder="e.g. Maple Court Service Charge Budget 2027"></div>
        <div class="mb-3"><label class="form-label">Notes</label><textarea name="overall_notes" class="form-control" rows="3">{{ old('overall_notes') }}</textarea></div>
        <button class="btn btn-primary">Create draft</button>
    </form>
</div>
@endsection
