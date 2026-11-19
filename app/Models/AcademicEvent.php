<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AcademicEvent extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'starts_at' => 'datetime',
            'ends_at' => 'datetime',
            'is_published' => 'boolean',
        ];
    }

    /** Published events that have not ended yet, soonest first. */
    public function scopeUpcoming(Builder $query): void
    {
        $query->where('is_published', true)
            ->where(fn (Builder $q) => $q->where('ends_at', '>=', now())
                ->orWhere(fn (Builder $q) => $q->whereNull('ends_at')->where('starts_at', '>=', now()->startOfDay())))
            ->orderBy('starts_at');
    }

    public function college(): BelongsTo
    {
        return $this->belongsTo(College::class);
    }
}
