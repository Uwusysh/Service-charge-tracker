@extends('layouts.app')
@section('title', 'New invoice')
@section('content')
<x-page-header title="New invoice" lede="Submit against a Final budget only. Gross must equal net + VAT." />

<div class="panel" style="max-width:720px">
    <form method="POST" action="{{ route('invoices.store') }}" enctype="multipart/form-data" class="row g-3">
        @csrf
        <div class="col-12">
            <label class="form-label">Budget</label>
            <select name="budget_id" id="budget_id" class="form-select" required>
                @foreach($budgets as $budget)
                    <option value="{{ $budget->id }}" @selected(old('budget_id', $selectedBudgetId)==$budget->id)>{{ $budget->building->name }} — {{ $budget->title }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-12">
            <label class="form-label">Budget line</label>
            <select name="budget_line_id" class="form-select" required>
                @foreach($budgets as $budget)
                    @foreach($budget->lines as $line)
                        <option value="{{ $line->id }}">[{{ $budget->title }}] {{ $line->schedule->name }} / {{ $line->costHeading->code }} ({{ \App\Support\Money::formatGbp($line->current_estimate) }})</option>
                    @endforeach
                @endforeach
            </select>
        </div>
        <div class="col-md-6">
            <label class="form-label">Supplier</label>
            <select name="supplier_id" class="form-select" required>
                @foreach($suppliers as $supplier)
                    <option value="{{ $supplier->id }}">{{ $supplier->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-6"><label class="form-label">Invoice reference</label><input name="invoice_reference" class="form-control" value="{{ old('invoice_reference') }}" required></div>
        <div class="col-md-4"><label class="form-label">Invoice date</label><input type="date" name="invoice_date" class="form-control" value="{{ old('invoice_date') }}" required></div>
        <div class="col-md-4"><label class="form-label">Net (£)</label><input name="net_amount" class="form-control" value="{{ old('net_amount') }}" required></div>
        <div class="col-md-4"><label class="form-label">VAT (£)</label><input name="vat_amount" class="form-control" value="{{ old('vat_amount', '0.00') }}" required></div>
        <div class="col-12"><label class="form-label">Description</label><textarea name="description" class="form-control" rows="2">{{ old('description') }}</textarea></div>
        <div class="col-12"><label class="form-label">Attachment (PDF / JPG / PNG)</label><input type="file" name="attachment" class="form-control" accept=".pdf,.jpg,.jpeg,.png"></div>
        <div class="col-12"><button class="btn btn-primary">Create draft</button></div>
    </form>
</div>
@endsection
