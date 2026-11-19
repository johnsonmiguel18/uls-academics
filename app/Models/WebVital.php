<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** One loading-speed measurement from a real visit (see VitalsController). */
class WebVital extends Model
{
    public const UPDATED_AT = null;

    /** Google's "good" and "poor" limits per metric (ms; CLS has no unit). */
    public const THRESHOLDS = [
        'LCP' => [2500, 4000],
        'INP' => [200, 500],
        'CLS' => [0.1, 0.25],
        'FCP' => [1800, 3000],
        'TTFB' => [800, 1800],
    ];

    protected $guarded = ['id'];

    /** 75th percentile of a metric since a date: the value Google uses to rate a site. */
    public static function p75(string $metric, \DateTimeInterface $since, ?string $device = null): ?array
    {
        $query = self::query()->where('metric', $metric)->where('created_at', '>=', $since)
            ->when($device, fn ($q) => $q->where('device', $device));
        $count = $query->count();

        if ($count === 0) {
            return null;
        }

        $value = (clone $query)->orderBy('value')->offset((int) floor(0.75 * ($count - 1)))->limit(1)->value('value');

        return ['value' => (float) $value, 'count' => $count];
    }
}
