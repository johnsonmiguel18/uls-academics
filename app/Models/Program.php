<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Program extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return [
            'faqs' => 'array',
            'is_featured' => 'boolean',
        ];
    }

    /** Published programs of published colleges. */
    public function scopePublished(Builder $query): void
    {
        $query->where('status', 'published')
            ->whereHas('college', fn (Builder $college) => $college->where('status', 'published'));
    }

    public function college(): BelongsTo
    {
        return $this->belongsTo(College::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    public function accreditations(): HasMany
    {
        return $this->hasMany(Accreditation::class)->latest('valid_until');
    }

    public function degreeLabel(): string
    {
        return Term::label('degree_type', $this->degree_type);
    }
}
