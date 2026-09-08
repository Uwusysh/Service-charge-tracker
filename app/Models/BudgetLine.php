<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'budget_id',
    'schedule_id',
    'cost_heading_id',
    'description',
    'previous_budget',
    'previous_actual',
    'current_estimate',
    'is_reserve',
    'basis_explanation',
])]
class BudgetLine extends Model
{
    protected function casts(): array
    {
        return [
            'previous_budget' => 'decimal:2',
            'previous_actual' => 'decimal:2',
            'current_estimate' => 'decimal:2',
            'is_reserve' => 'boolean',
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

    public function costHeading(): BelongsTo
    {
        return $this->belongsTo(CostHeading::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }
}
