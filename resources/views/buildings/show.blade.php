@extends('layouts.app')
@section('title', $building->name)
@section('content')
<x-page-header :title="$building->name" :lede="$building->fullAddress().' · '.$building->reference">
    <p class="lede mb-0 mt-1"><a href="{{ route('clients.show', $building->client) }}">{{ $building->client->name }}</a></p>
    <x-slot:actions>
        @if(auth()->user()->isAdministrator() || auth()->user()->isBlockManager())
            <a href="{{ route('buildings.edit', $building) }}" class="btn btn-outline-secondary">Edit</a>
            <a href="{{ route('units.create', $building) }}" class="btn btn-outline-primary">Add flat</a>
            <a href="{{ route('years.create', $building) }}" class="btn btn-outline-primary">Add year</a>
            <a href="{{ route('schedules.create', $building) }}" class="btn btn-outline-primary">Add schedule</a>
            <a href="{{ route('budgets.create', ['building_id' => $building->id]) }}" class="btn btn-primary">New budget</a>
        @endif
    </x-slot:actions>
</x-page-header>

<div class="row g-3">
    <div class="col-md-4">
        <div class="panel h-100">
            <h2 class="panel-title">Flats / units</h2>
            <div class="stack-list">
                @forelse($building->units as $unit)
                    <div class="stack-item">
                        <div>
                            <div class="title">{{ $unit->unit_reference }}</div>
                            @if($unit->description)<div class="meta">{{ $unit->description }}</div>@endif
                        </div>
                        @if(auth()->user()->isAdministrator() || auth()->user()->isBlockManager())
                            <a href="{{ route('units.edit', $unit) }}">Edit</a>
                        @endif
                    </div>
                @empty
                    <div class="empty-state">No flats yet.</div>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="panel h-100">
            <h2 class="panel-title">Service-charge years</h2>
            <div class="stack-list">
                @forelse($building->serviceChargeYears as $year)
                    <div class="stack-item">
                        <div>
                            <div class="title">{{ $year->displayLabel() }}</div>
                            <div class="meta"><x-status-badge :status="$year->status" /></div>
                        </div>
                        @if(auth()->user()->isAdministrator() || auth()->user()->isBlockManager())
                            <a href="{{ route('years.edit', $year) }}">Edit</a>
                        @endif
                    </div>
                @empty
                    <div class="empty-state">No years yet.</div>
                @endforelse
            </div>
        </div>
    </div>
    <div class="col-md-4">
        <div class="panel mb-3">
            <h2 class="panel-title">Schedules</h2>
            <div class="stack-list">
                @forelse($building->schedules as $schedule)
                    <a class="stack-item" href="{{ route('schedules.show', $schedule) }}">
                        <div>
                            <div class="title">{{ $schedule->name }}</div>
                            <div class="meta">{{ ucfirst($schedule->allocation_method) }} shares</div>
                        </div>
                    </a>
                @empty
                    <div class="empty-state">No schedules yet.</div>
                @endforelse
            </div>
        </div>
        <div class="panel">
            <h2 class="panel-title">Budgets</h2>
            <div class="stack-list">
                @forelse($building->budgets as $budget)
                    <a class="stack-item" href="{{ route('budgets.show', $budget) }}">
                        <div>
                            <div class="title">{{ $budget->title }}</div>
                        </div>
                        <x-status-badge :status="$budget->status" />
                    </a>
                @empty
                    <div class="empty-state">No budgets yet.</div>
                @endforelse
            </div>
        </div>
    </div>
</div>
@endsection
