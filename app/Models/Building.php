<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable([
    'client_id',
    'name',
    'address_line1',
    'address_line2',
    'city',
    'postcode',
    'reference',
    'is_active',
])]
class Building extends Model
{
    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
        ];
    }

    public function client(): BelongsTo
    {
        return $this->belongsTo(Client::class);
    }

    public function units(): HasMany
    {
        return $this->hasMany(Unit::class);
    }

    public function serviceChargeYears(): HasMany
    {
        return $this->hasMany(ServiceChargeYear::class);
    }

    public function schedules(): HasMany
    {
        return $this->hasMany(Schedule::class);
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }

    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function fullAddress(): string
    {
        return collect([
            $this->address_line1,
            $this->address_line2,
            $this->city,
            $this->postcode,
        ])->filter()->implode(', ');
    }
}
