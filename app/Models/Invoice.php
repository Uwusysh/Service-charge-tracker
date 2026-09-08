<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'building_id',
    'service_charge_year_id',
    'budget_id',
    'budget_line_id',
    'supplier_id',
    'invoice_reference',
    'normalized_reference',
    'invoice_date',
    'net_amount',
    'vat_amount',
    'gross_amount',
    'status',
    'description',
    'submitted_by',
    'submitted_at',
    'reviewed_by',
    'reviewed_at',
    'review_note',
    'rejection_reason',
    'reverse_reason',
])]
class Invoice extends Model
{
    public const STATUS_DRAFT = 'draft';

    public const STATUS_SUBMITTED = 'submitted';

    public const STATUS_APPROVED = 'approved';

    public const STATUS_REJECTED = 'rejected';

    protected function casts(): array
    {
        return [
            'invoice_date' => 'date',
            'net_amount' => 'decimal:2',
            'vat_amount' => 'decimal:2',
            'gross_amount' => 'decimal:2',
            'submitted_at' => 'datetime',
            'reviewed_at' => 'datetime',
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

    public function budget(): BelongsTo
    {
        return $this->belongsTo(Budget::class);
    }

    public function budgetLine(): BelongsTo
    {
        return $this->belongsTo(BudgetLine::class);
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    public function files(): HasMany
    {
        return $this->hasMany(InvoiceFile::class);
    }

    public static function normalizeReference(string $reference): string
    {
        return strtolower(trim($reference));
    }
}
