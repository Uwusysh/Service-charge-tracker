@extends('layouts.app')
@section('title', 'Budgets')
@section('content')
<x-page-header title="Budgets" lede="Draft, review and final annual service-charge budgets.">
    <x-slot:actions>
        @if(auth()->user()->isAdministrator() || auth()->user()->isBlockManager())
            <a href="{{ route('budgets.create') }}" class="btn btn-primary">Create budget</a>
        @endif
    </x-slot:actions>
</x-page-header>

<div class="panel">
    <div class="table-wrap">
        <table class="table setk-table table-sm">
            <thead>
                <tr>
                    <th>Title</th>
                    <th>Building</th>
                    <th>Year</th>
                    <th>Status</th>
                </tr>
            </thead>
            <tbody>
            @forelse($budgets as $budget)
                <tr>
                    <td><a class="fw-semibold" href="{{ route('budgets.show', $budget) }}">{{ $budget->title }}</a></td>
                    <td>{{ $budget->building->name }}</td>
                    <td>{{ $budget->serviceChargeYear->displayLabel() }}</td>
                    <td><x-status-badge :status="$budget->status" /></td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted py-4">No budgets yet.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
<div class="mt-3">{{ $budgets->links() }}</div>
@endsection
