@extends('layouts.app')
@section('title', 'Budget monitor')
@section('content')
<x-page-header title="Budget monitor" lede="Budget versus approved spend, pending invoices and remaining balance." />

<div class="panel">
    <div class="table-wrap">
        <table class="table setk-table table-sm">
            <thead><tr><th>Budget</th><th>Building</th><th>Year</th><th>Status</th></tr></thead>
            <tbody>
            @forelse($budgets as $budget)
                <tr>
                    <td><a class="fw-semibold" href="{{ route('monitors.show', $budget) }}">{{ $budget->title }}</a></td>
                    <td>{{ $budget->building->name }}</td>
                    <td>{{ $budget->serviceChargeYear->displayLabel() }}</td>
                    <td><x-status-badge :status="$budget->status" /></td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted py-4">No final budgets to monitor.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $budgets->links() }}</div>
@endsection
