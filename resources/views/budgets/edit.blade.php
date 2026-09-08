@extends('layouts.app')
@section('title', 'Edit budget')
@section('content')
@php($canEditLines = (auth()->user()->isAdministrator() || auth()->user()->isBlockManager()) && $budget->isEditable())

<x-page-header :title="'Edit — '.$budget->title" lede="Update header details and estimated cost lines while the budget is editable.">
    <x-slot:actions>
        <a href="{{ route('budgets.show', $budget) }}" class="btn btn-outline-secondary">View budget</a>
    </x-slot:actions>
</x-page-header>

<div class="panel mb-3">
    <h2 class="panel-title">Budget header</h2>
    <form method="POST" action="{{ route('budgets.update', $budget) }}" class="row g-3">
        @csrf @method('PUT')
        <div class="col-md-6"><label class="form-label">Title</label><input name="title" class="form-control" value="{{ old('title', $budget->title) }}" required></div>
        <div class="col-md-6"><label class="form-label">Authorised approver</label><input name="authorised_approver" class="form-control" value="{{ old('authorised_approver', $budget->authorised_approver) }}"></div>
        <div class="col-md-4"><label class="form-label">Authorised at</label><input type="date" name="authorised_at" class="form-control" value="{{ old('authorised_at', optional($budget->authorised_at)->format('Y-m-d')) }}"></div>
        <div class="col-md-4"><label class="form-label">Authorised capacity</label><input name="authorised_capacity" class="form-control" value="{{ old('authorised_capacity', $budget->authorised_capacity) }}"></div>
        <div class="col-md-12"><label class="form-label">Overall notes</label><textarea name="overall_notes" class="form-control" rows="2">{{ old('overall_notes', $budget->overall_notes) }}</textarea></div>
        <div class="col-12"><button class="btn btn-primary">Save header</button></div>
    </form>
</div>

@if($canEditLines)
<div class="panel mb-3">
    <h2 class="panel-title">Add cost line</h2>
    <form method="POST" action="{{ route('budget-lines.store', $budget) }}" class="row g-3 align-items-end">
        @csrf
        <div class="col-md-3">
            <label class="form-label">Schedule</label>
            <select name="schedule_id" class="form-select" required>
                @foreach($budget->building->schedules as $schedule)
                    <option value="{{ $schedule->id }}">{{ $schedule->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label">Cost heading</label>
            <select name="cost_heading_id" class="form-select" required>
                @foreach($costHeadings as $heading)
                    <option value="{{ $heading->id }}">{{ $heading->code }} — {{ $heading->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="col-md-2"><label class="form-label">Estimate (£)</label><input name="current_estimate" class="form-control" required></div>
        <div class="col-md-2"><label class="form-label">Prev budget</label><input name="previous_budget" class="form-control"></div>
        <div class="col-md-2">
            <div class="form-check mt-4">
                <input class="form-check-input" type="checkbox" name="is_reserve" value="1" id="res">
                <label for="res" class="form-check-label">Reserve fund</label>
            </div>
        </div>
        <div class="col-md-8"><label class="form-label">Basis / explanation</label><input name="basis_explanation" class="form-control" placeholder="Contract, quotation, inflation…"></div>
        <div class="col-md-4"><button class="btn btn-success w-100">Add line</button></div>
    </form>
</div>
@endif

<div class="panel">
    <h2 class="panel-title">Lines</h2>
    <div class="table-wrap">
        <table class="table setk-table table-sm">
            <thead><tr><th>Schedule</th><th>Heading</th><th class="text-end">Estimate</th><th></th></tr></thead>
            <tbody>
            @forelse($budget->lines as $line)
                <tr>
                    <td>{{ $line->schedule->name }}</td>
                    <td><strong>{{ $line->costHeading->code }}</strong> <span class="text-muted">{{ $line->costHeading->name }}</span></td>
                    <td class="text-end money">{{ \App\Support\Money::formatGbp($line->current_estimate) }}</td>
                    <td class="text-end">
                        @if($canEditLines)
                        <form method="POST" action="{{ route('budget-lines.destroy', $line) }}" class="d-inline">@csrf @method('DELETE')<button class="btn btn-link btn-sm text-danger">Remove</button></form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="4" class="text-center text-muted py-4">No lines yet — add estimates above.</td></tr>
            @endforelse
            </tbody>
        </table>
    </div>
</div>
@endsection
