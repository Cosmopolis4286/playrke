<?php

namespace App\DTOs\Roles\Admin\Administrator;

/**
 * Data Transfer Object para actualizar datos básicos de un administrador
 * existente (AdministratorController::update). No incluye contraseña: el
 * reseteo se gestiona por separado vía AdministratorService::resetPassword().
 */
final class AdministratorUpdateDTO
{
    public static function fromArray(array $data) : self
    {
        return new self(
            $data['first_name'] ?? null,
            $data['last_name'] ?? null,
            $data['username'] ?? null,
            $data['email'] ?? null,
            $data['phone_number'] ?? null,
            $data['country_id'] ?? null,
        );
    }

    public function __construct(
        public readonly ?string $firstName,
        public readonly ?string $lastName,
        public readonly ?string $username,
        public readonly ?string $email,
        public readonly ?string $phoneNumber,
        public readonly ?string $countryId,
    ) {
    }

    public function toArray() : array
    {
        return array_filter([
            'first_name' => $this->firstName,
            'last_name' => $this->lastName,
            'username' => $this->username,
            'email' => $this->email,
            'phone_number' => $this->phoneNumber,
            'country_id' => $this->countryId,
        ], static fn ($value) => $value !== null && $value !== '');
    }
}
