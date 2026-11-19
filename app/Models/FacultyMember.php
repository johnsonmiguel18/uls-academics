<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FacultyMember extends Model
{
    protected $guarded = ['id'];

    protected function casts(): array
    {
        return ['is_published' => 'boolean'];
    }

    /** "Engr. Ariel M. Lorenzo, DEM" -> "AL", for people without a photo. */
    public static function initials(?string $name): string
    {
        $name = trim(\Illuminate\Support\Str::before((string) $name, ','));
        $name = preg_replace('/^((rev|fr|atty|engr|dr|mr|mrs|ms|sr|sis)\.?\s+)+/i', '', $name);
        $words = array_values(array_filter(
            preg_split('/\s+/', $name, -1, PREG_SPLIT_NO_EMPTY),
            fn ($word) => ! preg_match('/^[A-Z]\.$/i', $word),
        ));

        return strtoupper(mb_substr($words[0] ?? 'U', 0, 1) . (count($words) > 1 ? mb_substr(end($words), 0, 1) : ''));
    }

    public function college(): BelongsTo
    {
        return $this->belongsTo(College::class);
    }

    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }
}
