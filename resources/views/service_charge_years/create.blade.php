@extends('layouts.app')
@section('title', 'Add service charge year')
@section('content')
<h1 class="h3 mb-3">Add year — {{ $building->name }}</h1>
<form method="POST" action="{{ route('years.store', $building) }}" class="col-md-6">
    @csrf
    <div class="mb-3"><label class="form-label">Start date</label><input type="date" name="start_date" class="form-control" value="{{ old('start_date') }}" required></div>
    <div class="mb-3"><label class="form-label">End date</label><input type="date" name="end_date" class="form-control" value="{{ old('end_date') }}" required></div>
    <div class="mb-3"><label class="form-label">Label</label><input name="label" class="form-control" value="{{ old('label') }}"></div>
    <div class="mb-3">
        <label class="form-label">Status</label>
        <select name="status" class="form-select">
            @foreach(['draft','active','closed'] as $status)
                <option value="{{ $status }}" @selected(old('status','active')===$status)>{{ $status }}</option>
            @endforeach
        </select>
    </div>
    <button class="btn btn-primary">Save</button>
</form>
@endsection
