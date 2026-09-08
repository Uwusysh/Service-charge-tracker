<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\Invoice;
use App\Models\InvoiceFile;
use App\Models\Supplier;
use App\Services\InvoiceService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class InvoiceController extends Controller
{
    public function index(Request $request): View
    {
        $invoices = Invoice::query()
            ->with(['building', 'supplier', 'budgetLine.costHeading'])
            ->when($request->string('status')->toString(), fn ($q, $status) => $q->where('status', $status))
            ->latest()
            ->paginate(25);

        return view('invoices.index', compact('invoices'));
    }

    public function create(Request $request): View
    {
        $budgets = Budget::query()
            ->with(['building', 'serviceChargeYear', 'lines.costHeading', 'lines.schedule'])
            ->where('status', Budget::STATUS_FINAL)
            ->orderByDesc('finalised_at')
            ->get();

        $suppliers = Supplier::query()->where('is_active', true)->orderBy('name')->get();
        $selectedBudgetId = $request->integer('budget_id') ?: null;

        return view('invoices.create', compact('budgets', 'suppliers', 'selectedBudgetId'));
    }

    public function store(Request $request, InvoiceService $invoices): RedirectResponse
    {
        $data = $request->validate([
            'budget_id' => ['required', 'exists:budgets,id'],
            'budget_line_id' => ['required', 'exists:budget_lines,id'],
            'supplier_id' => ['required', 'exists:suppliers,id'],
            'invoice_reference' => ['required', 'string', 'max:100'],
            'invoice_date' => ['required', 'date'],
            'net_amount' => ['required', 'numeric'],
            'vat_amount' => ['required', 'numeric', 'min:0'],
            'description' => ['nullable', 'string'],
            'attachment' => ['nullable', 'file', 'max:10240'],
        ]);

        $budget = Budget::query()->with('lines')->findOrFail($data['budget_id']);

        if (! $budget->isFinal()) {
            return back()->withInput()->withErrors([
                'budget_id' => 'Invoices can only be raised against Final budgets.',
            ]);
        }

        $line = $budget->lines->firstWhere('id', (int) $data['budget_line_id']);
        if (! $line) {
            return back()->withInput()->withErrors([
                'budget_line_id' => 'Budget line must belong to the selected budget.',
            ]);
        }

        $net = number_format((float) $data['net_amount'], 2, '.', '');
        $vat = number_format((float) $data['vat_amount'], 2, '.', '');
        $gross = $invoices->calculateGross($net, $vat);
        $normalized = Invoice::normalizeReference($data['invoice_reference']);

        $invoice = Invoice::query()->create([
            'building_id' => $budget->building_id,
            'service_charge_year_id' => $budget->service_charge_year_id,
            'budget_id' => $budget->id,
            'budget_line_id' => $line->id,
            'supplier_id' => $data['supplier_id'],
            'invoice_reference' => $data['invoice_reference'],
            'normalized_reference' => $normalized,
            'invoice_date' => $data['invoice_date'],
            'net_amount' => $net,
            'vat_amount' => $vat,
            'gross_amount' => $gross,
            'status' => Invoice::STATUS_DRAFT,
            'description' => $data['description'] ?? null,
            'submitted_by' => $request->user()->id,
        ]);

        if ($request->hasFile('attachment')) {
            $this->storeAttachment($invoice, $request->file('attachment'));
        }

        return redirect()->route('invoices.show', $invoice)->with('success', 'Invoice created as draft.');
    }

    public function show(Invoice $invoice): View
    {
        $invoice->load([
            'building',
            'supplier',
            'budget',
            'budgetLine.costHeading',
            'budgetLine.schedule',
            'files',
            'submitter',
            'reviewer',
        ]);

        return view('invoices.show', compact('invoice'));
    }

    public function submit(Invoice $invoice, InvoiceService $invoices, Request $request): RedirectResponse
    {
        $invoices->submit($invoice, $request->user());

        return back()->with('success', 'Invoice submitted for approval.');
    }

    public function approve(Invoice $invoice, InvoiceService $invoices, Request $request): RedirectResponse
    {
        $data = $request->validate([
            'review_note' => ['nullable', 'string'],
        ]);

        $invoices->approve($invoice, $request->user(), $data['review_note'] ?? null);

        return back()->with('success', 'Invoice approved.');
    }

    public function reject(Invoice $invoice, InvoiceService $invoices, Request $request): RedirectResponse
    {
        $data = $request->validate([
            'rejection_reason' => ['required', 'string'],
        ]);

        $invoices->reject($invoice, $request->user(), $data['rejection_reason']);

        return back()->with('success', 'Invoice rejected.');
    }

    public function reverse(Invoice $invoice, InvoiceService $invoices, Request $request): RedirectResponse
    {
        $data = $request->validate([
            'reverse_reason' => ['required', 'string'],
        ]);

        $invoices->reverseApproval($invoice, $request->user(), $data['reverse_reason']);

        return back()->with('success', 'Invoice approval reversed.');
    }

    public function download(InvoiceFile $invoiceFile): StreamedResponse
    {
        abort_unless(auth()->check(), 403);

        if (! Storage::disk($invoiceFile->disk)->exists($invoiceFile->path)) {
            abort(404, 'File not found.');
        }

        return Storage::disk($invoiceFile->disk)->download(
            $invoiceFile->path,
            $invoiceFile->original_name
        );
    }

    private function storeAttachment(Invoice $invoice, $file): void
    {
        $path = $file->store('invoices/'.$invoice->id, 'local');

        InvoiceFile::query()->create([
            'invoice_id' => $invoice->id,
            'disk' => 'local',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime' => $file->getClientMimeType(),
            'size' => $file->getSize() ?: 0,
        ]);
    }
}
