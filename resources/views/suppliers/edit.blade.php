@extends('layouts.app')
@section('title', 'Edit supplier')
@section('content')
<h1 class="h3 mb-3">Edit supplier</h1>
<form method="POST" action="{{ route('suppliers.update', $supplier) }}" class="col-md-5">
    @csrf @method('PUT')
    <div class="mb-3"><label class="form-label">Name</label><input name="name" class="form-control" value="{{ $supplier->name }}" required></div>
    <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="is_active" value="1" @checked($supplier->is_active) id="a"><label for="a" class="form-check-label">Active</label></div>
    <button class="btn btn-primary">Update</button>
</form>
@endsection
