<?php

namespace App\Traits;

use App\Models\Audit;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
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
     */
    protected static function bootAuditable(): void
    {
        static::created(fn($model) => $model->auditChanges('created'));
        static::updated(fn($model) => $model->auditChanges('updated'));

        static::deleted(function ($model) {
            $event = method_exists($model, 'isForceDeleting') && $model->isForceDeleting()
                ? 'force_deleted'
                : 'deleted';

            $model->auditChanges($event);
        });

        /**
         * El evento "restored" solo existe si el modelo usa SoftDeletes;
         * registrarlo incondicionalmente revienta el boot de modelos que no lo usan.
         */
        if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive(static::class), true)) {
            static::restored(fn($model) => $model->auditChanges('restored'));
        }
    }

    /**
     * Registra un evento de auditoría.
     *
     * @param string $event
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
            'actor_type' => $this->resolveActorType(),

            /**
             * Modelo auditado (polimórfico).
             */
            'auditable_type' => $this->getMorphClass(),
            'auditable_id'   => $this->serializeValue(
                $this->getAuditableId()
            ),

            /**
             * Evento ejecutado.
             */
            'event' => $event,

            /**
             * Cambios.
             */
            'old_values' => $this->serializeValue(
                $this->getOldValues($event)
            ),
            'new_values' => $this->serializeValue(
                $this->getNewValues($event)
            ),

            /**
             * Contexto de ejecución.
             */
            'url'        => Request::instance()?->fullUrl(),
            'ip_address' => Request::instance()?->ip(),
            'user_agent' => Request::instance()?->userAgent(),
        ]);

        $this->alertIfSensitive($event);
    }

    /**
     * Notifica (vía Log::critical(), que persiste en /logs por el canal
     * 'database') cuando el evento auditado está en la lista opt-in
     * `protected array $auditAlertEvents = [...]` del modelo — ej. borrar un
     * usuario. Mismo mecanismo de visibilidad que logs:alert-threshold, sin
     * inventar reglas de negocio no confirmadas: cada modelo decide qué
     * eventos propios son sensibles.
     */
    protected function alertIfSensitive(string $event): void
    {
        $sensitiveEvents = property_exists($this, 'auditAlertEvents') ? $this->auditAlertEvents : [];

        if (! in_array($event, $sensitiveEvents, true)) {
            return;
        }

        $actor = Auth::check() ? (Auth::user()->full_name ?? Auth::id()) : $this->resolveActorType();

        Log::critical(sprintf(
            '[audit] Cambio sensible: %s#%s — evento "%s" ejecutado por %s',
            class_basename($this),
            $this->serializeValue($this->getAuditableId()),
            $event,
            $actor
        ));
    }

    /**
     * Determina el actor_type real de la acción (user | job | command | system).
     *
     * Antes solo distinguía user/system — con un usuario autenticado en
     * sesión de cola (raro pero posible, ej. impersonation en un job) o
     * corriendo por CLI, el resto de la app quedaba sin poder diferenciar
     * "lo hizo un cron/artisan command" de "lo hizo el sistema en un
     * request HTTP anónimo", pese a que el schema/UI ya soportaban ambos
     * valores.
     */
    protected function resolveActorType(): string
    {
        if (Auth::check()) {
            return 'user';
        }

        if (! app()->runningInConsole()) {
            return 'system';
        }

        $command = $_SERVER['argv'][1] ?? null;

        return str_starts_with((string) $command, 'queue:') ? 'job' : 'command';
    }

    /**
     * Obtiene el identificador auditable del modelo.
     *
     * - Modelos normales: clave primaria
     * - Pivots: identidad compuesta definida por el modelo
     */
    protected function getAuditableId(): int|string|array|null
    {
        // Si es un pivot con clave compuesta, usar la identidad del pivot
        if ($this instanceof Pivot) {
            return $this->getPivotIdentity();
        }

        // Para modelos normales, usar la clave primaria
        return $this->getKey();
    }

    /**
     * Identidad compuesta del pivot.
     *
     * Debe ser sobrescrito por cada pivot auditable.
     *
     * Ejemplo:
     * [
     *   'commerce_id' => 1,
     *   'currency_id' => 2
     * ]
     *
     * @return array<string, mixed>
     */
    protected function getPivotIdentity(): array
    {
        return [];
    }

    /**
     * Obtiene los valores anteriores del modelo según el evento.
     */
    protected function getOldValues(string $event): ?array
    {
        if (! in_array($event, ['updated', 'deleted', 'restored', 'force_deleted'], true)) {
            return null;
        }

        return $this->filterAuditableAttributes(
            $this->getOriginal()
        );
    }

    /**
     * Obtiene los valores nuevos del modelo según el evento.
     */
    protected function getNewValues(string $event): ?array
    {
        if (! in_array($event, ['created', 'updated', 'restored'], true)) {
            return null;
        }

        return $this->filterAuditableAttributes(
            $this->getAttributes()
        );
    }

    /**
     * Filtra atributos no relevantes o sensibles para auditoría.
     *
     * Además de los timestamps, excluye los campos declarados por el modelo
     * en `protected array $auditHidden = [...]` (ej. password, tokens) para
     * que no queden en texto plano en old_values/new_values.
     */
    protected function filterAuditableAttributes(array $values): array
    {
        $hidden = property_exists($this, 'auditHidden') ? $this->auditHidden : [];

        return collect($values)
            ->except(['created_at', 'updated_at', 'deleted_at', ...$hidden])
            ->toArray();
    }

    /**
     * Serializa valores para persistencia segura en base de datos.
     *
     * - int|string => se devuelve tal cual
     * - array      => JSON
     * - null       => null
     */
    protected function serializeValue(mixed $value): string|int|null
    {
        if (is_null($value)) {
            return null;
        }

        if (is_array($value)) {
            return json_encode($value, JSON_THROW_ON_ERROR);
        }

        return $value;
    }
}
