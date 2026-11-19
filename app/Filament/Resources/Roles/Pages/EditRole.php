<?php

namespace App\Filament\Resources\Roles\Pages;

use App\Filament\Resources\Roles\RoleResource;
use App\Models\ActivityLog;
use App\Services\ActivityLogger;
use Filament\Resources\Pages\EditRecord;
use Spatie\Permission\Models\Role;

class EditRole extends EditRecord
{
    protected static string $resource = RoleResource::class;

    protected array $permissionsBeforeSave = [];

    protected function beforeSave(): void
    {
        /** @var Role $role */
        $role = $this->record;

        $this->permissionsBeforeSave = $role->permissions()
            ->pluck('name')
            ->all();
    }

    protected function afterSave(): void
    {
        /** @var Role $role */
        $role = $this->record;

        $permissionsAfterSave = $role->permissions()
            ->pluck('name')
            ->all();

        $added = array_values(array_diff(
            $permissionsAfterSave,
            $this->permissionsBeforeSave
        ));

        $removed = array_values(array_diff(
            $this->permissionsBeforeSave,
            $permissionsAfterSave
        ));

        $logger = app(ActivityLogger::class);

        foreach ($added as $permissionName) {
            $logger->record(
                'role.permission.assigned',
                'Permission "' . $permissionName .
                    '" was assigned to role "' . $role->name . '".',
                $role,
                [
                    'role_id' => $role->getKey(),
                    'role_name' => $role->name,
                    'permission' => $permissionName,
                    'action' => 'assigned',
                ]
            );
        }

        foreach ($removed as $permissionName) {
            $logger->record(
                'role.permission.removed',
                'Permission "' . $permissionName .
                    '" was removed from role "' . $role->name . '".',
                $role,
                [
                    'role_id' => $role->getKey(),
                    'role_name' => $role->name,
                    'permission' => $permissionName,
                    'action' => 'removed',
                ]
            );
        }
    }
}
