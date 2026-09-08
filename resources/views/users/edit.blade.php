@extends('layouts.app')
@section('title', 'Edit user')
@section('content')
<h1 class="h3 mb-3">Edit user</h1>
<form method="POST" action="{{ route('users.update', $user) }}" class="col-md-6">
    @csrf @method('PUT')
    <div class="mb-3"><label class="form-label">Name</label><input name="name" class="form-control" value="{{ $user->name }}" required></div>
    <div class="mb-3"><label class="form-label">Email</label><input type="email" name="email" class="form-control" value="{{ $user->email }}" required></div>
    <div class="mb-3"><label class="form-label">Password (leave blank to keep)</label><input type="password" name="password" class="form-control"></div>
    <div class="mb-3">
        <label class="form-label">Role</label>
        <select name="role" class="form-select">
            @foreach(['administrator','block_manager','accountant'] as $role)
                <option value="{{ $role }}" @selected($user->role===$role)>{{ $role }}</option>
            @endforeach
        </select>
    </div>
    <button class="btn btn-primary">Update</button>
</form>
@endsection
