<?php

namespace App\Observers;

use App\Services\ActivityLogger;
use Illuminate\Database\Eloquent\Model;

class RolePermissionActivityObserver
{
    public function created(Model $model): void
    {
        $this->record($model, 'created');
    }

    public function updated(Model $model): void
    {
        $changes = array_values(array_diff(
            array_keys($model->getChanges()),
            ['updated_at']
        ));

        if ($changes === []) {
            return;
        }

        $this->record($model, 'updated', [
            'changed_fields' => $changes,
        ]);
    }

    public function deleted(Model $model): void
    {
        $this->record($model, 'deleted');
    }

    private function record(
        Model $model,
        string $action,
        array $extra = []
    ): void {
        $type = class_basename($model);
        $name = (string) (
            $model->getAttribute('name')
            ?? $model->getAttribute('guard_name')
            ?? 'Unknown'
        );

        $event = strtolower($type) . '.' . $action;

        app(ActivityLogger::class)->record(
            $event,
            $type . ' "' . $name . '" was ' . $action . '.',
            $model,
            array_merge([
                'record_type' => $type,
                'record_id' => $model->getKey(),
                'name' => $name,
            ], $extra)
        );
    }
}
