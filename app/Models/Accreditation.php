<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Accreditation extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['valid_until' => 'date'];
    }

    public function program(): BelongsTo
    {
        return $this->belongsTo(Program::class);
    }

    public function label(): string
    {
        return trim("{$this->agency} {$this->level}");
    }
}
