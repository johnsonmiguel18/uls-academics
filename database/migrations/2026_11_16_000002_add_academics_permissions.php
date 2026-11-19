<?php

use Illuminate\Database\Migrations\Migration;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        $manage = Permission::findOrCreate('manage_academics', 'web');

        foreach (['super_admin', 'admin'] as $name) {
            Role::findOrCreate($name, 'web')->givePermissionTo($manage);
        }

        // College administrators edit only their own college (see App\Filament\Concerns\ScopedToCollege).
        $collegeAdmin = Role::findOrCreate('college_admin', 'web');

        foreach (['view_news', 'create_news', 'edit_news', 'publish_news'] as $name) {
            $collegeAdmin->givePermissionTo(Permission::findOrCreate($name, 'web'));
        }

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }

    public function down(): void
    {
        Role::where('name', 'college_admin')->delete();
        Permission::where('name', 'manage_academics')->delete();

        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
};
