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
     * @param mixed        $banks          Lista de bancos (venezolanos o brasileños según país).
     * @param mixed        $directories    Lista de directorios del usuario.
     * @param mixed        $documentTypes  Lista de tipos de documento.
     * @param string|null  $tokenId        Token de usuario conectado por API (opcional).
     * @param string       $roleUser       Rol del usuario (ej. 'commerce', 'customer').
     * @param mixed        $currencies     Monedas disponibles con información de país.
     */
    public function __construct(
        public readonly mixed $banks = [],
        public readonly mixed $directories = [],
        public readonly mixed $documentTypes = [],
        public readonly string $roleUser,
        public readonly ?string $tokenId = null,
        public readonly mixed $currencies = [],
    ) {}

    /**
     * Convierte el DTO en un array para la vista.
     *
     * @return array<string, mixed>
     */
    public function toView(): array
    {
        return [
            'banks'         => $this->banks,
            'directories'   => $this->directories,
            'documentTypes' => $this->documentTypes,
            'roleUser'      => $this->roleUser,
            'tokenId'       => $this->tokenId,
            'currencies'    => $this->currencies,
        ];
    }
}
