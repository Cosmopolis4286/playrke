<?php

namespace App\Traits;

use App\Models\Audit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

trait Auditable
{
    protected static function bootAuditable()
    {
        static::created(function ($model) {
            $model->auditChanges('created');
        });

        static::updated(function ($model) {
            $model->auditChanges('updated');
        });

        static::deleted(function ($model) {
            $model->auditChanges('deleted');
        });

        // Opcionalmente para restaurar soft deletes
        static::restored(function ($model) {
            $model->auditChanges('restored');
        });
    }

    protected function auditChanges(string $event): void
    {
        Audit::create([
            'user_id' => Auth::id(),
            'auditable_type' => get_class($this),
            'auditable_id' => $this->getKey(),
            'event' => $event,
            'old_values' => $event === 'updated' || $event === 'deleted' || $event === 'restored'
                ? $this->getOriginal()
                : null,
            'new_values' => $event === 'created' || $event === 'updated' || $event === 'restored'
                ? $this->getAttributes()
                : null,
            'url' => Request::fullUrl(),
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
        ]);
    }
}
