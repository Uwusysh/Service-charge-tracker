@extends('layouts.app')
@section('title', $budget->title)
@section('content')
<x-page-header :title="$budget->title">
    <p class="lede mb-0">
        {{ $budget->building->name }} · {{ $budget->serviceChargeYear->displayLabel() }}
        · <x-status-badge :status="$budget->status" />
    </p>
    <p class="lede mt-2 mb-0">Building total <span class="money">{{ \App\Support\Money::formatGbp($total) }}</span></p>
    <x-slot:actions>
        @if($budget->isEditable())
            <a href="{{ route('budgets.edit', $budget) }}" class="btn btn-outline-secondary">Edit</a>
        @endif
        @if((auth()->user()->isAdministrator() || auth()->user()->isBlockManager()) && $budget->isDraft())
            <form method="POST" action="{{ route('budgets.mark-ready', $budget) }}">@csrf<button class="btn btn-outline-primary">Mark ready</button></form>
        @endif
        @if(auth()->user()->canFinaliseBudget() && $budget->isEditable())
            <form method="POST" action="{{ route('budgets.finalise', $budget) }}">@csrf<button class="btn btn-success">Finalise</button></form>
        @endif
        @if(auth()->user()->canFinaliseBudget() && $budget->isFinal())
            <form method="POST" action="{{ route('budgets.archive', $budget) }}">@csrf<button class="btn btn-outline-danger">Archive</button></form>
            <a href="{{ route('exports.excel', $budget) }}" class="btn btn-outline-primary">Excel</a>
            <a href="{{ route('exports.pdf', $budget) }}" class="btn btn-outline-primary">PDF</a>
            <a href="{{ route('monitors.show', $budget) }}" class="btn btn-primary">Monitor</a>
        @endif
        @if(auth()->user()->isAdministrator() || auth()->user()->isBlockManager())
            <form method="POST" action="{{ route('budgets.copy', $budget) }}">@csrf<button class="btn btn-outline-secondary">Copy</button></form>
        @endif
    </x-slot:actions>
</x-page-header>

<div class="panel mb-3">
    <div class="row g-3 small">
        <div class="col-md-4">
            <div class="text-uppercase text-muted fw-bold" style="letter-spacing:.05em;font-size:.72rem;">Authorised approver</div>
            <div>{{ $budget->authorised_approver ?: '—' }}</div>
            <div class="text-muted">{{ $budget->authorised_capacity ?: '—' }} {{ optional($budget->authorised_at)->format('d M Y') }}</div>
        </div>
        <div class="col-md-4">
            <div class="text-uppercase text-muted fw-bold" style="letter-spacing:.05em;font-size:.72rem;">Prepared</div>
            <div>{{ optional($budget->preparer)->name ?: '—' }}</div>
            <div class="text-muted">{{ optional($budget->prepared_at)->format('d M Y') }}</div>
        </div>
        <div class="col-md-4">
            <div class="text-uppercase text-muted fw-bold" style="letter-spacing:.05em;font-size:.72rem;">Finalised</div>
            <div>{{ optional($budget->finaliser)->name ?: '—' }}</div>
            <div class="text-muted">{{ optional($budget->finalised_at)->format('d M Y') }}</div>
        </div>
    </div>
    @if($budget->overall_notes)
        <hr class="my-3">
        <div class="text-muted">{{ $budget->overall_notes }}</div>
    @endif
</div>

<div class="panel">
    <h2 class="panel-title">Budget lines</h2>
    <div class="table-wrap">
        <table class="table setk-table table-sm">
            <thead>
                <tr>
                    <th>Schedule</th>
                    <th>Heading</th>
                    <th class="text-end">Previous</th>
                    <th class="text-end">Estimate</th>
                    <th>Reserve</th>
                </tr>
            </thead>
            <tbody>
            @forelse($budget->lines as $line)
                <tr>
                    <td>{{ $line->schedule->name }}</td>
                    <td>
                        <strong>{{ $line->costHeading->code }}</strong>
                        <span class="text-muted">— {{ $line->costHeading->name }}</span>
                        @if($line->basis_explanation)
                            <div class="small text-muted">{{ $line->basis_explanation }}</div>
                        @endif
                    </td>
                    <td class="text-end money text-muted">{{ $line->previous_budget !== null ? \App\Support\Money::formatGbp($line->previous_budget) : '—' }}</td>
                    <td class="text-end money">{{ \App\Support\Money::formatGbp($line->current_estimate) }}</td>
                    <td>@if($line->is_reserve)<span class="badge-pill badge-ready">Reserve</span>@endif</td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-4">No lines yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>

@if($budget->unitTotals->isNotEmpty())
<div class="panel mt-3">
    <h2 class="panel-title">Frozen unit contributions</h2>
    <div class="table-wrap">
        <table class="table setk-table table-sm">
            <thead>
                <tr>
                    <th>Schedule</th>
                    <th>Unit</th>
                    <th class="text-end">%</th>
                    <th class="text-end">Contribution</th>
                    <th></th>
                </tr>
            </thead>
            <tbody>
            @foreach($budget->unitTotals as $row)
                <tr>
                    <td>{{ $row->schedule->name }}</td>
                    <td>{{ $row->unit->unit_reference }}</td>
                    <td class="text-end money">{{ number_format((float) $row->percentage, 6) }}</td>
                    <td class="text-end money">{{ \App\Support\Money::formatGbp($row->contribution) }}</td>
                    <td>@if($row->is_balancing_adjustment)<span class="badge-pill badge-neutral">Balancing</span>@endif</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endif
@endsection
