<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Department extends Model
{
    protected $guarded = ['id'];

    public function college(): BelongsTo
    {
        return $this->belongsTo(College::class);
    }

    public function programs(): HasMany
    {
        return $this->hasMany(Program::class)->orderBy('sort_order')->orderBy('name');
    }

    public function faculty(): HasMany
    {
        return $this->hasMany(FacultyMember::class)->where('is_published', true)->orderBy('sort_order')->orderBy('name');
    }

    public function documents(): HasMany
    {
        return $this->hasMany(Document::class)->where('is_public', true)->latest();
    }
}
