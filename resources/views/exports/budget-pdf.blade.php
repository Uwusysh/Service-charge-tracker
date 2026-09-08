<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>{{ $budget->title }}</title>
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 12px; color: #222; }
        h1 { font-size: 18px; margin-bottom: 4px; }
        h2 { font-size: 14px; margin-top: 18px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #ccc; padding: 4px 6px; text-align: left; }
        th { background: #f3f3f3; }
        .muted { color: #666; }
        .footer { position: fixed; bottom: 0; left: 0; right: 0; font-size: 9px; color: #555; border-top: 1px solid #ccc; padding-top: 6px; }
    </style>
</head>
<body>
    <h1>{{ $budget->title }}</h1>
    <p class="muted">{{ $budget->building->name }} · {{ $budget->building->client->name }} · {{ $budget->serviceChargeYear->displayLabel() }}</p>
    <p>Status: {{ $budget->status }} · Total: {{ \App\Support\Money::formatGbp($total) }}</p>
    <p>Authorised: {{ $budget->authorised_approver }} ({{ $budget->authorised_capacity }}) {{ optional($budget->authorised_at)->format('d/m/Y') }}</p>

    <h2>Budget lines</h2>
    <table>
        <thead>
            <tr><th>Schedule</th><th>Heading</th><th>Estimate</th><th>Reserve</th></tr>
        </thead>
        <tbody>
        @foreach($budget->lines as $line)
            <tr>
                <td>{{ $line->schedule->name }}</td>
                <td>{{ $line->costHeading->code }} — {{ $line->costHeading->name }}</td>
                <td>{{ \App\Support\Money::formatGbp($line->current_estimate) }}</td>
                <td>{{ $line->is_reserve ? 'Y' : '' }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>

    @if($budget->unitTotals->isNotEmpty())
    <h2>Unit contributions</h2>
    <table>
        <thead><tr><th>Schedule</th><th>Unit</th><th>%</th><th>Contribution</th></tr></thead>
        <tbody>
        @foreach($budget->unitTotals as $row)
            <tr>
                <td>{{ $row->schedule->name }}</td>
                <td>{{ $row->unit->unit_reference }}</td>
                <td>{{ $row->percentage }}</td>
                <td>{{ \App\Support\Money::formatGbp($row->contribution) }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    @endif

    <div class="footer">
        This document is a management information extract for service charge budgeting and monitoring. It does not constitute a statutory service charge certificate, audited account, or formal demand under the lease. Figures use pound sterling rounded to the nearest penny with balancing-unit remainder adjustments.
    </div>
</body>
</html>
