<?php

namespace App\Traits;

use App\Models\Audit;
use Illuminate\Database\Eloquent\Relations\Pivot;
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
 * - force_deleted
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
        static::created(fn($model) => $model->auditChanges('created'));
        static::updated(fn($model) => $model->auditChanges('updated'));
        static::deleted(fn($model) => $model->auditChanges('deleted'));

        /**
         * Eventos disponibles solo si el modelo usa SoftDeletes.
         */
        static::restored(fn($model) => $model->auditChanges('restored'));
        static::forceDeleted(fn($model) => $model->auditChanges('force_deleted'));
    }

    /**
     * Registra un evento de auditoría.
     *
     * @param string $event
     * @return void
     */
    protected function auditChanges(string $event): void
    {
        Audit::create([
            /**
             * Usuario humano que ejecutó la acción.
             * Null para system / jobs / CLI.
             */
            'user_id' => Auth::id(),

            /**
             * Tipo de actor que originó la acción.
             */
            'actor_type' => Auth::check() ? 'user' : 'system',

            /**
             * Modelo auditado (polimórfico).
             */
            'auditable_type' => $this->getMorphClass(),
            'auditable_id'   => $this->getAuditableId(),

            /**
             * Evento ejecutado.
             */
            'event' => $event,

            /**
             * Cambios.
             */
            'old_values' => $this->getOldValues($event),
            'new_values' => $this->getNewValues($event),

            /**
             * Contexto de ejecución.
             */
            'url'        => Request::instance()?->fullUrl(),
            'ip_address' => Request::instance()?->ip(),
            'user_agent' => Request::instance()?->userAgent(),
        ]);
    }

    /**
     * Obtiene el identificador auditable del modelo.
     *
     * - Modelos normales: clave primaria
     * - Pivots: identidad compuesta definida por el modelo
     *
     * @return int|string|array|null
     */
    protected function getAuditableId(): int|string|array|null
    {
        if (! is_null($this->getKey())) {
            return $this->getKey();
        }

        if ($this instanceof Pivot) {
            return $this->getPivotIdentity();
        }

        return null;
    }

    /**
     * Identidad compuesta del pivot.
     *
     * Debe ser sobrescrito por cada pivot auditable.
     *
     * @return array<string, mixed>
     */
    protected function getPivotIdentity(): array
    {
        return [];
    }

    /**
     * Obtiene los valores anteriores del modelo según el evento.
     *
     * @param string $event
     * @return array|null
     */
    protected function getOldValues(string $event): ?array
    {
        return in_array($event, ['updated', 'deleted', 'restored', 'force_deleted'], true)
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
