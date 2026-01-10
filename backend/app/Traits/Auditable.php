<?php

namespace App\Traits;

use App\Models\Audit;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Trait Auditable
 *
 * Registra automáticamente eventos de auditoría sobre un modelo Eloquent.
 *
 * Eventos soportados:
 * - created
 * - updated
 * - deleted
 * - restored
 *
 * Diseñado para funcionar correctamente en:
 * - Requests HTTP
 * - Seeders
 * - Jobs
 * - Commands (CLI)
 */
trait Auditable
{
    /**
     * Boot del trait Auditable.
     *
     * Registra los listeners de eventos del modelo.
     *
     * @return void
     */
    protected static function bootAuditable(): void
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

        /**
         * Evento disponible solo si el modelo usa SoftDeletes.
         */
        static::restored(function ($model) {
            $model->auditChanges('restored');
        });
    }

    /**
     * Registra un evento de auditoría.
     *
     * @param string $event Tipo de evento (created, updated, deleted, restored)
     * @return void
     */
    protected function auditChanges(string $event): void
    {
        Audit::create([
            /**
             * Usuario humano que ejecutó la acción.
             * Será null para seeders, jobs, commands o acciones del sistema.
             */
            'user_id' => Auth::id(),

            /**
             * Tipo de actor que originó la acción.
             * user   => acción humana autenticada
             * system => seeders, jobs, CLI, procesos automáticos
             */
            'actor_type' => Auth::check() ? 'user' : 'system',

            /**
             * Modelo auditado (polimórfico).
             */
            'auditable_type' => $this->getMorphClass(),
            'auditable_id' => $this->getKey(),

            /**
             * Evento ejecutado.
             */
            'event' => $event,

            /**
             * Valores anteriores (cuando aplica).
             */
            'old_values' => $this->getOldValues($event),

            /**
             * Valores nuevos (cuando aplica).
             */
            'new_values' => $this->getNewValues($event),

            /**
             * Contexto de la ejecución.
             * En CLI o jobs pueden ser null.
             */
            'url' => Request::instance()?->fullUrl(),
            'ip_address' => Request::instance()?->ip(),
            'user_agent' => Request::instance()?->userAgent(),
        ]);
    }

    /**
     * Obtiene los valores anteriores del modelo según el evento.
     *
     * @param string $event
     * @return array|null
     */
    protected function getOldValues(string $event): ?array
    {
        return in_array($event, ['updated', 'deleted', 'restored'], true)
            ? $this->getOriginal()
            : null;
    }

    /**
     * Obtiene los valores nuevos del modelo según el evento.
     *
     * @param string $event
     * @return array|null
     */
    protected function getNewValues(string $event): ?array
    {
        return in_array($event, ['created', 'updated', 'restored'], true)
            ? $this->getAttributes()
            : null;
    }
}
