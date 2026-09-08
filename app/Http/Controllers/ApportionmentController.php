<?php

namespace App\Http\Controllers;

use App\Models\Schedule;
use App\Services\ApportionmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ApportionmentController extends Controller
{
    public function applyEqual(Schedule $schedule, ApportionmentService $apportionment): RedirectResponse
    {
        $apportionment->applyEqualShares($schedule->load('scheduleUnits'));

        return back()->with('success', 'Equal shares applied.');
    }

    public function updateManual(Request $request, Schedule $schedule, ApportionmentService $apportionment): RedirectResponse
    {
        $data = $request->validate([
            'percentages' => ['required', 'array'],
            'percentages.*.unit_id' => ['required', 'integer', 'exists:units,id'],
            'percentages.*.percentage' => ['required', 'numeric', 'min:0', 'max:100'],
        ]);

        $apportionment->syncManualPercentages($schedule, $data['percentages']);

        $check = $apportionment->validateScheduleTotals($schedule->fresh('scheduleUnits'));

        if (! $check['valid']) {
            return back()->with('warning', $check['message']);
        }

        return back()->with('success', 'Manual percentages saved. Totals validate at 100%.');
    }
}
