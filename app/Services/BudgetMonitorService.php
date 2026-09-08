<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\BudgetLine;
use App\Models\CostHeading;
use App\Models\Invoice;
use App\Models\Schedule;
use App\Support\Money;

class BudgetMonitorService
{
    /**
     * @return array{
     *     budget: string,
     *     approved_spent: string,
     *     pending_amount: string,
     *     remaining: string,
     *     forecast_remaining: string,
     *     pct_used: string,
     *     overspend: bool
     * }
     */
    public function forBuilding(Budget $budget): array
    {
        $budgetAmount = app(BudgetCalculationService::class)->buildingBudgetTotal($budget);

        return $this->metrics(
            $budgetAmount,
            $this->sumInvoices($budget, Invoice::STATUS_APPROVED),
            $this->sumInvoices($budget, Invoice::STATUS_SUBMITTED)
        );
    }

    /**
     * @return array{
     *     budget: string,
     *     approved_spent: string,
     *     pending_amount: string,
     *     remaining: string,
     *     forecast_remaining: string,
     *     pct_used: string,
     *     overspend: bool
     * }
     */
    public function forSchedule(Budget $budget, Schedule|int $schedule): array
    {
        $scheduleId = $schedule instanceof Schedule ? $schedule->id : $schedule;
        $budgetAmount = app(BudgetCalculationService::class)->scheduleTotal($budget, $scheduleId);

        return $this->metrics(
            $budgetAmount,
            $this->sumInvoices($budget, Invoice::STATUS_APPROVED, scheduleId: $scheduleId),
            $this->sumInvoices($budget, Invoice::STATUS_SUBMITTED, scheduleId: $scheduleId)
        );
    }

    /**
     * @return array{
     *     budget: string,
     *     approved_spent: string,
     *     pending_amount: string,
     *     remaining: string,
     *     forecast_remaining: string,
     *     pct_used: string,
     *     overspend: bool
     * }
     */
    public function forHeading(Budget $budget, CostHeading|int $heading): array
    {
        $headingId = $heading instanceof CostHeading ? $heading->id : $heading;

        $budgetAmount = Money::normalize((string) $budget->lines()
            ->where('cost_heading_id', $headingId)
            ->sum('current_estimate'));

        return $this->metrics(
            $budgetAmount,
            $this->sumInvoices($budget, Invoice::STATUS_APPROVED, headingId: $headingId),
            $this->sumInvoices($budget, Invoice::STATUS_SUBMITTED, headingId: $headingId)
        );
    }

    /**
     * @return array{
     *     budget: string,
     *     approved_spent: string,
     *     pending_amount: string,
     *     remaining: string,
     *     forecast_remaining: string,
     *     pct_used: string,
     *     overspend: bool
     * }
     */
    public function forLine(BudgetLine $line): array
    {
        $budgetAmount = Money::normalize((string) $line->current_estimate);

        $approved = Money::normalize((string) Invoice::query()
            ->where('budget_line_id', $line->id)
            ->where('status', Invoice::STATUS_APPROVED)
            ->sum('gross_amount'));

        $pending = Money::normalize((string) Invoice::query()
            ->where('budget_line_id', $line->id)
            ->where('status', Invoice::STATUS_SUBMITTED)
            ->sum('gross_amount'));

        return $this->metrics($budgetAmount, $approved, $pending);
    }

    /**
     * @return list<array{level:string, key:string, label:string}&array>
     */
    public function buildingBreakdown(Budget $budget): array
    {
        $budget->loadMissing(['lines.schedule', 'lines.costHeading']);

        $rows = [
            array_merge(['level' => 'building', 'key' => 'building', 'label' => 'Building total'], $this->forBuilding($budget)),
        ];

        $scheduleIds = $budget->lines->pluck('schedule_id')->unique();
        foreach ($scheduleIds as $scheduleId) {
            $schedule = $budget->lines->firstWhere('schedule_id', $scheduleId)?->schedule;
            if (! $schedule) {
                continue;
            }
            $rows[] = array_merge(
                ['level' => 'schedule', 'key' => 'schedule-'.$schedule->id, 'label' => $schedule->name],
                $this->forSchedule($budget, $schedule)
            );
        }

        $headingIds = $budget->lines->pluck('cost_heading_id')->unique();
        foreach ($headingIds as $headingId) {
            $heading = $budget->lines->firstWhere('cost_heading_id', $headingId)?->costHeading;
            if (! $heading) {
                continue;
            }
            $rows[] = array_merge(
                ['level' => 'heading', 'key' => 'heading-'.$heading->id, 'label' => $heading->code.' – '.$heading->name],
                $this->forHeading($budget, $heading)
            );
        }

        return $rows;
    }

    /**
     * @return array{
     *     budget: string,
     *     approved_spent: string,
     *     pending_amount: string,
     *     remaining: string,
     *     forecast_remaining: string,
     *     pct_used: string,
     *     overspend: bool
     * }
     */
    private function metrics(string $budget, string $approved, string $pending): array
    {
        $remaining = Money::subtract($budget, $approved);
        $forecastRemaining = Money::subtract($remaining, $pending);
        $pctUsed = '0.00';

        if (Money::compare($budget, '0.00') > 0) {
            $pctUsed = bcmul(bcdiv($approved, $budget, 6), '100', 2);
        }

        return [
            'budget' => $budget,
            'approved_spent' => $approved,
            'pending_amount' => $pending,
            'remaining' => $remaining,
            'forecast_remaining' => $forecastRemaining,
            'pct_used' => $pctUsed,
            'overspend' => Money::compare($remaining, '0.00') < 0,
        ];
    }

    private function sumInvoices(
        Budget $budget,
        string $status,
        ?int $scheduleId = null,
        ?int $headingId = null
    ): string {
        $query = Invoice::query()
            ->where('budget_id', $budget->id)
            ->where('status', $status);

        if ($scheduleId !== null || $headingId !== null) {
            $query->whereHas('budgetLine', function ($q) use ($scheduleId, $headingId) {
                if ($scheduleId !== null) {
                    $q->where('schedule_id', $scheduleId);
                }
                if ($headingId !== null) {
                    $q->where('cost_heading_id', $headingId);
                }
            });
        }

        return Money::normalize((string) $query->sum('gross_amount'));
    }
}
