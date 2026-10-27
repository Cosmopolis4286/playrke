<?php

namespace App\Models\Pivots;

use App\Traits\Auditable;
use Illuminate\Database\Eloquent\Relations\Pivot;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Clase base para pivots auditables con SoftDeletes.
 *
 * Facilita la definición de identidad compuesta para auditoría
 * mediante la propiedad $pivotIdentityKeys en cada hijo.
 */
abstract class BasePivot extends Pivot
{
    use Auditable, SoftDeletes;

    /**
     * Deshabilita asignación masiva no intencionada.
     *
     * @var array<int, string>
     */
    protected $guarded = ['*'];

    /**
     * Define las claves que forman la identidad compuesta para auditoría.
     *
     * Cada pivot hijo debe definir esta propiedad con los nombres
     * de columnas que componen su clave compuesta.
     *
     * Ejemplo:
     * protected array $pivotIdentityKeys = ['commerce_id', 'currency_id'];
     *
     * @var array<int, string>
     */
    protected array $pivotIdentityKeys = [];

    /**
     * Obtiene la identidad compuesta del pivot para auditoría.
     *
     * Construye el array de identidad usando las claves definidas
     * en $pivotIdentityKeys y sus valores actuales.
     *
     * @return array<string, mixed>
     */
    protected function getPivotIdentity() : array
    {
        $identity = [];

        foreach ($this->pivotIdentityKeys as $key) {
            $identity[$key] = $this->getAttribute($key);
        }

        return $identity;
    }
}
