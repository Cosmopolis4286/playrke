<?php

namespace App\Factories\Directories\Fiat\Brazil;

use App\DTOs\Directories\Fiat\Brazil\PixKeyDTO;
use InvalidArgumentException;

final class PixKeyFactory
{
    public function fromRequest(array $data, ?string $pixKeyId = null): PixKeyDTO
    {
        if ($pixKeyId === null) {
            foreach (['pix_key_type', 'pix_key_value', 'bank_id'] as $field) {
                if (empty($data[$field])) {
                    throw new InvalidArgumentException("El campo \"{$field}\" es obligatorio.");
                }
            }
        }

        return new PixKeyDTO(
            id:          $data['id'] ?? $pixKeyId,
            pixKeyType:  $data['pix_key_type'],
            pixKeyValue: $data['pix_key_value'],
            bankId:      $data['bank_id'],
            name:        $data['name'] ?? null,
        );
    }
}
