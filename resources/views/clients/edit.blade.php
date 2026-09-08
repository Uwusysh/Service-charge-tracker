@extends('layouts.app')
@section('title', 'Edit client')
@section('content')
<h1 class="h3 mb-3">Edit client</h1>
<form method="POST" action="{{ route('clients.update', $client) }}" class="col-md-6">
    @csrf @method('PUT')
    <div class="mb-3"><label class="form-label">Name</label><input name="name" class="form-control" value="{{ old('name', $client->name) }}" required></div>
    <div class="mb-3">
        <label class="form-label">Type</label>
        <select name="type" class="form-select" required>
            @foreach(['freeholder','landlord','rmc','rtm','other'] as $type)
                <option value="{{ $type }}" @selected(old('type', $client->type)===$type)>{{ strtoupper($type) }}</option>
            @endforeach
        </select>
    </div>
    <div class="mb-3"><label class="form-label">Company details</label><textarea name="company_details" class="form-control">{{ old('company_details', $client->company_details) }}</textarea></div>
    <div class="mb-3"><label class="form-label">Accountant details</label><textarea name="accountant_details" class="form-control">{{ old('accountant_details', $client->accountant_details) }}</textarea></div>
    <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="is_active" value="1" @checked(old('is_active', $client->is_active)) id="active"><label class="form-check-label" for="active">Active</label></div>
    <button class="btn btn-primary">Update</button>
</form>
@endsection
