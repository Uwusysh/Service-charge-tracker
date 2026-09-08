@extends('layouts.app')
@section('title', 'Edit building')
@section('content')
<h1 class="h3 mb-3">Edit building</h1>
<form method="POST" action="{{ route('buildings.update', $building) }}" class="col-md-7">
    @csrf @method('PUT')
    <div class="mb-3">
        <label class="form-label">Client</label>
        <select name="client_id" class="form-select" required>
            @foreach($clients as $client)
                <option value="{{ $client->id }}" @selected(old('client_id', $building->client_id)==$client->id)>{{ $client->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="mb-3"><label class="form-label">Name</label><input name="name" class="form-control" value="{{ old('name', $building->name) }}" required></div>
    <div class="mb-3"><label class="form-label">Address line 1</label><input name="address_line1" class="form-control" value="{{ old('address_line1', $building->address_line1) }}" required></div>
    <div class="mb-3"><label class="form-label">Address line 2</label><input name="address_line2" class="form-control" value="{{ old('address_line2', $building->address_line2) }}"></div>
    <div class="mb-3"><label class="form-label">City</label><input name="city" class="form-control" value="{{ old('city', $building->city) }}" required></div>
    <div class="mb-3"><label class="form-label">Postcode</label><input name="postcode" class="form-control" value="{{ old('postcode', $building->postcode) }}" required></div>
    <div class="mb-3"><label class="form-label">Reference</label><input name="reference" class="form-control" value="{{ old('reference', $building->reference) }}" required></div>
    <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="is_active" value="1" @checked(old('is_active', $building->is_active)) id="active"><label for="active" class="form-check-label">Active</label></div>
    <button class="btn btn-primary">Update</button>
</form>
@endsection
