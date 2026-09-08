<?php

namespace App\Services;

use App\Models\Schedule;
use App\Models\ScheduleUnit;
use Illuminate\Support\Facades\DB;

class ApportionmentService
{
    /**
     * @return array{valid: bool, total: string, message?: string}
     */
    public function validateScheduleTotals(Schedule $schedule): array
    {
        $schedule->loadMissing('scheduleUnits');

        $total = '0.000000';
        foreach ($schedule->scheduleUnits as $scheduleUnit) {
            $total = bcadd($total, (string) $scheduleUnit->percentage, 6);
        }

        $valid = bccomp($total, '100.000000', 6) === 0;

        $result = [
            'valid' => $valid,
            'total' => $total,
        ];

        if (! $valid) {
            $result['message'] = sprintf(
                'Schedule "%s" percentages total %s%%; they must total exactly 100.000000%%.',
                $schedule->name,
                $total
            );
        }

        return $result;
    }

    public function applyEqualShares(Schedule $schedule): void
    {
        $schedule->loadMissing('scheduleUnits');

        $count = $schedule->scheduleUnits->count();
        if ($count === 0) {
            return;
        }

        $base = bcdiv('100', (string) $count, 6);
        $allocated = '0.000000';

        DB::transaction(function () use ($schedule, $count, $base, &$allocated) {
            $units = $schedule->scheduleUnits->values();

            foreach ($units as $index => $scheduleUnit) {
                if ($index === $count - 1) {
                    $percentage = bcsub('100.000000', $allocated, 6);
                } else {
                    $percentage = $base;
                    $allocated = bcadd($allocated, $percentage, 6);
                }

                $scheduleUnit->update(['percentage' => $percentage]);
            }

            $schedule->update(['allocation_method' => 'equal']);
        });
    }

    /**
     * Update manual percentages without inventing values to force 100%.
     *
     * @param  array<int, array{unit_id: int, percentage: string|float}>  $rows
     */
    public function syncManualPercentages(Schedule $schedule, array $rows): void
    {
        DB::transaction(function () use ($schedule, $rows) {
            $schedule->scheduleUnits()->delete();

            foreach ($rows as $row) {
                ScheduleUnit::query()->create([
                    'schedule_id' => $schedule->id,
                    'unit_id' => $row['unit_id'],
                    'percentage' => number_format((float) $row['percentage'], 6, '.', ''),
                ]);
            }

            $schedule->update(['allocation_method' => 'manual']);
        });
    }
}
