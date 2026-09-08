<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'building_id',
    'unit_reference',
    'description',
    'is_active',
    'active_from',
    'active_to',
])]
class Unit extends Model
{
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'active_from' => 'date',
            'active_to' => 'date',
        ];
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function schedules(): BelongsToMany
    {
        return $this->belongsToMany(Schedule::class, 'schedule_units')
            ->withPivot('percentage')
            ->withTimestamps();
    }

    public function scheduleUnits(): HasMany
    {
        return $this->hasMany(ScheduleUnit::class);
    }

    public function budgetUnitTotals(): HasMany
    {
        return $this->hasMany(BudgetUnitTotal::class);
    }
}
