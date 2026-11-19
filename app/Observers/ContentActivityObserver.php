<?php

namespace App\Observers;

use App\Models\News;
use App\Services\ActivityLogger;
use Illuminate\Database\Eloquent\Model;

class ContentActivityObserver
{
    public function created(Model $model): void
    {
        $this->write($model, 'created');
    }

    public function updated(Model $model): void
    {
        $this->write($model, 'updated');
    }

    public function deleted(Model $model): void
    {
        $this->write($model, 'deleted');
    }

    private function write(Model $model, string $action): void
    {
        $type = match (true) {
            $model instanceof News => 'news',
            default => \Illuminate\Support\Str::snake(class_basename($model)),
        };

        $title = (string) ($model->getAttribute('title') ?? $model->getAttribute('name') ?? 'Untitled');

        $properties = [
            'title' => $title,
            'slug' => $model->getAttribute('slug'),
        ];

        if ($action === 'created') {
            $properties['status'] = $model->getAttribute('status');
        }

        if ($action === 'updated') {
            // Record field names only; do not copy full article/page content.
            $properties['changed_fields'] = array_values(
                array_diff(
                    array_keys($model->getChanges()),
                    ['updated_at']
                )
            );
        }

        app(ActivityLogger::class)->record(
            "{$type}.{$action}",
            \Illuminate\Support\Str::headline($type) . ' "' . $title . '" was ' . $action . '.',
            $model,
            $properties
        );
    }
}
