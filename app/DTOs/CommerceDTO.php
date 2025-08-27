<?php

namespace App\DTOs;

/**
 * Data Transfer Object para transportar la información de una Comercializadora (Commerce).
 *
 * ✅ Ventajas de esta versión:
 * - Más dinámica: recibe un array y lo asigna automáticamente.
 * - Evita modificar el constructor cada vez que agregues un nuevo campo.
 * - Mantiene typing y documentación clara.
 *
 * Ejemplo de uso:
 * $dto = CommerceDTO::fromArray($request->validated());
 * $array = $dto->toArray();
 */
class CommerceDTO
{
    public readonly string $username;
    public readonly string $email;
    public readonly string $country;
    public readonly string $timeZone;
    public readonly string $coin;
    public readonly string $phone;
    public readonly string $name;
    public readonly string $codeRke;
    public readonly int $userType;
    public readonly ?array $allowedGames;
    public readonly ?array $otherCoins;

    /**
     * Constructor privado: se recomienda instanciar usando fromArray().
     *
     * @param array $data Datos validados
     */
    private function __construct(array $data)
    {
        $this->username     = $data['username'];
        $this->email        = $data['email'];
        $this->country      = $data['country'];
        $this->timeZone     = $data['time_zone'];
        $this->coin         = $data['coin'];
        $this->phone        = $data['phone'];
        $this->name         = $data['name'];
        $this->codeRke      = $data['code_rke'];
        $this->userType     = (int) $data['user_type'];
        $this->allowedGames = $data['allowed_games'] ?? null;
        $this->otherCoins   = $data['other_coins'] ?? null;
    }

    /**
     * Crear una instancia de DTO desde un array validado.
     *
     * @param array<string,mixed> $data
     * @return static
     */
    public static function fromArray(array $data): self
    {
        return new self($data);
    }

    /**
     * Convertir DTO a array asociativo.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'username'      => $this->username,
            'email'         => $this->email,
            'country'       => $this->country,
            'time_zone'     => $this->timeZone,
            'coin'          => $this->coin,
            'phone'         => $this->phone,
            'name'          => $this->name,
            'code_rke'      => $this->codeRke,
            'user_type'     => $this->userType,
            'allowed_games' => $this->allowedGames,
            'other_coins'   => $this->otherCoins,
        ];
    }
}
