<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'budget_id',
    'schedule_id',
    'unit_id',
    'percentage',
    'contribution',
    'is_balancing_adjustment',
])]
class BudgetUnitTotal extends Model
{
    protected function casts(): array
    {
        return [
            'percentage' => 'decimal:6',
            'contribution' => 'decimal:2',
            'is_balancing_adjustment' => 'boolean',
        ];
    }

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function schedule(): BelongsTo
    {
        return $this->belongsTo(Schedule::class);
    }

    public function unit(): BelongsTo
    {
        return $this->belongsTo(Unit::class);
    }
}
