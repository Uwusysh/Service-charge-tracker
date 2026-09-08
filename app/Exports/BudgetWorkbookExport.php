<?php

namespace App\Exports;

use App\Models\Budget;
use App\Models\Invoice;
use App\Services\BudgetCalculationService;
use App\Services\BudgetMonitorService;
use Maatwebsite\Excel\Concerns\Export;
use Maatwebsite\Excel\Concerns\WithMultipleSheets;

class BudgetWorkbookExport implements Export, WithMultipleSheets
{
    public function __construct(private readonly Budget $budget) {}

    public function sheets(): array
    {
        $budget = $this->budget->loadMissing([
            'building.client',
            'serviceChargeYear',
            'lines.schedule',
            'lines.costHeading',
            'unitTotals.unit',
            'unitTotals.schedule',
            'building.schedules.scheduleUnits.unit',
            'preparer',
            'finaliser',
        ]);

        $calc = app(BudgetCalculationService::class);
        $monitor = app(BudgetMonitorService::class);

        return [
            new Sheets\ArraySheet('Budget Summary', $this->summaryRows($budget, $calc)),
            new Sheets\ArraySheet('Budget Lines', $this->lineRows($budget)),
            new Sheets\ArraySheet('Unit Contributions', $this->unitRows($budget)),
            new Sheets\ArraySheet('Apportionments', $this->apportionmentRows($budget)),
            new Sheets\ArraySheet('Budget vs Actual', $this->vsActualRows($budget, $monitor)),
            new Sheets\ArraySheet('Invoice Register', $this->invoiceRows($budget)),
            new Sheets\ArraySheet('Assumptions', $this->assumptionRows($budget)),
        ];
    }

    private function summaryRows(Budget $budget, BudgetCalculationService $calc): array
    {
        return [
            ['Field', 'Value'],
            ['Building', $budget->building->name],
            ['Client', $budget->building->client->name],
            ['Reference', $budget->building->reference],
            ['Service charge year', $budget->serviceChargeYear->displayLabel()],
            ['Budget title', $budget->title],
            ['Status', $budget->status],
            ['Version', $budget->version],
            ['Prepared by', $budget->preparer?->name],
            ['Authorised approver', $budget->authorised_approver],
            ['Authorised capacity', $budget->authorised_capacity],
            ['Authorised at', optional($budget->authorised_at)?->format('Y-m-d')],
            ['Finalised by', $budget->finaliser?->name],
            ['Finalised at', optional($budget->finalised_at)?->format('Y-m-d H:i')],
            ['Total budget', $calc->buildingBudgetTotal($budget)],
            ['Overall notes', $budget->overall_notes],
        ];
    }

    private function lineRows(Budget $budget): array
    {
        $rows = [[
            'Schedule',
            'Cost heading',
            'Description',
            'Previous budget',
            'Previous actual',
            'Current estimate',
            'Reserve?',
            'Basis explanation',
        ]];

        foreach ($budget->lines as $line) {
            $rows[] = [
                $line->schedule?->name,
                $line->costHeading?->code.' – '.$line->costHeading?->name,
                $line->description,
                $line->previous_budget,
                $line->previous_actual,
                $line->current_estimate,
                $line->is_reserve ? 'Yes' : 'No',
                $line->basis_explanation,
            ];
        }

        return $rows;
    }

    private function unitRows(Budget $budget): array
    {
        $rows = [['Schedule', 'Unit', 'Percentage', 'Contribution', 'Balancing adjustment?']];

        foreach ($budget->unitTotals as $total) {
            $rows[] = [
                $total->schedule?->name,
                $total->unit?->unit_reference,
                $total->percentage,
                $total->contribution,
                $total->is_balancing_adjustment ? 'Yes' : 'No',
            ];
        }

        return $rows;
    }

    private function apportionmentRows(Budget $budget): array
    {
        $rows = [['Schedule', 'Method', 'Unit', 'Percentage', 'Balancing unit']];

        foreach ($budget->building->schedules as $schedule) {
            foreach ($schedule->scheduleUnits as $su) {
                $rows[] = [
                    $schedule->name,
                    $schedule->allocation_method,
                    $su->unit?->unit_reference,
                    $su->percentage,
                    $schedule->balancing_unit_id === $su->unit_id ? 'Yes' : '',
                ];
            }
        }

        return $rows;
    }

    private function vsActualRows(Budget $budget, BudgetMonitorService $monitor): array
    {
        $rows = [[
            'Level',
            'Label',
            'Budget',
            'Approved spent',
            'Pending',
            'Remaining',
            'Forecast remaining',
            '% used',
            'Overspend?',
        ]];

        if ($budget->isFinal() || $budget->status === Budget::STATUS_ARCHIVED) {
            foreach ($monitor->buildingBreakdown($budget) as $row) {
                $rows[] = [
                    $row['level'],
                    $row['label'],
                    $row['budget'],
                    $row['approved_spent'],
                    $row['pending_amount'],
                    $row['remaining'],
                    $row['forecast_remaining'],
                    $row['pct_used'],
                    $row['overspend'] ? 'Yes' : 'No',
                ];
            }
        }

        return $rows;
    }

    private function invoiceRows(Budget $budget): array
    {
        $rows = [[
            'Reference',
            'Supplier',
            'Date',
            'Heading',
            'Net',
            'VAT',
            'Gross',
            'Status',
        ]];

        $invoices = Invoice::query()
            ->with(['supplier', 'budgetLine.costHeading'])
            ->where('budget_id', $budget->id)
            ->orderBy('invoice_date')
            ->get();

        foreach ($invoices as $invoice) {
            $rows[] = [
                $invoice->invoice_reference,
                $invoice->supplier?->name,
                optional($invoice->invoice_date)?->format('Y-m-d'),
                $invoice->budgetLine?->costHeading?->code,
                $invoice->net_amount,
                $invoice->vat_amount,
                $invoice->gross_amount,
                $invoice->status,
            ];
        }

        return $rows;
    }

    private function assumptionRows(Budget $budget): array
    {
        return [
            ['Assumption', 'Detail'],
            ['Currency', 'GBP'],
            ['Rounding', 'Contributions rounded to nearest penny; remainder posted to balancing unit'],
            ['Percentages', 'Lease/schedule percentages are not invented to force 100%'],
            ['Invoices', 'Only Final budgets receive invoices; overspend is allowed and highlighted'],
            ['Notes', $budget->overall_notes ?: 'None'],
            ['Disclaimer', 'This workbook is a management information extract and does not constitute a statutory service charge certificate.'],
        ];
    }
}
