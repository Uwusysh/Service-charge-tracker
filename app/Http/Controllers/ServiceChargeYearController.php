<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\ServiceChargeYear;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceChargeYearController extends Controller
{
    public function create(Building $building): View
    {
        return view('service_charge_years.create', compact('building'));
    }

    public function store(Request $request, Building $building): RedirectResponse
    {
        $data = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'status' => ['required', 'in:draft,active,closed'],
            'label' => ['nullable', 'string', 'max:255'],
        ]);

        $data['building_id'] = $building->id;
        ServiceChargeYear::query()->create($data);

        return redirect()->route('buildings.show', $building)->with('success', 'Service charge year created.');
    }

    public function edit(ServiceChargeYear $serviceChargeYear): View
    {
        $serviceChargeYear->load('building');

        return view('service_charge_years.edit', compact('serviceChargeYear'));
    }

    public function update(Request $request, ServiceChargeYear $serviceChargeYear): RedirectResponse
    {
        $data = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after:start_date'],
            'status' => ['required', 'in:draft,active,closed'],
            'label' => ['nullable', 'string', 'max:255'],
        ]);

        $serviceChargeYear->update($data);

        return redirect()
            ->route('buildings.show', $serviceChargeYear->building_id)
            ->with('success', 'Service charge year updated.');
    }
}
