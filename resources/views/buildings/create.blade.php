@extends('layouts.app')
@section('title', 'New building')
@section('content')
<h1 class="h3 mb-3">New building</h1>
<form method="POST" action="{{ route('buildings.store') }}" class="col-md-7">
    @csrf
    <div class="mb-3">
        <label class="form-label">Client</label>
        <select name="client_id" class="form-select" required>
            @foreach($clients as $client)
                <option value="{{ $client->id }}" @selected(old('client_id', $selectedClientId)==$client->id)>{{ $client->name }}</option>
            @endforeach
        </select>
    </div>
    <div class="mb-3"><label class="form-label">Name</label><input name="name" class="form-control" value="{{ old('name') }}" required></div>
    <div class="mb-3"><label class="form-label">Address line 1</label><input name="address_line1" class="form-control" value="{{ old('address_line1') }}" required></div>
    <div class="mb-3"><label class="form-label">Address line 2</label><input name="address_line2" class="form-control" value="{{ old('address_line2') }}"></div>
    <div class="mb-3"><label class="form-label">City</label><input name="city" class="form-control" value="{{ old('city') }}" required></div>
    <div class="mb-3"><label class="form-label">Postcode</label><input name="postcode" class="form-control" value="{{ old('postcode') }}" required></div>
    <div class="mb-3"><label class="form-label">Reference</label><input name="reference" class="form-control" value="{{ old('reference') }}" required></div>
    <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="is_active" value="1" checked id="active"><label for="active" class="form-check-label">Active</label></div>
    <button class="btn btn-primary">Save</button>
</form>
@endsection
