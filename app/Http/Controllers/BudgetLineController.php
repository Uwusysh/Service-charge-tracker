<?php

namespace App\Http\Controllers;

use App\Models\Budget;
use App\Models\BudgetLine;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BudgetLineController extends Controller
{
    public function store(Request $request, Budget $budget): RedirectResponse
    {
        $this->ensureEditable($budget, $request);

        $data = $request->validate([
            'schedule_id' => ['required', 'exists:schedules,id'],
            'cost_heading_id' => ['required', 'exists:cost_headings,id'],
            'description' => ['nullable', 'string', 'max:255'],
            'previous_budget' => ['nullable', 'numeric'],
            'previous_actual' => ['nullable', 'numeric'],
            'current_estimate' => ['required', 'numeric', 'min:0'],
            'is_reserve' => ['sometimes', 'boolean'],
            'basis_explanation' => ['nullable', 'string'],
        ]);

        $data['budget_id'] = $budget->id;
        $data['is_reserve'] = $request->boolean('is_reserve');
        $data['current_estimate'] = number_format((float) $data['current_estimate'], 2, '.', '');
        if (isset($data['previous_budget'])) {
            $data['previous_budget'] = number_format((float) $data['previous_budget'], 2, '.', '');
        }
        if (isset($data['previous_actual'])) {
            $data['previous_actual'] = number_format((float) $data['previous_actual'], 2, '.', '');
        }

        BudgetLine::query()->create($data);

        return back()->with('success', 'Budget line added.');
    }

    public function update(Request $request, BudgetLine $budgetLine): RedirectResponse
    {
        $budget = $budgetLine->budget;
        $this->ensureEditable($budget, $request);

        $data = $request->validate([
            'schedule_id' => ['required', 'exists:schedules,id'],
            'cost_heading_id' => ['required', 'exists:cost_headings,id'],
            'description' => ['nullable', 'string', 'max:255'],
            'previous_budget' => ['nullable', 'numeric'],
            'previous_actual' => ['nullable', 'numeric'],
            'current_estimate' => ['required', 'numeric', 'min:0'],
            'is_reserve' => ['sometimes', 'boolean'],
            'basis_explanation' => ['nullable', 'string'],
        ]);

        $data['is_reserve'] = $request->boolean('is_reserve');
        $data['current_estimate'] = number_format((float) $data['current_estimate'], 2, '.', '');
        if (array_key_exists('previous_budget', $data) && $data['previous_budget'] !== null) {
            $data['previous_budget'] = number_format((float) $data['previous_budget'], 2, '.', '');
        }
        if (array_key_exists('previous_actual', $data) && $data['previous_actual'] !== null) {
            $data['previous_actual'] = number_format((float) $data['previous_actual'], 2, '.', '');
        }

        $budgetLine->update($data);

        return back()->with('success', 'Budget line updated.');
    }

    public function destroy(Request $request, BudgetLine $budgetLine): RedirectResponse
    {
        $budget = $budgetLine->budget;
        $this->ensureEditable($budget, $request);
        $budgetLine->delete();

        return back()->with('success', 'Budget line removed.');
    }

    private function ensureEditable(Budget $budget, Request $request): void
    {
        if ($budget->isFinal()) {
            abort(403, 'Final budget amounts cannot be changed.');
        }

        if (! $budget->isEditable()) {
            abort(403, 'This budget cannot be edited.');
        }

        // Block managers and admins can edit draft/ready line amounts.
        // Accountants may edit ready-for-review notes/explanations but not invent Final amounts.
        if ($request->user()->isAccountant() && ! $request->user()->isAdministrator() && $budget->isDraft()) {
            // Accountants typically work ready/finalise — still allow draft line edits for POC flexibility via admin; accountants focus on review.
        }
    }
}
