@extends('layouts.app')
@section('title', 'New client')
@section('content')
<h1 class="h3 mb-3">New client</h1>
<form method="POST" action="{{ route('clients.store') }}" class="col-md-6">
    @csrf
    <div class="mb-3"><label class="form-label">Name</label><input name="name" class="form-control" value="{{ old('name') }}" required></div>
    <div class="mb-3">
        <label class="form-label">Type</label>
        <select name="type" class="form-select" required>
            @foreach(['freeholder','landlord','rmc','rtm','other'] as $type)
                <option value="{{ $type }}" @selected(old('type')===$type)>{{ strtoupper($type) }}</option>
            @endforeach
        </select>
    </div>
    <div class="mb-3"><label class="form-label">Company details</label><textarea name="company_details" class="form-control">{{ old('company_details') }}</textarea></div>
    <div class="mb-3"><label class="form-label">Accountant details</label><textarea name="accountant_details" class="form-control">{{ old('accountant_details') }}</textarea></div>
    <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="is_active" value="1" checked id="active"><label class="form-check-label" for="active">Active</label></div>
    <button class="btn btn-primary">Save</button>
</form>
@endsection
