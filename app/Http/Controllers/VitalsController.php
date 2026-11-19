<?php

namespace App\Http\Controllers;

use App\Models\WebVital;
use Illuminate\Http\Request;

/**
 * Receives loading-speed measurements sent by the public pages (layouts/app.blade.php)
 * with navigator.sendBeacon. Accepts only known metrics and sane values.
 */
class VitalsController extends Controller
{
    public function __invoke(Request $request)
    {
        $data = json_decode($request->getContent(), true);

        if (! is_array($data) || ! is_array($data['metrics'] ?? null)) {
            return response()->noContent(422);
        }

        $path = substr(parse_url(is_string($data['path'] ?? null) ? $data['path'] : '/', PHP_URL_PATH) ?: '/', 0, 255);
        $device = ($data['device'] ?? '') === 'mobile' ? 'mobile' : 'desktop';
        $rows = [];

        foreach ($data['metrics'] as $metric => $value) {
            if (isset(WebVital::THRESHOLDS[$metric]) && is_numeric($value) && $value >= 0 && $value < 120000) {
                $rows[] = ['metric' => $metric, 'value' => round((float) $value, 4), 'device' => $device, 'path' => $path, 'created_at' => now()];
            }
        }

        if ($rows) {
            WebVital::insert($rows); // one query per visit
        }

        return response()->noContent();
    }
}
