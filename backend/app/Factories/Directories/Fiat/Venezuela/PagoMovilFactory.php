<?php

namespace App\Factories\Directories\Fiat\Venezuela;

use App\DTOs\Directories\Fiat\Venezuela\PagoMovilDTO;
use InvalidArgumentException;

/**
 * Factory para crear DTOs de un Directorio de Pago Móvil (Venezuela).
 *
 * Responsabilidades:
 * - Transformar datos de request a DTO
 * - Validar integridad de datos
 * - Aplicar defaults
 *
 * @package App\Factories\Directories\Fiat\Venezuela
 */
final class PagoMovilFactory
{
    /**
     * Crea un PagoMovilDTO a partir de un arreglo de datos (request).
     *
     * @param array<string, mixed> $data         Datos de entrada, típicamente request validated
     * @param string|null          $directoryId  ID del directorio (para updates)
     * @return PagoMovilDTO DTO listo para ser procesado
     *
     * @throws InvalidArgumentException Si faltan campos obligatorios
     */
    public function fromRequest(array $data, ?string $directoryId = null): PagoMovilDTO
    {
        // Validaciones de campos obligatorios (solo para creación)
        if ($directoryId === null) {
            if (empty($data['holder_name'])) {
                throw new InvalidArgumentException('El campo "holder_name" es obligatorio.');
            }

            if (empty($data['document_type_id'])) {
                throw new InvalidArgumentException('El campo "document_type_id" es obligatorio.');
            }

            if (empty($data['document_number'])) {
                throw new InvalidArgumentException('El campo "document_number" es obligatorio.');
            }

            if (empty($data['phone'])) {
                throw new InvalidArgumentException('El campo "phone" es obligatorio.');
            }

            if (empty($data['bank_id'])) {
                throw new InvalidArgumentException('El campo "bank_id" es obligatorio.');
            }
        }

        return new PagoMovilDTO(
            id: $data['id'] ?? $directoryId,
            holderName: $data['holder_name'],
            documentTypeId: $data['document_type_id'],
            documentNumber: $data['document_number'],
            phone: $data['phone'],
            bankId: $data['bank_id']
        );
    }
}
