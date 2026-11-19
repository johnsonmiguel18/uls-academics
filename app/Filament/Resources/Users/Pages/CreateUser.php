<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Services\ActivityLogger;
use Filament\Resources\Pages\CreateRecord;

class CreateUser extends CreateRecord
{
    protected static string $resource = UserResource::class;

    protected function afterCreate(): void
    {
        /** @var User $user */
        $user = $this->record;

        $roles = $user->roles()->pluck('name')->all();

        foreach ($roles as $roleName) {
            app(ActivityLogger::class)->record(
                'role.assigned',
                'Role "' . $roleName . '" was assigned to a newly created user.',
                $user,
                [
                    'target_user_id' => $user->getKey(),
                    'target_email' => $user->email,
                    'role' => $roleName,
                ]
            );
        }
    }
}
