<?php

namespace App\Services\Roles\Admin;

use App\Traits\HashedTableDataTrait;

/**
 * Servicio de dominio para gestión del administrador.
 *
 * Orquesta la obtención y preparación del contexto para vistas,
 * incluyendo transformación y normalización de datos para tablas.
 *
 * NO maneja lógica HTTP ni infraestructura, solo lógica de negocio.
 */
class AdminService
{
    use HashedTableDataTrait;

    public function __construct(protected AdminContextService $context) {}

    /**
     * Obtiene el contexto preparado para la visualización según rol.
     *
     * Para ciertos roles, aplica transformación de tablas con
     * IDs hasheados, columnas y keys normalizadas para frontend.
     *
     * @param string $rolePrefix Identificador del contexto (ej: 'commerces').
     * @return array Contexto listo para pasar a la vista Inertia.
     */
    public function context(string $rolePrefix): array
    {
        // Obtiene contexto base (DTO convertido a array para vista)
        $context = $this->context->resolve($rolePrefix)->toView();

        if ($rolePrefix === 'commerces') {
            // Define columnas visibles y keys fuente para la tabla
            $columns = ['Nombre', 'País', 'Teléfono', 'Email', 'Status'];
            $rowKeys = ['name', 'Country.name', 'phone', 'User.email', 'status_label'];

            // Aplica transformación de contexto para tablas Inertia
            $context = $this->processContextTableData($context, $rolePrefix, $columns, $rowKeys);
        }

        // Aquí podrías manejar otros roles con lógica similar

        return $context;
    }
}
