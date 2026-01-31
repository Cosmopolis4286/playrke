<?php

namespace App\DTOs\Directory;

/**
 * Data Transfer Object para directorios de pago.
 *
 * Transporta los datos necesarios para crear o actualizar
 * registros de directorios de pago móvil (Venezuela).
 *
 * @package App\DTOs\Directory
 */
final class DirectoryDTO
{
    /**
     * Constructor del DTO de Directorio.
     *
     * @param string|null $id                ID del registro (null para creación)
     * @param string      $documentTypeId    ID del tipo de documento (V, E, J)
     * @param string      $document          Número de documento/cédula
     * @param string      $phone             Teléfono del pago móvil
     * @param string      $bankId            ID del banco venezolano
     * @param string|null $userId            ID del usuario propietario
     * @param string|null $countryCode       Código ISO del país (ej: VE)
     */
    public function __construct(
        public readonly ?string $id,
        public readonly string $documentTypeId,
        public readonly string $document,
        public readonly string $phone,
        public readonly string $bankId,
        public readonly ?string $userId = null,
        public readonly ?string $countryCode = 'VE',
    ) {}

    /**
     * Convierte el DTO a un array para operaciones de base de datos.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'document_type_id' => $this->documentTypeId,
            'document'         => $this->document,
            'phone'           => $this->phone,
            'bank_id'         => $this->bankId,
            'user_id'         => $this->userId,
        ];
    }

    /**
     * Indica si es una actualización (tiene ID).
     *
     * @return bool
     */
    public function isUpdate(): bool
    {
        return $this->id !== null;
    }
}
