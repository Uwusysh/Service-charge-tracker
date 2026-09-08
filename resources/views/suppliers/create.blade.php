@extends('layouts.app')
@section('title', 'New supplier')
@section('content')
<h1 class="h3 mb-3">New supplier</h1>
<form method="POST" action="{{ route('suppliers.store') }}" class="col-md-5">
    @csrf
    <div class="mb-3"><label class="form-label">Name</label><input name="name" class="form-control" required></div>
    <div class="form-check mb-3"><input class="form-check-input" type="checkbox" name="is_active" value="1" checked id="a"><label for="a" class="form-check-label">Active</label></div>
    <button class="btn btn-primary">Save</button>
</form>
@endsection
