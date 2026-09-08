@extends('layouts.app')
@section('title', $invoice->invoice_reference)
@section('content')
<x-page-header :title="'Invoice '.$invoice->invoice_reference">
    <p class="lede mb-0">
        {{ $invoice->building->name }} · {{ $invoice->supplier->name }}
        · <x-status-badge :status="$invoice->status" />
    </p>
</x-page-header>

<div class="row g-3">
    <div class="col-lg-7">
        <div class="panel">
            <h2 class="panel-title">Amounts</h2>
            <div class="row g-3">
                <div class="col-4">
                    <div class="text-muted small text-uppercase fw-bold" style="letter-spacing:.05em">Net</div>
                    <div class="money fs-5">{{ \App\Support\Money::formatGbp($invoice->net_amount) }}</div>
                </div>
                <div class="col-4">
                    <div class="text-muted small text-uppercase fw-bold" style="letter-spacing:.05em">VAT</div>
                    <div class="money fs-5">{{ \App\Support\Money::formatGbp($invoice->vat_amount) }}</div>
                </div>
                <div class="col-4">
                    <div class="text-muted small text-uppercase fw-bold" style="letter-spacing:.05em">Gross</div>
                    <div class="money fs-4">{{ \App\Support\Money::formatGbp($invoice->gross_amount) }}</div>
                </div>
            </div>
            <hr>
            <div class="mb-2"><strong>Budget line:</strong> {{ $invoice->budgetLine->costHeading->code }} — {{ $invoice->budgetLine->schedule->name }}</div>
            <div class="mb-2"><strong>Invoice date:</strong> {{ optional($invoice->invoice_date)->format('d M Y') }}</div>
            @if($invoice->description)
                <div class="text-muted">{{ $invoice->description }}</div>
            @endif
        </div>

        @if($invoice->files->isNotEmpty())
        <div class="panel mt-3">
            <h2 class="panel-title">Attachments</h2>
            <div class="stack-list">
                @foreach($invoice->files as $file)
                    <a class="stack-item" href="{{ route('invoice-files.download', $file) }}">
                        <div class="title">{{ $file->original_name }}</div>
                        <span class="badge-pill badge-neutral">Download</span>
                    </a>
                @endforeach
            </div>
        </div>
        @endif
    </div>

    <div class="col-lg-5">
        <div class="panel">
            <h2 class="panel-title">Review actions</h2>
            <div class="d-flex flex-column gap-2">
                @if((auth()->user()->isAdministrator() || auth()->user()->isBlockManager()) && in_array($invoice->status, ['draft','rejected'], true))
                    <form method="POST" action="{{ route('invoices.submit', $invoice) }}">
                        @csrf
                        <button class="btn btn-primary w-100">Submit for review</button>
                    </form>
                @endif

                @if(auth()->user()->canApproveInvoice() && $invoice->status === 'submitted')
                    <form method="POST" action="{{ route('invoices.approve', $invoice) }}">
                        @csrf
                        <button class="btn btn-success w-100">Approve</button>
                    </form>
                    <form method="POST" action="{{ route('invoices.reject', $invoice) }}" class="vstack gap-2">
                        @csrf
                        <input name="rejection_reason" class="form-control" placeholder="Rejection reason" required>
                        <button class="btn btn-outline-danger w-100">Reject</button>
                    </form>
                @endif

                @if(auth()->user()->canApproveInvoice() && $invoice->status === 'approved')
                    <form method="POST" action="{{ route('invoices.reverse', $invoice) }}" class="vstack gap-2">
                        @csrf
                        <input name="reverse_reason" class="form-control" placeholder="Reverse reason" required>
                        <button class="btn btn-outline-warning w-100">Reverse approval</button>
                    </form>
                @endif

                @if(! auth()->user()->canApproveInvoice() && $invoice->status === 'submitted')
                    <p class="text-muted mb-0 small">Waiting for accountant review.</p>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection
