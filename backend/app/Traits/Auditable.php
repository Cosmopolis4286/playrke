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
    protected static function bootAuditable() : void
    {
        static::created(fn ($model) => $model->auditChanges('created'));
        static::updated(fn ($model) => $model->auditChanges('updated'));

        static::deleted(function($model) {
            $event = method_exists($model, 'isForceDeleting') && $model->isForceDeleting()
                ? 'force_deleted'
                : 'deleted';

            $model->auditChanges($event);
        });

        /*
         * El evento "restored" solo existe si el modelo usa SoftDeletes;
         * registrarlo incondicionalmente revienta el boot de modelos que no lo usan.
         */
        if (in_array(\Illuminate\Database\Eloquent\SoftDeletes::class, class_uses_recursive(static::class), true)) {
            static::restored(fn ($model) => $model->auditChanges('restored'));
        }
    }

    /**
     * Notifica (vía Log::critical(), que persiste en /logs por el canal
     * 'database') cuando el evento auditado está en la lista opt-in
     * `protected array $auditAlertEvents = [...]` del modelo — ej. borrar un
     * usuario. Mismo mecanismo de visibilidad que logs:alert-threshold, sin
     * inventar reglas de negocio no confirmadas: cada modelo decide qué
     * eventos propios son sensibles.
     */
    protected function alertIfSensitive(string $event) : void
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
            $actor,
        ));
    }

    /**
     * Registra un evento de auditoría.
     */
    protected function auditChanges(string $event) : void
    {
        $oldValuesPreview = $this->getOldValues($event);
        $newValuesPreview = $this->getNewValues($event);

        if ($event === 'updated' && ! $this->hasAuditableDiff($oldValuesPreview, $newValuesPreview)) {
            /*
             * updated() se dispara con isDirty() en al menos un atributo,
             * pero ese atributo puede ser uno excluido por auditHidden (ej.
             * remember_token: Laravel lo regenera en CADA logout de un
             * usuario con "recordarme" activo, vía
             * SessionGuard::cycleRememberToken() -> EloquentUserProvider::
             * updateRememberToken() -> $user->save()). Sin este corte, cada
             * logout generaba un registro 'updated' con url=/logout y diff
             * vacío (todos los campos visibles "sin cambio") — ruido que no
             * aporta nada rastreable, ya que el único campo que sí cambió es
             * justamente el que se oculta por ser un secreto de sesión.
             */
            return;
        }

        Audit::create([
            /*
             * Usuario humano que ejecutó la acción.
             * Null para system / jobs / CLI.
             */
            'user_id' => Auth::id(),

            /*
             * Tipo de actor que originó la acción.
             */
            'actor_type' => $this->resolveActorType(),

            /*
             * Modelo auditado (polimórfico).
             */
            'auditable_type' => $this->getMorphClass(),
            /*
             * auditable_id/old_values/new_values se pasan en su forma nativa
             * (array o escalar), SIN json_encode() manual: las tres columnas
             * ya tienen cast 'array' en Audit — Eloquent las serializa al
             * guardar y las decodifica al leer. Codificarlas aquí también
             * causaba doble serialización (un JSON-string quedaba persistido
             * como valor escalar dentro de la columna JSON), por lo que
             * $audit->old_values/new_values nunca se leían como array real en
             * ningún consumidor (AuditController, AuditService, etc.).
             */
            'auditable_id' => $this->getAuditableId(),

            /*
             * Evento ejecutado.
             */
            'event' => $event,

            /*
             * Cambios.
             */
            'old_values' => $oldValuesPreview,
            'new_values' => $newValuesPreview,

            /*
             * Contexto de ejecución.
             */
            'url' => Request::instance()?->fullUrl(),
            'ip_address' => Request::instance()?->ip(),
            'user_agent' => Request::instance()?->userAgent(),
        ]);

        $this->alertIfSensitive($event);
    }

    /**
     * Filtra atributos no relevantes o sensibles para auditoría.
     *
     * Además de los timestamps, excluye los campos declarados por el modelo
     * en `protected array $auditHidden = [...]` (ej. password, tokens) para
     * que no queden en texto plano en old_values/new_values.
     */
    protected function filterAuditableAttributes(array $values) : array
    {
        $hidden = property_exists($this, 'auditHidden') ? $this->auditHidden : [];

        return collect($values)
            ->except(['created_at', 'updated_at', 'deleted_at', ...$hidden])
            ->toArray();
    }

    /**
     * Obtiene el identificador auditable del modelo.
     *
     * - Modelos normales: clave primaria
     * - Pivots: identidad compuesta definida por el modelo
     */
    protected function getAuditableId() : int|string|array|null
    {
        // Si es un pivot con clave compuesta, usar la identidad del pivot
        if ($this instanceof Pivot) {
            return $this->getPivotIdentity();
        }

        // Para modelos normales, usar la clave primaria
        return $this->getKey();
    }

    /**
     * Obtiene los valores nuevos del modelo según el evento.
     */
    protected function getNewValues(string $event) : ?array
    {
        if (! in_array($event, ['created', 'updated', 'restored'], true)) {
            return null;
        }

        return $this->filterAuditableAttributes(
            $this->getAttributes(),
        );
    }

    /**
     * Obtiene los valores anteriores del modelo según el evento.
     *
     * getRawOriginal() (no getOriginal()) a propósito: getOriginal() aplica
     * los casts del modelo (ej. 'datetime' produce un Carbon), mientras que
     * getNewValues() usa getAttributes(), que NUNCA aplica casts y devuelve
     * el valor crudo tal como está en el atributo interno. Comparar un lado
     * casteado contra el otro sin castear generaba "cambios" falsos en cada
     * updated() para cualquier campo con cast datetime/array/etc. — el mismo
     * instante quedaba serializado con formato distinto en old_values
     * (Carbon::jsonSerialize(), ISO+microsegundos+Z) y new_values (string
     * crudo de la BD), aunque el valor real no hubiera cambiado.
     */
    protected function getOldValues(string $event) : ?array
    {
        if (! in_array($event, ['updated', 'deleted', 'restored', 'force_deleted'], true)) {
            return null;
        }

        return $this->filterAuditableAttributes(
            $this->getRawOriginal(),
        );
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
    protected function getPivotIdentity() : array
    {
        return [];
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
    protected function resolveActorType() : string
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
     * Serializa un valor a texto legible para logs (ver alertIfSensitive()).
     * NO se usa para las columnas auditable_id/old_values/new_values de
     * Audit — esas ya tienen cast 'array' y se pasan en su forma nativa
     * (ver auditChanges()), para que Eloquent sea el único responsable de
     * (de)serializarlas.
     *
     * - int|string => se devuelve tal cual
     * - array      => JSON
     * - null       => null
     */
    protected function serializeValue(mixed $value) : string|int|null
    {
        if (is_null($value)) {
            return null;
        }

        if (is_array($value)) {
            return json_encode($value, JSON_THROW_ON_ERROR);
        }

        return $value;
    }

    /**
     * true si hay al menos un campo (ya filtrado por auditHidden/timestamps)
     * cuyo valor difiere entre old y new — ver el corte en auditChanges()
     * para 'updated'. created/deleted/restored no llaman esto (uno de los
     * dos lados siempre es null ahí por diseño de getOldValues/getNewValues,
     * así que "sin diff" no aplica como concepto para esos eventos).
     */
    private function hasAuditableDiff(?array $oldValues, ?array $newValues) : bool
    {
        $old = $oldValues ?? [];
        $new = $newValues ?? [];

        foreach (array_unique([...array_keys($old), ...array_keys($new)]) as $key) {
            if (($old[$key] ?? null) !== ($new[$key] ?? null)) {
                return true;
            }
        }

        return false;
    }
}
