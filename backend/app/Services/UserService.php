<?php

namespace App\Services;

use App\Contracts\UserRepositoryInterface;
use App\Models\Shared\UniqueCode;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class UserService
{
    /**
     * Contructor
     */
    public function __construct(
        private UserRepositoryInterface $repository,
    ) {
    }

    /**
     * Crear código único asociado a un usuario.
     */
    public function createUniqueCode(string $userId) : UniqueCode
    {
        return UniqueCode::create([
            'uuid_code' => (string) Str::uuid(),
            'rke_code' => $this->generateUniqueRkeCode(),
            'user_id' => $userId,
        ]);
    }

    /* =====================================================
     | Creación de usuarios y códigos
     |=====================================================*/

    /**
     * Crear usuario a partir de un DTO.
     */
    public function createUserFromDTO(object $dto, string $plainPassword) : User
    {
        $user = $this->repository->create([
            'username' => $dto->username,
            'email' => $dto->email,
            'password' => Hash::make($plainPassword),
            'country_id' => $dto->country,
            'time_zone_id' => $dto->timeZone,
            'email_verified_at' => now(),
            'referral_code' => $this->generateUniqueReferralCode(),
            'status' => 'ACTIVE',
        ]);

        return $user;
    }

    /**
     * Generar código de referido único.
     */
    private function generateUniqueReferralCode() : string
    {
        do {
            $code = strtoupper(Str::random(10));
        } while ($this->repository->existsByField('referral_code', $code));

        return $code;
    }

    /**
     * Generar código RKE único.
     */
    private function generateUniqueRkeCode() : string
    {
        do {
            $code = 'RKE' . strtoupper(Str::random(8));
        } while (UniqueCode::existsRKECode($code));

        return $code;
    }
}
