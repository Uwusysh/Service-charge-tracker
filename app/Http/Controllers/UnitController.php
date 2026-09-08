<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\Unit;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class UnitController extends Controller
{
    public function create(Building $building): View
    {
        return view('units.create', compact('building'));
    }

    public function store(Request $request, Building $building): RedirectResponse
    {
        $data = $request->validate([
            'unit_reference' => [
                'required',
                'string',
                'max:50',
                Rule::unique('units', 'unit_reference')->where(fn ($q) => $q->where('building_id', $building->id)),
            ],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'active_from' => ['nullable', 'date'],
            'active_to' => ['nullable', 'date', 'after_or_equal:active_from'],
        ]);

        $data['building_id'] = $building->id;
        $data['is_active'] = $request->boolean('is_active', true);

        Unit::query()->create($data);

        return redirect()->route('buildings.show', $building)->with('success', 'Unit created.');
    }

    public function edit(Unit $unit): View
    {
        $unit->load('building');

        return view('units.edit', compact('unit'));
    }

    public function update(Request $request, Unit $unit): RedirectResponse
    {
        $data = $request->validate([
            'unit_reference' => [
                'required',
                'string',
                'max:50',
                Rule::unique('units', 'unit_reference')
                    ->where(fn ($q) => $q->where('building_id', $unit->building_id))
                    ->ignore($unit->id),
            ],
            'description' => ['nullable', 'string', 'max:255'],
            'is_active' => ['sometimes', 'boolean'],
            'active_from' => ['nullable', 'date'],
            'active_to' => ['nullable', 'date', 'after_or_equal:active_from'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $unit->update($data);

        return redirect()->route('buildings.show', $unit->building_id)->with('success', 'Unit updated.');
    }

    public function destroy(Unit $unit): RedirectResponse
    {
        $buildingId = $unit->building_id;
        $unit->delete();

        return redirect()->route('buildings.show', $buildingId)->with('success', 'Unit deleted.');
    }
}
