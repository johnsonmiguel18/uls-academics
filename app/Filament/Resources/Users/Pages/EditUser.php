<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Services\ActivityLogger;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected array $rolesBeforeSave = [];

    protected function beforeSave(): void
    {
        /** @var User $user */
        $user = $this->record;

        $this->rolesBeforeSave = $user->roles()
            ->pluck('name')
            ->all();
    }

    protected function afterSave(): void
    {
        /** @var User $user */
        $user = $this->record;

        $rolesAfterSave = $user->roles()
            ->pluck('name')
            ->all();

        $addedRoles = array_values(
            array_diff($rolesAfterSave, $this->rolesBeforeSave)
        );

        $removedRoles = array_values(
            array_diff($this->rolesBeforeSave, $rolesAfterSave)
        );

        foreach ($addedRoles as $roleName) {
            app(ActivityLogger::class)->record(
                'role.assigned',
                'Role "' . $roleName . '" was assigned to a user.',
                $user,
                [
                    'target_user_id' => $user->getKey(),
                    'target_email' => $user->email,
                    'role' => $roleName,
                ]
            );
        }

        foreach ($removedRoles as $roleName) {
            app(ActivityLogger::class)->record(
                'role.removed',
                'Role "' . $roleName . '" was removed from a user.',
                $user,
                [
                    'target_user_id' => $user->getKey(),
                    'target_email' => $user->email,
                    'role' => $roleName,
                ]
            );
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
