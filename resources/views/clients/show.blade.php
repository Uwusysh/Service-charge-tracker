@extends('layouts.app')
@section('title', $client->name)
@section('content')
<x-page-header :title="$client->name" :lede="strtoupper($client->type).' · '.($client->is_active ? 'Active' : 'Inactive')">
    <x-slot:actions>
        @if(auth()->user()->isAdministrator() || auth()->user()->isBlockManager())
            <a href="{{ route('clients.edit', $client) }}" class="btn btn-outline-secondary">Edit</a>
            <a href="{{ route('buildings.create', ['client_id' => $client->id]) }}" class="btn btn-primary">Add building</a>
        @endif
    </x-slot:actions>
</x-page-header>

@if($client->company_details || $client->accountant_details)
<div class="panel mb-3">
    @if($client->company_details)<div class="mb-2"><strong>Company</strong><div class="text-muted">{{ $client->company_details }}</div></div>@endif
    @if($client->accountant_details)<div><strong>Accountant</strong><div class="text-muted">{{ $client->accountant_details }}</div></div>@endif
</div>
@endif

<div class="panel">
    <h2 class="panel-title">Buildings</h2>
    <div class="stack-list">
        @forelse($client->buildings as $building)
            <a class="stack-item" href="{{ route('buildings.show', $building) }}">
                <div>
                    <div class="title">{{ $building->name }}</div>
                    <div class="meta">{{ $building->reference }}</div>
                </div>
            </a>
        @empty
            <div class="empty-state">No buildings for this client.</div>
        @endforelse
    </div>
</div>
@endsection
