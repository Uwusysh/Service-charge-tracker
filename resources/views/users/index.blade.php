@extends('layouts.app')
@section('title', 'Users')
@section('content')
<x-page-header title="Users" lede="Administrator access to POC roles and accounts.">
    <x-slot:actions>
        <a href="{{ route('users.create') }}" class="btn btn-primary">New user</a>
    </x-slot:actions>
</x-page-header>

<div class="panel">
    <div class="table-wrap">
        <table class="table setk-table table-sm">
            <thead><tr><th>Name</th><th>Email</th><th>Role</th><th></th></tr></thead>
            <tbody>
            @foreach($users as $userRow)
                <tr>
                    <td class="fw-semibold">{{ $userRow->name }}</td>
                    <td>{{ $userRow->email }}</td>
                    <td><span class="badge-pill badge-neutral">{{ str_replace('_', ' ', $userRow->role) }}</span></td>
                    <td class="text-end">
                        <a href="{{ route('users.edit', $userRow) }}">Edit</a>
                        @if($userRow->id !== auth()->id())
                        <form method="POST" action="{{ route('users.destroy', $userRow) }}" class="d-inline">@csrf @method('DELETE')<button class="btn btn-link btn-sm text-danger">Delete</button></form>
                        @endif
                    </td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $users->links() }}</div>
@endsection
