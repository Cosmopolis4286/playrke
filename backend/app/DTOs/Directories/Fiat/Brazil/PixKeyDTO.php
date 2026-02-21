<?php

namespace App\DTOs\Directories\Fiat\Brazil;

final class PixKeyDTO
{
    public function __construct(
        public readonly ?string $id,
        public readonly string  $pixKeyType,
        public readonly string  $pixKeyValue,
        public readonly string  $bankId,
        public readonly ?string $name    = null,
        public readonly ?string $tokenId = null,
    ) {}

    public function toArray(): array
    {
        return [
            'pix_key_type'  => $this->pixKeyType,
            'pix_key_value' => $this->pixKeyValue,
            'bank_id'       => $this->bankId,
            'name'          => $this->name,
        ];
    }

    public function isUpdate(): bool
    {
        return $this->id !== null;
    }
}
