<?php

namespace App\Models;

use Spatie\Permission\Models\Permission as SpatiePermission;

/**
 * Modelo Permission personalizado con soporte para IDs como strings.
 *
 * Extiende el modelo de Spatie para mantener compatibilidad con laravel-permission,
 * mientras se asegura que los IDs se serialicen como strings en JSON.
 */
class Permission extends SpatiePermission
{
    /**
     * El tipo de dato de la llave primaria.
     * 'string' asegura que los IDs se serialicen como strings en JSON,
     * previniendo pérdida de precisión en JavaScript con números grandes.
     */
    protected $keyType = 'string';

    /**
     * Indica si las llaves primarias son auto-incrementales.
     */
    public $incrementing = false;

    /**
     * Los atributos que deben ser convertidos.
     */
    protected $casts = [
        'id' => 'string',
    ];

    /**
     * Constructor para inicializar casts.
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        // Asegurar que el ID siempre se trate como string
        $this->mergeCasts([
            $this->getKeyName() => 'string',
        ]);
    }

    /**
     * Accessor para asegurar que el ID se retorne como string.
     */
    public function getIdAttribute($value): string
    {
        return (string) $value;
    }
}
