<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'actor_name',
        'actor_email',
        'actor_roles',
        'event',
        'description',
        'subject_type',
        'subject_id',
        'properties',
        'ip_address',
        'user_agent',
        'http_method',
        'route_name',
        'url_path',
        'created_at',
    ];

    /** Readable names for the event codes, used by the admin table and its filter. */
    public const EVENTS = [
        'system.test' => 'System Test',
        'news.created' => 'News Created',
        'news.updated' => 'News Updated',
        'news.deleted' => 'News Deleted',
        'auth.login' => 'Successful Login',
        'auth.failed' => 'Failed Login',
        'auth.logout' => 'Logout',
        'auth.denied' => 'Access Denied',
        'user.created' => 'User Created',
        'user.updated' => 'User Updated',
        'user.deleted' => 'User Deleted',
        'role.assigned' => 'Role Assigned',
        'role.removed' => 'Role Removed',
        'role.permission.assigned' => 'Role Permission Assigned',
        'role.permission.removed' => 'Role Permission Removed',
        'settings.ai_updated' => 'AI Settings Updated',
    ];

    protected function casts(): array
    {
        return [
            'actor_roles' => 'array',
            'properties' => 'array',
            'created_at' => 'datetime',
        ];
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
