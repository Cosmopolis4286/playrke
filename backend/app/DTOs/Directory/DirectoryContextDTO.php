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
     * @param array|object $currencies     Lista u objeto con monedas disponibles.
     * @param mixed        $exchangeRates  Datos de las tasas de cambio.
     * @param string       $roleUser       Rol del usuario (ej. 'commerce', 'customer').
     * @param string|null  $tokenId        Token de usuario conectado por API (opcional).
     * @param mixed        $banks          Lista de bancos venezolanos.
     * @param mixed        $documentTypes  Lista de tipos de documento.
     * @param mixed        $directories    Lista de directorios del usuario.
     */
    public function __construct(
        public readonly array|object $currencies,
        public readonly mixed $exchangeRates,
        public readonly string $roleUser,
        public readonly ?string $tokenId = null,
        public readonly mixed $banks = [],
        public readonly mixed $documentTypes = [],
        public readonly mixed $directories = [],
    ) {}

    /**
     * Convierte el DTO en un array para la vista.
     *
     * @return array<string, mixed>
     */
    public function toView(): array
    {
        return [
            'currencies'    => $this->currencies,
            'exchangeRates' => $this->exchangeRates,
            'roleUser'      => $this->roleUser,
            'tokenId'       => $this->tokenId,
            'banks'         => $this->banks,
            'documentTypes' => $this->documentTypes,
            'directories'   => $this->directories,
        ];
    }
}
