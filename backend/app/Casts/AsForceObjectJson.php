<?php

namespace App\Casts;

use Illuminate\Contracts\Database\Eloquent\CastsAttributes;
use Illuminate\Database\Eloquent\Model;

/**
 * Cast para columnas JSON que deben persistir siempre como objeto (`{}`),
 * nunca como array (`[]`), sin importar cuántas entradas tengan ni si sus
 * claves son numéricas.
 *
 * El cast nativo `'array'` de Eloquent usa `json_encode()` sin flags: un
 * array asociativo con una sola entrada cuya clave normaliza a un entero
 * (ej. "1395534469177655561" en algunos contextos, o cualquier clave que
 * PHP interprete como índice de lista) se serializa como `[...]` en vez de
 * `{...}`, perdiendo la clave (currency_id) al decodificarse en el frontend.
 */
class AsForceObjectJson implements CastsAttributes
{
    public function get(Model $model, string $key, mixed $value, array $attributes): ?array
    {
        if ($value === null) {
            return null;
        }

        $decoded = json_decode($value, true);

        return is_array($decoded) ? $decoded : null;
    }

    public function set(Model $model, string $key, mixed $value, array $attributes): ?string
    {
        if ($value === null) {
            return null;
        }

        return json_encode($value, JSON_FORCE_OBJECT);
    }
}
