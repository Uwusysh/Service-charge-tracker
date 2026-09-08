<?php

namespace App\Http\Controllers;

use App\Models\Building;
use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class BuildingController extends Controller
{
    public function index(): View
    {
        $buildings = Building::query()->with('client')->orderBy('name')->paginate(20);

        return view('buildings.index', compact('buildings'));
    }

    public function create(Request $request): View
    {
        $clients = Client::query()->where('is_active', true)->orderBy('name')->get();
        $selectedClientId = $request->integer('client_id') ?: null;

        return view('buildings.create', compact('clients', 'selectedClientId'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'name' => ['required', 'string', 'max:255'],
            'address_line1' => ['required', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'postcode' => ['required', 'string', 'max:20'],
            'reference' => ['required', 'string', 'max:50', 'unique:buildings,reference'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $building = Building::query()->create($data);

        return redirect()->route('buildings.show', $building)->with('success', 'Building created.');
    }

    public function show(Building $building): View
    {
        $building->load([
            'client',
            'units',
            'schedules.scheduleUnits.unit',
            'serviceChargeYears',
            'budgets.serviceChargeYear',
        ]);

        return view('buildings.show', compact('building'));
    }

    public function edit(Building $building): View
    {
        $clients = Client::query()->orderBy('name')->get();

        return view('buildings.edit', compact('building', 'clients'));
    }

    public function update(Request $request, Building $building): RedirectResponse
    {
        $data = $request->validate([
            'client_id' => ['required', 'exists:clients,id'],
            'name' => ['required', 'string', 'max:255'],
            'address_line1' => ['required', 'string', 'max:255'],
            'address_line2' => ['nullable', 'string', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'postcode' => ['required', 'string', 'max:20'],
            'reference' => ['required', 'string', 'max:50', Rule::unique('buildings', 'reference')->ignore($building->id)],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $building->update($data);

        return redirect()->route('buildings.show', $building)->with('success', 'Building updated.');
    }

    public function destroy(Building $building): RedirectResponse
    {
        $building->delete();

        return redirect()->route('buildings.index')->with('success', 'Building deleted.');
    }
}
