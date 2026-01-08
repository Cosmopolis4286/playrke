<?php

namespace App\DTOs\User\Roles;

/**
 * Data Transfer Object para Commerce.
 *
 * - Inmutable
 * - Tipado
 * - Compatible con create y update
 * - Sin lógica innecesaria
 */
final class CommerceDTO
{
    public function __construct(
        public readonly ?string $username,
        public readonly ?string $email,
        public readonly ?string $country,
        public readonly ?string $timeZone,
        public readonly ?string $currency,
        public readonly ?string $phone,
        public readonly ?string $name,
        /** @var array<int,string>|null */
        public readonly ?array $otherCurrencies,
        /** @var array<int,string>|null */
        public readonly ?array $allowedGames,
    ) {}

    /**
     * Crear DTO desde array validado.
     */
    public static function fromArray(array $data): self
    {
        return new self(
            $data['username'] ?? null,
            $data['email'] ?? null,
            $data['country'] ?? null,
            $data['time_zone'] ?? null,
            $data['currency'] ?? null,
            $data['phone'] ?? null,
            $data['name'] ?? null,
            $data['other_currencies'] ?? null,
            $data['allowed_games'] ?? null,
        );
    }

    /**
     * Exportar a array listo para persistencia.
     *
     * (Solo valores no nulos)
     */
    public function toArray(): array
    {
        return array_filter([
            'username'         => $this->username,
            'email'            => $this->email,
            'country'          => $this->country,
            'time_zone'        => $this->timeZone,
            'currency'         => $this->currency,
            'phone'            => $this->phone,
            'name'             => $this->name,
            'other_currencies' => $this->otherCurrencies,
            'allowed_games'    => $this->allowedGames,
        ], static fn($value) => $value !== null);
    }
}
