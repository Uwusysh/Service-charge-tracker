@extends('layouts.app')
@section('title', 'Invoices')
@section('content')
<x-page-header title="Invoices" lede="Supplier invoices against final budgets. Approved amounts reduce remaining budget.">
    <x-slot:actions>
        @if(auth()->user()->isAdministrator() || auth()->user()->isBlockManager())
            <a href="{{ route('invoices.create') }}" class="btn btn-primary">New invoice</a>
        @endif
    </x-slot:actions>
</x-page-header>

<div class="panel">
    <div class="table-wrap">
        <table class="table setk-table table-sm">
            <thead>
                <tr>
                    <th>Reference</th>
                    <th>Building</th>
                    <th>Supplier</th>
                    <th class="text-end">Gross</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            @forelse($invoices as $invoice)
                <tr>
                    <td><a href="{{ route('invoices.show', $invoice) }}" class="fw-semibold">{{ $invoice->invoice_reference }}</a></td>
                    <td>{{ $invoice->building->name }}</td>
                    <td>{{ $invoice->supplier->name }}</td>
                    <td class="text-end money">{{ \App\Support\Money::formatGbp($invoice->gross_amount) }}</td>
                    <td><x-status-badge :status="$invoice->status" /></td>
                </tr>
            @empty
                <tr><td colspan="5" class="text-center text-muted py-4">No invoices yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $invoices->links() }}</div>
@endsection
