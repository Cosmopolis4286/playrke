<?php

namespace App\DTOs\Roles\Admin\Player;

/**
 * Data Transfer Object para Player (rol por defecto).
 *
 * - Inmutable
 * - Tipado
 * - Compatible con create y update
 * - Sin lógica de negocio
 */
final class AdminPlayerDTO
{
    public static function fromArray(array $data) : self
    {
        return new self(
            $data['username'] ?? null,
            $data['email'] ?? null,
            $data['first_name'] ?? null,
            $data['last_name'] ?? null,
            $data['phone'] ?? null,
            $data['country'] ?? null,
            $data['time_zone'] ?? null,
        );
    }

    public function __construct(
        public readonly ?string $username,
        public readonly ?string $email,
        public readonly ?string $firstName,
        public readonly ?string $lastName,
        public readonly ?string $phone,
        public readonly ?string $country,
        public readonly ?string $timeZone,
    ) {
    }

    public function toArray() : array
    {
        return array_filter([
            'username' => $this->username,
            'email' => $this->email,
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'phone' => $this->phone,
            'country' => $this->country,
            'time_zone' => $this->timeZone,
        ], static fn ($v) => $v !== null && $v !== '');
    }
}
