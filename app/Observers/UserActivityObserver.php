<?php

namespace App\Observers;

use App\Models\User;
use App\Services\ActivityLogger;

class UserActivityObserver
{
    public function created(User $user): void
    {
        $this->log(
            $user,
            'user.created',
            'User account "' . $user->name . '" was created.',
            ['email' => $user->email]
        );
    }

    public function updated(User $user): void
    {
        $changedFields = array_values(array_diff(
            array_keys($user->getChanges()),
            ['updated_at', 'remember_token', 'password']
        ));

        if ($changedFields === []) {
            return;
        }

        $this->log(
            $user,
            'user.updated',
            'User account "' . $user->name . '" was updated.',
            [
                'email' => $user->email,
                'changed_fields' => $changedFields,
            ]
        );
    }

    public function deleted(User $user): void
    {
        $this->log(
            $user,
            'user.deleted',
            'User account "' . $user->name . '" was deleted.',
            ['email' => $user->email]
        );
    }

    private function log(
        User $subject,
        string $event,
        string $description,
        array $properties = []
    ): void {
        app(ActivityLogger::class)->record(
            $event,
            $description,
            $subject,
            $properties
        );
    }
}
