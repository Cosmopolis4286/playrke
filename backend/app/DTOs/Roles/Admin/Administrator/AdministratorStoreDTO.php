<?php

namespace App\DTOs\Roles\Admin\Administrator;

/**
 * Data Transfer Object para el alta de un nuevo administrador
 * (AdministratorController::store).
 */
final class AdministratorStoreDTO
{
    public function __construct(
        public readonly string $firstName,
        public readonly string $lastName,
        public readonly string $username,
        public readonly string $email,
        public readonly string $password,
        public readonly string $countryId,
        public readonly ?string $phoneNumber,
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            $data['first_name'],
            $data['last_name'],
            $data['username'],
            $data['email'],
            $data['password'],
            $data['country_id'],
            $data['phone_number'] ?? null,
        );
    }
}
