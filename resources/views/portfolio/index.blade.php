@extends('layouts.app')

@section('title', 'Portfolio')

@section('content')
<x-page-header title="Portfolio" lede="Clients, buildings and live budget activity across the managed estate.">
    <x-slot:actions>
        @if(auth()->user()->isAdministrator() || auth()->user()->isBlockManager())
            <a href="{{ route('clients.create') }}" class="btn btn-outline-primary">New client</a>
            <a href="{{ route('budgets.create') }}" class="btn btn-primary">Create budget</a>
        @endif
    </x-slot:actions>
</x-page-header>

<div class="metric-grid">
    <div class="metric-card" style="animation-delay:.05s">
        <div class="label">Clients</div>
        <div class="value">{{ $stats['clients'] }}</div>
        <div class="hint">Landlord / RMC / RTM</div>
    </div>
    <div class="metric-card" style="animation-delay:.1s">
        <div class="label">Buildings</div>
        <div class="value">{{ $stats['buildings'] }}</div>
        <div class="hint">Active residential blocks</div>
    </div>
    <div class="metric-card" style="animation-delay:.15s">
        <div class="label">Final budgets</div>
        <div class="value">{{ $stats['final_budgets'] }}</div>
        <div class="hint">Locked working budgets</div>
    </div>
    <div class="metric-card" style="animation-delay:.2s">
        <div class="label">Pending invoices</div>
        <div class="value">{{ $stats['pending_invoices'] }}</div>
        <div class="hint">Awaiting accountant review</div>
    </div>
</div>

<div class="row g-3">
    <div class="col-lg-4">
        <div class="panel h-100">
            <h2 class="panel-title">Clients</h2>
            <div class="stack-list">
                @forelse($clients as $client)
                    <a class="stack-item" href="{{ route('clients.show', $client) }}">
                        <div>
                            <div class="title">{{ $client->name }}</div>
                            <div class="meta">{{ strtoupper($client->type) }}</div>
                        </div>
                        <span class="badge-pill badge-neutral">{{ $client->buildings_count }} bldg</span>
                    </a>
                @empty
                    <div class="empty-state">No clients yet.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="panel h-100">
            <h2 class="panel-title">Buildings</h2>
            <div class="stack-list">
                @forelse($buildings as $building)
                    <a class="stack-item" href="{{ route('buildings.show', $building) }}">
                        <div>
                            <div class="title">{{ $building->name }}</div>
                            <div class="meta">{{ $building->client->name }} · {{ $building->reference }}</div>
                        </div>
                    </a>
                @empty
                    <div class="empty-state">No buildings yet.</div>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="panel mb-3">
            <h2 class="panel-title">Pending invoices</h2>
            <div class="stack-list">
                @forelse($pendingInvoices as $invoice)
                    <a class="stack-item" href="{{ route('invoices.show', $invoice) }}">
                        <div>
                            <div class="title">{{ $invoice->invoice_reference }}</div>
                            <div class="meta">{{ $invoice->building->name }} · {{ $invoice->supplier->name }}</div>
                        </div>
                        <span class="money">{{ \App\Support\Money::formatGbp($invoice->gross_amount) }}</span>
                    </a>
                @empty
                    <div class="empty-state">Nothing waiting for review.</div>
                @endforelse
            </div>
        </div>
        <div class="panel">
            <h2 class="panel-title">Final budgets</h2>
            <div class="stack-list">
                @forelse($finalBudgets as $budget)
                    <a class="stack-item" href="{{ route('budgets.show', $budget) }}">
                        <div>
                            <div class="title">{{ $budget->title }}</div>
                            <div class="meta">{{ $budget->building->name }}</div>
                        </div>
                        <x-status-badge :status="$budget->status" />
                    </a>
                @empty
                    <div class="empty-state">No final budgets yet.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
