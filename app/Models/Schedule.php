<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['building_id', 'name', 'allocation_method', 'balancing_unit_id'])]
class Schedule extends Model
{
    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function balancingUnit(): BelongsTo
    {
        return $this->belongsTo(Unit::class, 'balancing_unit_id');
    }

    public function scheduleUnits(): HasMany
    {
        return $this->hasMany(ScheduleUnit::class);
    }

    public function units(): BelongsToMany
    {
        return $this->belongsToMany(Unit::class, 'schedule_units')
            ->withPivot('percentage')
            ->withTimestamps();
    }

    public function budgetLines(): HasMany
    {
        return $this->hasMany(BudgetLine::class);
    }

    public function budgetUnitTotals(): HasMany
    {
        return $this->hasMany(BudgetUnitTotal::class);
    }
}
