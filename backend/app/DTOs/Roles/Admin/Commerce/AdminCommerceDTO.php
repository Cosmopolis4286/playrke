<?php

namespace App\DTOs\Roles\Admin\Commerce;

/**
 * Data Transfer Object para Commerce.
 *
 * - Inmutable
 * - Tipado
 * - Compatible con create y update
 * - Sin lógica de negocio
 */
final class AdminCommerceDTO
{
    /**
     * @param array<int,string>|null $otherCurrencies
     * @param array<int,string>|null $allowedGames
     */
    public function __construct(
        public readonly ?string $username,
        public readonly ?string $email,
        public readonly ?string $country,
        public readonly ?string $timeZone,
        public readonly ?string $currency,
        public readonly ?string $phone,
        public readonly ?string $name,
        public readonly ?array $otherCurrencies,
        public readonly ?array $allowedGames,
    ) {}

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
            isset($data['other_currencies'])
                ? array_values((array) $data['other_currencies'])
                : null,
            isset($data['allowed_games'])
                ? array_values((array) $data['allowed_games'])
                : null,
        );
    }

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
        ], static function ($value) {
            if ($value === null) {
                return false;
            }

            if (is_string($value)) {
                return $value !== '';
            }

            if (is_array($value)) {
                return $value !== [];
            }

            return true;
        });
    }
}
