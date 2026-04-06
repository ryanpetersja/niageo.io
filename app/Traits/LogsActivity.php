<?php

namespace App\Traits;

use App\Models\ActivityLog;

trait LogsActivity
{
    public static function bootLogsActivity(): void
    {
        static::created(function ($model) {
            $model->logActivity('created');
        });

        static::updated(function ($model) {
            $model->logActivity('updated');
        });

        static::deleted(function ($model) {
            $model->logActivity('deleted');
        });
    }

    protected function logActivity(string $action): void
    {
        $userId = auth()->id();

        ActivityLog::create([
            'user_id' => $userId,
            'action' => $action,
            'subject_type' => get_class($this),
            'subject_id' => $this->getKey(),
            'description' => $this->getActivityDescription($action),
            'properties' => $action === 'updated' ? ['changed' => $this->getChanges()] : null,
        ]);
    }

    protected function getActivityDescription(string $action): string
    {
        $modelName = class_basename($this);
        return "{$modelName} #{$this->getKey()} was {$action}";
    }
}
