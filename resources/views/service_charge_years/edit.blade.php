@extends('layouts.app')
@section('title', 'Edit service charge year')
@section('content')
<h1 class="h3 mb-3">Edit year — {{ $serviceChargeYear->building->name }}</h1>
<form method="POST" action="{{ route('years.update', $serviceChargeYear) }}" class="col-md-6">
    @csrf @method('PUT')
    <div class="mb-3"><label class="form-label">Start date</label><input type="date" name="start_date" class="form-control" value="{{ old('start_date', $serviceChargeYear->start_date->format('Y-m-d')) }}" required></div>
    <div class="mb-3"><label class="form-label">End date</label><input type="date" name="end_date" class="form-control" value="{{ old('end_date', $serviceChargeYear->end_date->format('Y-m-d')) }}" required></div>
    <div class="mb-3"><label class="form-label">Label</label><input name="label" class="form-control" value="{{ old('label', $serviceChargeYear->label) }}"></div>
    <div class="mb-3">
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
            @foreach(['draft','active','closed'] as $status)
                <option value="{{ $status }}" @selected(old('status', $serviceChargeYear->status)===$status)>{{ $status }}</option>
            @endforeach
        </select>
    </div>
    <button class="btn btn-primary">Update</button>
</form>
@endsection
