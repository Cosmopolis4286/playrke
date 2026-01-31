<?php

namespace App\Factories\Directory;

use App\DTOs\Directory\DirectoryDTO;
use InvalidArgumentException;

/**
 * Factory para crear DTOs de Directory desde datos de request.
 *
 * Responsabilidades:
 * - Transformar datos de request a DTO
 * - Validar integridad de datos
 * - Aplicar defaults
 *
 * @package App\Factories\Directory
 */
final class DirectoryFactory
{
    /**
     * Crea un DirectoryDTO a partir de un arreglo de datos (request).
     *
     * @param array<string, mixed> $data         Datos de entrada, típicamente request validated
     * @param string|null          $directoryId  ID del directorio (para updates)
     * @return DirectoryDTO DTO listo para ser procesado
     *
     * @throws InvalidArgumentException Si faltan campos obligatorios
     */
    public function fromRequest(array $data, ?string $directoryId = null): DirectoryDTO
    {
        // Validaciones de campos obligatorios (solo para creación)
        if ($directoryId === null) {
            if (empty($data['document_type_id'])) {
                throw new InvalidArgumentException('El campo "document_type_id" es obligatorio.');
            }

            if (empty($data['document'])) {
                throw new InvalidArgumentException('El campo "document" es obligatorio.');
            }

            if (empty($data['phone'])) {
                throw new InvalidArgumentException('El campo "phone" es obligatorio.');
            }

            if (empty($data['bank_id'])) {
                throw new InvalidArgumentException('El campo "bank_id" es obligatorio.');
            }
        }

        return new DirectoryDTO(
            id: $data['id'] ?? $directoryId,
            documentTypeId: $data['document_type_id'],
            document: $data['document'],
            phone: $data['phone'],
            bankId: $data['bank_id'],
            userId: $data['user_id'] ?? null,
            countryCode: $data['country_code'] ?? 'VE',
        );
    }
}
