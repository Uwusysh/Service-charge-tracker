<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'building_id',
    'service_charge_year_id',
    'title',
    'status',
    'prepared_by',
    'prepared_at',
    'authorised_approver',
    'authorised_at',
    'authorised_capacity',
    'finalised_by',
    'finalised_at',
    'overall_notes',
    'copied_from_budget_id',
    'version',
])]
class Budget extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_READY = 'ready_for_review';

    public const STATUS_FINAL = 'final';

    public const STATUS_ARCHIVED = 'archived';

    protected function casts(): array
    {
        return [
            'prepared_at' => 'datetime',
            'authorised_at' => 'datetime',
            'finalised_at' => 'datetime',
            'version' => 'integer',
        ];
    }

    public function building(): BelongsTo
    {
        return $this->belongsTo(Building::class);
    }

    public function serviceChargeYear(): BelongsTo
    {
        return $this->belongsTo(ServiceChargeYear::class);
    }

    public function preparer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'prepared_by');
    }

    public function finaliser(): BelongsTo
    {
        return $this->belongsTo(User::class, 'finalised_by');
    }

    public function copiedFrom(): BelongsTo
    {
        return $this->belongsTo(Budget::class, 'copied_from_budget_id');
    }

    public function lines(): HasMany
    {
        return $this->hasMany(BudgetLine::class);
    }

    public function unitTotals(): HasMany
    {
        return $this->hasMany(BudgetUnitTotal::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isReadyForReview(): bool
    {
        return $this->status === self::STATUS_READY;
    }

    public function isFinal(): bool
    {
        return $this->status === self::STATUS_FINAL;
    }

    public function isEditable(): bool
    {
        return in_array($this->status, [self::STATUS_DRAFT, self::STATUS_READY], true);
    }
}
