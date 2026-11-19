<?php

namespace App\Services;

use App\Models\ActivityLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;
use Illuminate\Support\Str;

class ActivityLogger
{
    /**
     * Record an audit event.
     *
     * Do not pass passwords, tokens, session data, or entire request payloads
     * into this service.
     */
    public function record(
        string $event,
        string $description,
        ?Model $subject = null,
        array $properties = [],
    ): ActivityLog {
        $user = Auth::user();

        $roles = $user && method_exists($user, 'roles')
            ? $user->roles()->pluck('name')->all()
            : [];

        return ActivityLog::create([
            'user_id' => $user?->getAuthIdentifier(),
            'actor_name' => $user?->name,
            'actor_email' => $user?->email,
            'actor_roles' => $roles,

            'event' => Str::limit($event, 100, ''),
            'description' => $description,

            'subject_type' => $subject
                ? $subject->getMorphClass()
                : null,
            'subject_id' => $subject?->getKey(),

            'properties' => $this->sanitizeProperties($properties),

            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent()
                ? Str::limit(Request::userAgent(), 2000, '')
                : null,
            'http_method' => Request::method(),
            'route_name' => Request::route()?->getName(),
            'url_path' => Str::limit(Request::path(), 500, ''),

            'created_at' => now(),
        ]);
    }

    /**
     * Remove common secret-bearing keys from logged properties.
     */
    private function sanitizeProperties(array $properties): array
    {
        $safe = [];

        foreach ($properties as $key => $value) {
            $keyString = (string) $key;

            if (preg_match(
                '/password|token|secret|authorization|cookie|session|api[_-]?key/i',
                $keyString
            )) {
                continue;
            }

            if (is_array($value)) {
                $safe[$keyString] = $this->sanitizeProperties($value);
                continue;
            }

            if (
                is_null($value)
                || is_scalar($value)
            ) {
                $safe[$keyString] = is_string($value)
                    ? Str::limit($value, 1000, '')
                    : $value;
            }
        }

        return $safe;
    }
}
