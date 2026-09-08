<?php

namespace App\Http\Controllers;

use App\Models\Client;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ClientController extends Controller
{
    public function index(): View
    {
        $clients = Client::query()->orderBy('name')->paginate(20);

        return view('clients.index', compact('clients'));
    }

    public function create(): View
    {
        return view('clients.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:freeholder,landlord,rmc,rtm,other'],
            'company_details' => ['nullable', 'string'],
            'accountant_details' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);

        $client = Client::query()->create($data);

        return redirect()->route('clients.show', $client)->with('success', 'Client created.');
    }

    public function show(Client $client): View
    {
        $client->load(['buildings.units', 'buildings.serviceChargeYears']);

        return view('clients.show', compact('client'));
    }

    public function edit(Client $client): View
    {
        return view('clients.edit', compact('client'));
    }

    public function update(Request $request, Client $client): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:freeholder,landlord,rmc,rtm,other'],
            'company_details' => ['nullable', 'string'],
            'accountant_details' => ['nullable', 'string'],
            'is_active' => ['sometimes', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $client->update($data);

        return redirect()->route('clients.show', $client)->with('success', 'Client updated.');
    }

    public function destroy(Client $client): RedirectResponse
    {
        $client->delete();

        return redirect()->route('clients.index')->with('success', 'Client deleted.');
    }
}
