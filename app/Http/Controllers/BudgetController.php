<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\BudgetLine;
use App\Models\Building;
use App\Models\ServiceChargeYear;
use App\Services\BudgetCalculationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class BudgetController extends Controller
{
    public function index(Request $request): View
    {
        $budgets = Budget::query()
            ->with(['building', 'serviceChargeYear', 'preparer'])
            ->when($request->integer('building_id'), fn ($q, $id) => $q->where('building_id', $id))
            ->latest()
            ->paginate(20);

        return view('budgets.index', compact('budgets'));
    }

    public function create(Request $request): View
    {
        $buildings = Building::query()->where('is_active', true)->orderBy('name')->get();
        $years = ServiceChargeYear::query()->with('building')->orderByDesc('start_date')->get();
        $selectedBuildingId = $request->integer('building_id') ?: null;

        return view('budgets.create', compact('buildings', 'years', 'selectedBuildingId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'building_id' => ['required', 'exists:buildings,id'],
            'service_charge_year_id' => ['required', 'exists:service_charge_years,id'],
            'title' => ['required', 'string', 'max:255'],
            'overall_notes' => ['nullable', 'string'],
        ]);

        $year = ServiceChargeYear::query()->findOrFail($data['service_charge_year_id']);
        if ((int) $year->building_id !== (int) $data['building_id']) {
            return back()->withInput()->withErrors([
                'service_charge_year_id' => 'Service charge year must belong to the selected building.',
            ]);
        }

        $budget = Budget::query()->create([
            ...$data,
            'status' => Budget::STATUS_DRAFT,
            'prepared_by' => $request->user()->id,
            'prepared_at' => now(),
            'version' => 1,
        ]);

        return redirect()->route('budgets.edit', $budget)->with('success', 'Budget created.');
    }

    public function show(Budget $budget, BudgetCalculationService $calc): View
    {
        $budget->load([
            'building',
            'serviceChargeYear',
            'lines.schedule',
            'lines.costHeading',
            'unitTotals.unit',
            'unitTotals.schedule',
            'preparer',
            'finaliser',
        ]);

        $total = $calc->buildingBudgetTotal($budget);
        $draftTotals = $budget->isFinal()
            ? []
            : $calc->recalculateDraftTotals($budget);

        return view('budgets.show', compact('budget', 'total', 'draftTotals'));
    }

    public function edit(Budget $budget): View|RedirectResponse
    {
        if ($budget->isFinal()) {
            return redirect()->route('budgets.show', $budget)
                ->with('warning', 'Final budgets cannot have amounts changed. View only.');
        }

        if ($budget->status === Budget::STATUS_ARCHIVED) {
            return redirect()->route('budgets.show', $budget)
                ->with('warning', 'Archived budgets are read-only.');
        }

        $budget->load(['building.schedules', 'serviceChargeYear', 'lines.costHeading', 'lines.schedule']);
        $costHeadings = \App\Models\CostHeading::query()->orderBy('sort_order')->get();

        return view('budgets.edit', compact('budget', 'costHeadings'));
    }

    public function update(Request $request, Budget $budget): RedirectResponse
    {
        if (! $budget->isEditable()) {
            abort(403, 'This budget cannot be edited.');
        }

        // Accountants may update approval header fields on ready budgets; block managers edit draft content.
        $data = $request->validate([
            'title' => ['required', 'string', 'max:255'],
            'overall_notes' => ['nullable', 'string'],
            'authorised_approver' => ['nullable', 'string', 'max:255'],
            'authorised_at' => ['nullable', 'date'],
            'authorised_capacity' => ['nullable', 'string', 'max:255'],
        ]);

        $budget->update($data);

        return redirect()->route('budgets.edit', $budget)->with('success', 'Budget updated.');
    }

    public function copy(Budget $budget, Request $request): RedirectResponse
    {
        $newBudget = DB::transaction(function () use ($budget, $request) {
            $budget->load('lines');

            $copy = Budget::query()->create([
                'building_id' => $budget->building_id,
                'service_charge_year_id' => $budget->service_charge_year_id,
                'title' => $budget->title.' (Copy)',
                'status' => Budget::STATUS_DRAFT,
                'prepared_by' => $request->user()->id,
                'prepared_at' => now(),
                'overall_notes' => $budget->overall_notes,
                'copied_from_budget_id' => $budget->id,
                'version' => $budget->version + 1,
            ]);

            foreach ($budget->lines as $line) {
                BudgetLine::query()->create([
                    'budget_id' => $copy->id,
                    'schedule_id' => $line->schedule_id,
                    'cost_heading_id' => $line->cost_heading_id,
                    'description' => $line->description,
                    'previous_budget' => $line->current_estimate,
                    'previous_actual' => $line->previous_actual,
                    'current_estimate' => $line->current_estimate,
                    'is_reserve' => $line->is_reserve,
                    'basis_explanation' => null,
                ]);
            }

            return $copy;
        });

        return redirect()->route('budgets.edit', $newBudget)->with('success', 'Budget copied as a new draft.');
    }

    public function markReady(Budget $budget): RedirectResponse
    {
        if ($budget->status !== Budget::STATUS_DRAFT) {
            return back()->withErrors(['status' => 'Only draft budgets can be marked ready for review.']);
        }

        if ($budget->lines()->count() === 0) {
            return back()->withErrors(['status' => 'Add budget lines before marking ready for review.']);
        }

        $budget->update(['status' => Budget::STATUS_READY]);

        return redirect()->route('budgets.show', $budget)->with('success', 'Budget marked ready for review.');
    }

    public function finalise(Budget $budget, BudgetCalculationService $calc, Request $request): RedirectResponse
    {
        if (! $request->user()->canFinaliseBudget()) {
            abort(403, 'You cannot finalise budgets.');
        }

        if (! in_array($budget->status, [Budget::STATUS_DRAFT, Budget::STATUS_READY], true)) {
            return back()->withErrors(['status' => 'Only draft or ready budgets can be finalised.']);
        }

        $validation = $calc->validateForFinal($budget);

        if ($validation['errors'] !== []) {
            return back()->withErrors(['finalise' => $validation['errors']]);
        }

        DB::transaction(function () use ($budget, $calc, $request) {
            $calc->freezeUnitTotals($budget);

            $budget->update([
                'status' => Budget::STATUS_FINAL,
                'finalised_by' => $request->user()->id,
                'finalised_at' => now(),
            ]);
        });

        $message = 'Budget finalised and unit contributions frozen.';
        if ($validation['warnings'] !== []) {
            $message .= ' Warnings: '.implode(' ', $validation['warnings']);
        }

        return redirect()->route('budgets.show', $budget)->with('success', $message);
    }

    public function archive(Budget $budget): RedirectResponse
    {
        if ($budget->status !== Budget::STATUS_FINAL) {
            return back()->withErrors(['status' => 'Only Final budgets can be archived.']);
        }

        $budget->update(['status' => Budget::STATUS_ARCHIVED]);

        return redirect()->route('budgets.show', $budget)->with('success', 'Budget archived.');
    }
}
