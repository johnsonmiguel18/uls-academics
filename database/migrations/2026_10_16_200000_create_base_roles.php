<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/** Roles and permissions of a fresh ULS Academics database (the main website got them from its SQL dump). */
return new class extends Migration
{
    private const ROLES = [
        'super_admin' => ['view_news', 'create_news', 'edit_news', 'delete_news', 'publish_news', 'manage_users', 'manage_roles'],
        'admin' => ['view_news', 'create_news', 'edit_news', 'delete_news', 'publish_news'],
        'editor' => ['view_news', 'create_news', 'edit_news'],
    ];

    public function up(): void
    {
        // Setup, not admin activity: keep it out of the activity log.
        \Illuminate\Database\Eloquent\Model::withoutEvents(function (): void {
            foreach (self::ROLES as $role => $permissions) {
                Role::findOrCreate($role, 'web')->givePermissionTo(
                    array_map(fn ($name) => Permission::findOrCreate($name, 'web'), $permissions)
                );
            }
        });

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Role::whereIn('name', array_keys(self::ROLES))->delete();
        Permission::whereIn('name', array_merge(...array_values(self::ROLES)))->delete();
    }
};
