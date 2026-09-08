<?php

namespace App\Services;

use App\Models\Budget;
use App\Models\BudgetUnitTotal;
use App\Models\Schedule;
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BudgetCalculationService
{
    public function __construct(
        private readonly ApportionmentService $apportionmentService
    ) {}

    public function scheduleTotal(Budget $budget, Schedule|int $schedule): string
    {
        $scheduleId = $schedule instanceof Schedule ? $schedule->id : $schedule;

        $sum = $budget->lines()
            ->where('schedule_id', $scheduleId)
            ->sum('current_estimate');

        return Money::normalize((string) $sum);
    }

    public function flatScheduleShare(Budget $budget, Schedule|int $schedule, int $unitId): string
    {
        $scheduleModel = $schedule instanceof Schedule
            ? $schedule
            : Schedule::query()->with('scheduleUnits')->findOrFail($schedule);

        $scheduleModel->loadMissing('scheduleUnits');

        $scheduleUnit = $scheduleModel->scheduleUnits->firstWhere('unit_id', $unitId);
        if (! $scheduleUnit) {
            return '0.00';
        }

        $total = $this->scheduleTotal($budget, $scheduleModel);

        return Money::percentOf($total, (string) $scheduleUnit->percentage);
    }

    public function flatAnnualContribution(Budget $budget, int $unitId): string
    {
        $budget->loadMissing('lines.schedule.scheduleUnits');

        $total = '0.00';
        $scheduleIds = $budget->lines->pluck('schedule_id')->unique();

        foreach ($scheduleIds as $scheduleId) {
            $schedule = $budget->lines->firstWhere('schedule_id', $scheduleId)?->schedule;
            if (! $schedule) {
                continue;
            }
            $total = Money::add($total, $this->flatScheduleShare($budget, $schedule, $unitId));
        }

        return $total;
    }

    public function buildingBudgetTotal(Budget $budget): string
    {
        return Money::normalize((string) $budget->lines()->sum('current_estimate'));
    }

    /**
     * Recalculate draft contribution preview without persisting frozen totals.
     *
     * @return array<int, array{schedule_id:int, unit_id:int, percentage:string, contribution:string, is_balancing_adjustment:bool}>
     */
    public function recalculateDraftTotals(Budget $budget): array
    {
        $budget->loadMissing(['lines']);

        $rows = [];
        $scheduleIds = $budget->lines->pluck('schedule_id')->unique()->filter();

        foreach ($scheduleIds as $scheduleId) {
            $schedule = Schedule::query()->with('scheduleUnits')->find($scheduleId);
            if (! $schedule || $schedule->scheduleUnits->isEmpty()) {
                continue;
            }

            $scheduleTotal = $this->scheduleTotal($budget, $schedule);
            $units = $schedule->scheduleUnits->values();
            $balancingUnitId = $schedule->balancing_unit_id ?? $units->last()->unit_id;
            $allocated = '0.00';

            foreach ($units as $scheduleUnit) {
                if ((int) $scheduleUnit->unit_id === (int) $balancingUnitId) {
                    continue;
                }

                $contribution = Money::percentOf($scheduleTotal, (string) $scheduleUnit->percentage);
                $allocated = Money::add($allocated, $contribution);

                $rows[] = [
                    'schedule_id' => $schedule->id,
                    'unit_id' => (int) $scheduleUnit->unit_id,
                    'percentage' => (string) $scheduleUnit->percentage,
                    'contribution' => $contribution,
                    'is_balancing_adjustment' => false,
                ];
            }

            $balancingScheduleUnit = $units->firstWhere('unit_id', $balancingUnitId) ?? $units->last();
            $remainder = Money::subtract($scheduleTotal, $allocated);
            $rawShare = Money::percentOf($scheduleTotal, (string) $balancingScheduleUnit->percentage);

            $rows[] = [
                'schedule_id' => $schedule->id,
                'unit_id' => (int) $balancingScheduleUnit->unit_id,
                'percentage' => (string) $balancingScheduleUnit->percentage,
                'contribution' => $remainder,
                'is_balancing_adjustment' => Money::compare($remainder, $rawShare) !== 0,
            ];
        }

        return $rows;
    }

    public function freezeUnitTotals(Budget $budget): void
    {
        $budget->loadMissing(['lines.schedule.scheduleUnits']);

        DB::transaction(function () use ($budget) {
            $budget->unitTotals()->delete();

            $scheduleIds = $budget->lines->pluck('schedule_id')->unique()->filter();

            foreach ($scheduleIds as $scheduleId) {
                $schedule = Schedule::query()->with('scheduleUnits')->find($scheduleId);
                if (! $schedule || $schedule->scheduleUnits->isEmpty()) {
                    continue;
                }

                $scheduleTotal = $this->scheduleTotal($budget, $schedule);
                $units = $schedule->scheduleUnits->values();
                $balancingUnitId = $schedule->balancing_unit_id ?? $units->last()->unit_id;

                $allocated = '0.00';
                $rows = [];

                foreach ($units as $scheduleUnit) {
                    if ((int) $scheduleUnit->unit_id === (int) $balancingUnitId) {
                        continue;
                    }

                    $contribution = Money::percentOf($scheduleTotal, (string) $scheduleUnit->percentage);
                    $allocated = Money::add($allocated, $contribution);

                    $rows[] = [
                        'budget_id' => $budget->id,
                        'schedule_id' => $schedule->id,
                        'unit_id' => $scheduleUnit->unit_id,
                        'percentage' => $scheduleUnit->percentage,
                        'contribution' => $contribution,
                        'is_balancing_adjustment' => false,
                    ];
                }

                $balancingScheduleUnit = $units->firstWhere('unit_id', $balancingUnitId) ?? $units->last();
                $remainder = Money::subtract($scheduleTotal, $allocated);
                $rawShare = Money::percentOf($scheduleTotal, (string) $balancingScheduleUnit->percentage);
                $isAdjustment = Money::compare($remainder, $rawShare) !== 0;

                if ($isAdjustment) {
                    Log::info('Budget balancing adjustment applied', [
                        'budget_id' => $budget->id,
                        'schedule_id' => $schedule->id,
                        'unit_id' => $balancingScheduleUnit->unit_id,
                        'raw_share' => $rawShare,
                        'adjusted_contribution' => $remainder,
                        'schedule_total' => $scheduleTotal,
                    ]);
                }

                $rows[] = [
                    'budget_id' => $budget->id,
                    'schedule_id' => $schedule->id,
                    'unit_id' => $balancingScheduleUnit->unit_id,
                    'percentage' => $balancingScheduleUnit->percentage,
                    'contribution' => $remainder,
                    'is_balancing_adjustment' => $isAdjustment,
                ];

                foreach ($rows as $row) {
                    BudgetUnitTotal::query()->create($row);
                }
            }
        });
    }

    /**
     * @return array{errors: list<string>, warnings: list<string>}
     */
    public function validateForFinal(Budget $budget): array
    {
        $budget->loadMissing(['lines.schedule.scheduleUnits', 'lines.costHeading']);

        $errors = [];
        $warnings = [];

        if (blank($budget->authorised_approver) || blank($budget->authorised_at) || blank($budget->authorised_capacity)) {
            $errors[] = 'Header approval information is incomplete (authorised approver, date and capacity are required).';
        }

        if ($budget->lines->isEmpty()) {
            $errors[] = 'Budget must contain at least one budget line.';
        }

        foreach ($budget->lines as $line) {
            if (Money::compare((string) $line->current_estimate, '0.00') < 0) {
                $errors[] = sprintf(
                    'Line %s has a negative current estimate.',
                    $line->costHeading?->code ?? $line->id
                );
            }

            if ($line->previous_budget !== null && Money::compare((string) $line->previous_budget, '0.00') > 0) {
                $previous = (string) $line->previous_budget;
                $current = (string) $line->current_estimate;
                $delta = Money::subtract($current, $previous);
                $absDelta = Money::compare($delta, '0.00') < 0 ? Money::subtract('0.00', $delta) : $delta;
                $threshold = Money::multiply($previous, '0.10', 2);

                if (Money::compare($absDelta, $threshold) > 0 && blank($line->basis_explanation)) {
                    $warnings[] = sprintf(
                        'Line %s changed by more than 10%% from previous budget and needs a basis explanation.',
                        $line->costHeading?->code ?? $line->id
                    );
                }
            }
        }

        $scheduleIds = $budget->lines->pluck('schedule_id')->unique()->filter();
        foreach ($scheduleIds as $scheduleId) {
            $schedule = Schedule::query()->with('scheduleUnits')->find($scheduleId);
            if (! $schedule) {
                $errors[] = "Schedule #{$scheduleId} is missing.";
                continue;
            }

            $check = $this->apportionmentService->validateScheduleTotals($schedule);
            if (! $check['valid']) {
                $errors[] = $check['message'] ?? "Schedule {$schedule->name} does not total 100%.";
            }

            if (! $schedule->balancing_unit_id) {
                $warnings[] = "Schedule {$schedule->name} has no balancing unit set; the last participating unit will be used.";
            }
        }

        $existingFinal = Budget::query()
            ->where('building_id', $budget->building_id)
            ->where('service_charge_year_id', $budget->service_charge_year_id)
            ->where('status', Budget::STATUS_FINAL)
            ->where('id', '!=', $budget->id)
            ->exists();

        if ($existingFinal) {
            $errors[] = 'A Final budget already exists for this building and service charge year.';
        }

        return compact('errors', 'warnings');
    }
}
