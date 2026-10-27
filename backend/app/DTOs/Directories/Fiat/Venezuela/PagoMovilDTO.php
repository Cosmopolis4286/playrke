<?php

namespace App\DTOs\Directories\Fiat\Venezuela;

/**
 * Data Transfer Object para directorios de Pago Móvil (Venezuela).
 *
 * Transporta los datos necesarios para crear o actualizar
 * registros de directorios de pago móvil (Venezuela).
 */
final class PagoMovilDTO
{
    /**
     * Constructor del DTO de Directorio.
     *
     * @param string|null $id             ID del registro (null para creación)
     * @param string      $holderName     Nombre del titular de la cuenta
     * @param string      $documentTypeId ID del tipo de documento (V, E, J)
     * @param string      $documentNumber Número de documento/cédula
     * @param string      $phone          Teléfono del pago móvil
     * @param string      $bankId         ID del banco venezolano
     * @param string|null $tokenId        token de usuario conectado por API (opcional)
     */
    public function __construct(
        public readonly ?string $id,
        public readonly string $holderName,
        public readonly string $documentTypeId,
        public readonly string $documentNumber,
        public readonly string $phone,
        public readonly string $bankId,
        public readonly ?string $tokenId = null,
    ) {
    }

    /**
     * Indica si es una actualización (tiene ID).
     */
    public function isUpdate() : bool
    {
        return $this->id !== null;
    }

    /**
     * Convierte el DTO a un array para operaciones de base de datos.
     *
     * @return array<string, mixed>
     */
    public function toArray() : array
    {
        return [
            'holder_name' => $this->holderName,
            'document_type_id' => $this->documentTypeId,
            'document_number' => $this->documentNumber,
            'phone' => $this->phone,
            'bank_id' => $this->bankId,
        ];
    }
}
