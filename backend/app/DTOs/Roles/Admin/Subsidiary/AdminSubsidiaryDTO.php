<?php

namespace App\DTOs\Roles\Admin\Subsidiary;

/**
 * Data Transfer Object para Sucursal (Subsidiary).
 *
 * - Inmutable
 * - Tipado
 * - Compatible con create y update
 * - Sin lógica de negocio
 */
final class AdminSubsidiaryDTO
{
    public function __construct(
        public readonly ?string $username,
        public readonly ?string $email,
        public readonly ?string $name,
        public readonly ?string $commerce,
        public readonly ?string $country,
        public readonly ?string $currency,
        public readonly ?string $phone,
        public readonly ?string $commission,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            $data['username'] ?? null,
            $data['email'] ?? null,
            $data['name'] ?? null,
            $data['commerce'] ?? null,
            $data['country'] ?? null,
            $data['currency'] ?? null,
            $data['phone'] ?? null,
            $data['commission'] ?? null,
        );
    }

    public function toArray(): array
    {
        return array_filter([
            'username'    => $this->username,
            'email'       => $this->email,
            'name'        => $this->name,
            'commerce'    => $this->commerce,
            'country'     => $this->country,
            'currency'    => $this->currency,
            'phone'       => $this->phone,
            'commission'  => $this->commission,
        ], static function ($value) {
            if ($value === null) {
                return false;
            }

            if (is_string($value)) {
                return $value !== '';
            }

            return true;
        });
    }
}
