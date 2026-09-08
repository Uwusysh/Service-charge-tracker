<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['code', 'name', 'sort_order'])]
class CostHeading extends Model
{
    public function budgetLines(): HasMany
    {
        return $this->hasMany(BudgetLine::class);
    }
}
