<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\Budget;
use App\Models\Client;
use App\Models\Invoice;
use Illuminate\View\View;

class PortfolioController extends Controller
{
    public function index(): View
    {
        $clients = Client::query()
            ->withCount(['buildings'])
            ->orderBy('name')
            ->get();

        $buildings = Building::query()
            ->with('client')
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $finalBudgets = Budget::query()
            ->with(['building', 'serviceChargeYear'])
            ->where('status', Budget::STATUS_FINAL)
            ->latest()
            ->take(10)
            ->get();

        $pendingInvoices = Invoice::query()
            ->with(['building', 'supplier'])
            ->where('status', Invoice::STATUS_SUBMITTED)
            ->latest('submitted_at')
            ->take(10)
            ->get();

        $stats = [
            'clients' => $clients->count(),
            'buildings' => $buildings->count(),
            'final_budgets' => Budget::query()->where('status', Budget::STATUS_FINAL)->count(),
            'pending_invoices' => Invoice::query()->where('status', Invoice::STATUS_SUBMITTED)->count(),
        ];

        return view('portfolio.index', compact('clients', 'buildings', 'finalBudgets', 'pendingInvoices', 'stats'));
    }
}
