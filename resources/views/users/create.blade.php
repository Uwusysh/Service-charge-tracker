@extends('layouts.app')
@section('title', 'New user')
@section('content')
<h1 class="h3 mb-3">New user</h1>
<form method="POST" action="{{ route('users.store') }}" class="col-md-6">
    @csrf
    <div class="mb-3"><label class="form-label">Name</label><input name="name" class="form-control" required></div>
    <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" required></div>
    <div class="mb-3"><label class="form-label">Password</label><input type="password" name="password" class="form-control" required></div>
    <div class="mb-3">
        <label class="form-label">Role</label>
        <select name="role" class="form-select">
            <option value="administrator">administrator</option>
            <option value="block_manager" selected>block_manager</option>
            <option value="accountant">accountant</option>
        </select>
    </div>
    <button class="btn btn-primary">Save</button>
</form>
@endsection
