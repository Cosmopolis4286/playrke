<?php

namespace App\DTOs\Directory;

use App\DTOs\ContextDTO;

/**
 * Data Transfer Object para el contexto del directorio.
 *
 * Transporta información necesaria para la vista o capa superior.
 */
final class DirectoryContextDTO implements ContextDTO
{
    /**
     * @param string       $roleUser        Rol del usuario (ej. 'commerce', 'customer').
     * @param mixed        $banks           Lista de bancos (venezolanos o brasileños según país).
     * @param mixed        $directories     Lista de directorios del usuario.
     * @param mixed        $documentTypes   Lista de tipos de documento.
     * @param string|null  $tokenId         Token de usuario conectado por API (opcional).
     * @param mixed        $currencies      Monedas disponibles con información de país.
     * @param bool         $isImpersonation True si un admin/superadmin está viendo el directorio de otro usuario.
     * @param array|null   $viewingUser     {id, username, email} del usuario dueño del directorio cuando isImpersonation=true — permite a la UI mostrar "Viendo el directorio de X".
     */
    public function __construct(
        public readonly string $roleUser,
        public readonly mixed $banks = [],
        public readonly mixed $directories = [],
        public readonly mixed $documentTypes = [],
        public readonly ?string $tokenId = null,
        public readonly mixed $currencies = [],
        public readonly bool $isImpersonation = false,
        public readonly ?array $viewingUser = null,
    ) {}

    /**
     * Convierte el DTO en un array para la vista.
     *
     * @return array<string, mixed>
     */
    public function toView(): array
    {
        return [
            'banks'           => $this->banks,
            'directories'     => $this->directories,
            'documentTypes'   => $this->documentTypes,
            'roleUser'        => $this->roleUser,
            'tokenId'         => $this->tokenId,
            'currencies'      => $this->currencies,
            'isImpersonation' => $this->isImpersonation,
            'viewingUser'     => $this->viewingUser,
        ];
    }
}
