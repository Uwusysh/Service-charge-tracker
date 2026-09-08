@extends('layouts.app')
@section('title', 'Monitor '.$budget->title)
@section('content')
@php
    $buildingRow = collect($rows)->firstWhere('level', 'building') ?? ($rows[0] ?? null);
@endphp

<x-page-header :title="$budget->title" :lede="$budget->building->name.' · '.$budget->serviceChargeYear->displayLabel()">
    <x-slot:actions>
        <a href="{{ route('budgets.show', $budget) }}" class="btn btn-outline-secondary">Budget</a>
        <a href="{{ route('exports.excel', $budget) }}" class="btn btn-outline-primary">Excel</a>
        <a href="{{ route('exports.pdf', $budget) }}" class="btn btn-primary">PDF</a>
    </x-slot:actions>
</x-page-header>

@if($buildingRow)
<div class="metric-grid">
    <div class="metric-card">
        <div class="label">Annual budget</div>
        <div class="value" style="font-size:1.45rem">{{ \App\Support\Money::formatGbp($buildingRow['budget']) }}</div>
    </div>
    <div class="metric-card">
        <div class="label">Approved spend</div>
        <div class="value" style="font-size:1.45rem">{{ \App\Support\Money::formatGbp($buildingRow['approved_spent']) }}</div>
        <div class="hint">{{ $buildingRow['pct_used'] }}% used</div>
        <div class="progress-slim {{ $buildingRow['overspend'] ? 'over' : '' }} mt-2">
            <span style="width: {{ min(100, max(0, (float) $buildingRow['pct_used'])) }}%"></span>
        </div>
    </div>
    <div class="metric-card">
        <div class="label">Pending</div>
        <div class="value" style="font-size:1.45rem">{{ \App\Support\Money::formatGbp($buildingRow['pending_amount']) }}</div>
        <div class="hint">Submitted, not yet approved</div>
    </div>
    <div class="metric-card">
        <div class="label">Remaining</div>
        <div class="value {{ $buildingRow['overspend'] ? 'money-neg' : '' }}" style="font-size:1.45rem">{{ \App\Support\Money::formatGbp($buildingRow['remaining']) }}</div>
        <div class="hint">Forecast {{ \App\Support\Money::formatGbp($buildingRow['forecast_remaining']) }}</div>
    </div>
</div>
@endif

<div class="panel">
    <h2 class="panel-title">By schedule</h2>
    <div class="table-wrap">
        <table class="table setk-table table-sm">
            <thead>
                <tr>
                    <th>Level</th>
                    <th>Label</th>
                    <th class="text-end">Budget</th>
                    <th class="text-end">Approved</th>
                    <th class="text-end">Pending</th>
                    <th class="text-end">Remaining</th>
                    <th class="text-end">Forecast</th>
                    <th class="text-end">% used</th>
                </tr>
            </thead>
            <tbody>
            @foreach($rows as $row)
                <tr class="{{ !empty($row['overspend']) ? 'table-danger' : '' }}">
                    <td><span class="badge-pill badge-neutral">{{ $row['level'] }}</span></td>
                    <td>{{ $row['label'] }}</td>
                    <td class="text-end money">{{ \App\Support\Money::formatGbp($row['budget']) }}</td>
                    <td class="text-end money">{{ \App\Support\Money::formatGbp($row['approved_spent']) }}</td>
                    <td class="text-end money">{{ \App\Support\Money::formatGbp($row['pending_amount']) }}</td>
                    <td class="text-end money {{ !empty($row['overspend']) ? 'money-neg' : '' }}">{{ \App\Support\Money::formatGbp($row['remaining']) }}</td>
                    <td class="text-end money">{{ \App\Support\Money::formatGbp($row['forecast_remaining']) }}</td>
                    <td class="text-end">{{ $row['pct_used'] }}%</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>

<div class="panel mt-3">
    <h2 class="panel-title">By budget line</h2>
    <div class="table-wrap">
        <table class="table setk-table table-sm">
            <thead>
                <tr>
                    <th>Line</th>
                    <th class="text-end">Budget</th>
                    <th class="text-end">Approved</th>
                    <th class="text-end">Pending</th>
                    <th class="text-end">Remaining</th>
                </tr>
            </thead>
            <tbody>
            @foreach($budget->lines as $line)
                @php($m = $lineMetrics[$line->id])
                <tr class="{{ !empty($m['overspend']) ? 'table-warning' : '' }}">
                    <td>
                        <strong>{{ $line->costHeading->code }}</strong>
                        <span class="text-muted">/ {{ $line->schedule->name }}</span>
                    </td>
                    <td class="text-end money">{{ \App\Support\Money::formatGbp($m['budget']) }}</td>
                    <td class="text-end money">{{ \App\Support\Money::formatGbp($m['approved_spent']) }}</td>
                    <td class="text-end money">{{ \App\Support\Money::formatGbp($m['pending_amount']) }}</td>
                    <td class="text-end money {{ !empty($m['overspend']) ? 'money-neg' : '' }}">{{ \App\Support\Money::formatGbp($m['remaining']) }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    <p class="small text-muted mb-0 mt-3">Overspend is allowed and highlighted. Red/amber rows indicate remaining below zero.</p>
</div>
@endsection
