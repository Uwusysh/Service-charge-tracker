@extends('layouts.app')
@section('title', 'Buildings')
@section('content')
<x-page-header title="Buildings" lede="Residential blocks with flats, schedules and annual budgets.">
    <x-slot:actions>
        @if(auth()->user()->isAdministrator() || auth()->user()->isBlockManager())
            <a href="{{ route('buildings.create') }}" class="btn btn-primary">New building</a>
        @endif
    </x-slot:actions>
</x-page-header>

<div class="panel">
    <div class="table-wrap">
        <table class="table setk-table table-sm">
            <thead><tr><th>Building</th><th>Client</th><th>Reference</th><th>Status</th></tr></thead>
            <tbody>
            @forelse($buildings as $building)
                <tr>
                    <td><a class="fw-semibold" href="{{ route('buildings.show', $building) }}">{{ $building->name }}</a></td>
                    <td>{{ $building->client->name }}</td>
                    <td><code>{{ $building->reference }}</code></td>
                    <td>
                        @if($building->is_active)
                            <span class="badge-pill badge-final">Active</span>
                        @else
                            <span class="badge-pill badge-archived">Inactive</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted py-4">No buildings yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $buildings->links() }}</div>
@endsection
