<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\Schedule;
use App\Models\ScheduleUnit;
use App\Services\ApportionmentService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ScheduleController extends Controller
{
    public function create(Building $building): View
    {
        $building->load('units');

        return view('schedules.create', compact('building'));
    }

    public function store(Request $request, Building $building, ApportionmentService $apportionment): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'allocation_method' => ['required', 'in:equal,manual'],
            'balancing_unit_id' => ['nullable', 'exists:units,id'],
            'unit_ids' => ['required', 'array', 'min:1'],
            'unit_ids.*' => ['integer', 'exists:units,id'],
        ]);

        $schedule = Schedule::query()->create([
            'building_id' => $building->id,
            'name' => $data['name'],
            'allocation_method' => $data['allocation_method'],
            'balancing_unit_id' => $data['balancing_unit_id'] ?? null,
        ]);

        foreach ($data['unit_ids'] as $unitId) {
            ScheduleUnit::query()->create([
                'schedule_id' => $schedule->id,
                'unit_id' => $unitId,
                'percentage' => '0.000000',
            ]);
        }

        if ($data['allocation_method'] === 'equal') {
            $apportionment->applyEqualShares($schedule->fresh('scheduleUnits'));
        }

        return redirect()->route('schedules.show', $schedule)->with('success', 'Schedule created.');
    }

    public function show(Schedule $schedule): View
    {
        $schedule->load(['building.units', 'scheduleUnits.unit', 'balancingUnit']);
        $validation = app(ApportionmentService::class)->validateScheduleTotals($schedule);

        return view('schedules.show', compact('schedule', 'validation'));
    }

    public function edit(Schedule $schedule): View
    {
        $schedule->load(['building.units', 'scheduleUnits']);

        return view('schedules.edit', compact('schedule'));
    }

    public function update(Request $request, Schedule $schedule): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'allocation_method' => ['required', 'in:equal,manual'],
            'balancing_unit_id' => ['nullable', 'exists:units,id'],
        ]);

        $schedule->update($data);

        return redirect()->route('schedules.show', $schedule)->with('success', 'Schedule updated.');
    }
}
