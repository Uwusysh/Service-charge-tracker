<?php

namespace App\Http\Controllers;

use App\Exports\BudgetWorkbookExport;
use App\Models\Budget;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Http\Response;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ExportController extends Controller
{
    public function excel(Budget $budget): BinaryFileResponse
    {
        $filename = sprintf('budget-%s-%s.xlsx', $budget->id, now()->format('Ymd'));

        return Excel::download(new BudgetWorkbookExport($budget), $filename);
    }

    public function pdf(Budget $budget): Response
    {
        $budget->load([
            'building.client',
            'serviceChargeYear',
            'lines.schedule',
            'lines.costHeading',
            'unitTotals.unit',
            'unitTotals.schedule',
            'preparer',
            'finaliser',
        ]);

        $pdf = Pdf::loadView('exports.budget-pdf', [
            'budget' => $budget,
            'total' => app(\App\Services\BudgetCalculationService::class)->buildingBudgetTotal($budget),
        ])->setPaper('a4', 'portrait');

        $filename = sprintf('budget-%s.pdf', $budget->id);

        return $pdf->download($filename);
    }
}
