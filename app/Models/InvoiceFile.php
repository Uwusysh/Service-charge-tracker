<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

#[Fillable(['invoice_id', 'disk', 'path', 'original_name', 'mime', 'size'])]
class InvoiceFile extends Model
{
    protected function casts(): array
    {
        return [
            'size' => 'integer',
        ];
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(Invoice::class);
    }

    public function absolutePath(): string
    {
        return Storage::disk($this->disk)->path($this->path);
    }
}
