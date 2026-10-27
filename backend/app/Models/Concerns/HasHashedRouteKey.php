<?php

namespace App\Models\Concerns;

use Vinkla\Hashids\Facades\Hashids;

/**
 * Trait HasHashedRouteKey
 *
 * Usa Hashids como Route Key y cachea los hashes
 * a nivel global por request.
 */
trait HasHashedRouteKey
{
    /**
     * Cache global por request:
     * [model_class => [id => hash]]
     *
     * @var array<class-string, array<string, string>>
     */
    protected static array $hashedRouteKeyCache = [];

    /**
     * Accessor del ID hasheado con cache global por request.
     */
    public function getHashedIdAttribute() : string
    {
        $class = static::class;
        $key = (string) $this->getKey();

        if (isset(self::$hashedRouteKeyCache[$class][$key])) {
            return self::$hashedRouteKeyCache[$class][$key];
        }

        return self::$hashedRouteKeyCache[$class][$key] =
            Hashids::connection('main')->encode($key);
    }

    /**
     * Valor usado en URLs.
     */
    public function getRouteKey() : string
    {
        return $this->getHashedIdAttribute();
    }

    /**
     * Nombre de la clave primaria.
     */
    public function getRouteKeyName() : string
    {
        return 'id';
    }

    /**
     * Route Model Binding usando Hashids.
     */
    public function resolveRouteBinding($value, $field = null)
    {
        $decoded = Hashids::connection('main')->decode((string) $value);

        if (empty($decoded)) {
            return null;
        }

        return static::query()
            ->whereKey($decoded[0])
            ->first();
    }
}
