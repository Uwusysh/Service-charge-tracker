@extends('layouts.app')
@section('title', $schedule->name)
@section('content')
<x-page-header :title="$schedule->name" :lede="$schedule->building->name.' · '.ucfirst($schedule->allocation_method).' allocation'">
    <p class="lede mb-0 mt-1">Participating total: <strong class="money">{{ $validation['total'] }}%</strong></p>
    <x-slot:actions>
        @if(auth()->user()->isAdministrator() || auth()->user()->isBlockManager())
            <a href="{{ route('schedules.edit', $schedule) }}" class="btn btn-outline-secondary">Edit schedule</a>
            <form method="POST" action="{{ route('apportionment.equal', $schedule) }}">@csrf<button class="btn btn-outline-primary">Apply equal shares</button></form>
        @endif
        <a href="{{ route('buildings.show', $schedule->building) }}" class="btn btn-outline-secondary">Building</a>
    </x-slot:actions>
</x-page-header>

@if(!$validation['valid'])
    <div class="alert alert-warning">{{ $validation['message'] }}</div>
@else
    <div class="alert alert-success">Percentages total 100.000000% — ready for Final.</div>
@endif

<div class="panel">
    <h2 class="panel-title">Flat percentages</h2>
    @if(auth()->user()->isAdministrator() || auth()->user()->isBlockManager())
    <form method="POST" action="{{ route('apportionment.manual', $schedule) }}">
        @csrf
        <div class="table-wrap">
            <table class="table setk-table table-sm">
                <thead><tr><th>Unit</th><th style="width:180px">Percentage</th></tr></thead>
                <tbody>
                @foreach($schedule->scheduleUnits as $i => $su)
                    <tr>
                        <td>
                            {{ $su->unit->unit_reference }}
                            @if($schedule->balancing_unit_id === $su->unit_id)
                                <span class="badge-pill badge-neutral ms-1">balancing</span>
                            @endif
                        </td>
                        <td>
                            <input type="hidden" name="percentages[{{ $i }}][unit_id]" value="{{ $su->unit_id }}">
                            <input class="form-control form-control-sm" name="percentages[{{ $i }}][percentage]" value="{{ $su->percentage }}">
                        </td>
                    </tr>
                @endforeach
                </tbody>
            </table>
        </div>
        <button class="btn btn-primary mt-3">Save manual percentages</button>
    </form>
    @else
    <div class="table-wrap">
        <table class="table setk-table table-sm">
            <thead><tr><th>Unit</th><th class="text-end">Percentage</th></tr></thead>
            <tbody>
            @foreach($schedule->scheduleUnits as $su)
                <tr>
                    <td>{{ $su->unit->unit_reference }}</td>
                    <td class="text-end money">{{ number_format((float) $su->percentage, 6) }}%</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
    @endif
</div>
@endsection
