<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Services\BudgetMonitorService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BudgetMonitorController extends Controller
{
    public function show(Budget $budget, BudgetMonitorService $monitor): View
    {
        abort_unless($budget->isFinal() || $budget->status === Budget::STATUS_ARCHIVED, 404);

        $budget->load(['building', 'serviceChargeYear', 'lines.schedule', 'lines.costHeading']);
        $rows = $monitor->buildingBreakdown($budget);
        $lineMetrics = $budget->lines->mapWithKeys(
            fn ($line) => [$line->id => $monitor->forLine($line)]
        );

        return view('monitors.show', compact('budget', 'rows', 'lineMetrics'));
    }

    public function index(Request $request): View
    {
        $budgets = Budget::query()
            ->with(['building', 'serviceChargeYear'])
            ->whereIn('status', [Budget::STATUS_FINAL, Budget::STATUS_ARCHIVED])
            ->latest('finalised_at')
            ->paginate(20);

        return view('monitors.index', compact('budgets'));
    }
}
