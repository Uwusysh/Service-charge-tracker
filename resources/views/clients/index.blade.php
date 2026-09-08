@extends('layouts.app')
@section('title', 'Clients')
@section('content')
<x-page-header title="Clients" lede="Freeholders, landlords, RMCs and RTMs that appointed the manager.">
    <x-slot:actions>
        @if(auth()->user()->isAdministrator() || auth()->user()->isBlockManager())
            <a href="{{ route('clients.create') }}" class="btn btn-primary">New client</a>
        @endif
    </x-slot:actions>
</x-page-header>

<div class="panel">
    <div class="table-wrap">
        <table class="table setk-table table-sm">
            <thead><tr><th>Name</th><th>Type</th><th>Status</th></tr></thead>
            <tbody>
            @forelse($clients as $client)
                <tr>
                    <td><a class="fw-semibold" href="{{ route('clients.show', $client) }}">{{ $client->name }}</a></td>
                    <td>{{ strtoupper($client->type) }}</td>
                    <td>
                        @if($client->is_active)
                            <span class="badge-pill badge-final">Active</span>
                        @else
                            <span class="badge-pill badge-archived">Inactive</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="3" class="text-center text-muted py-4">No clients yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $clients->links() }}</div>
@endsection
