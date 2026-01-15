<?php

namespace App\Services\Users;

use App\Models\User;
use App\Models\Shared\UniqueCode;
use App\Repositories\Users\Contracts\UserRepositoryContract;
use App\Services\BaseService;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class UserService extends BaseService
{
    /**
     * @param UserRepositoryContract $repository
     */
    public function __construct(UserRepositoryContract $repository)
    {
        parent::__construct($repository);
    }

    /* =====================================================
     | Creación de usuarios y códigos
     |=====================================================*/

    /**
     * Crear usuario a partir de un DTO.
     *
     * @param object $dto
     * @param string $plainPassword
     * @return User
     */
    public function createUserFromDTO(object $dto, string $plainPassword): User
    {
        /** @var User $user */
        $user = $this->repository->create([
            'username'          => $dto->username,
            'email'             => $dto->email,
            'password'          => Hash::make($plainPassword),
            'country_id'        => $dto->country,
            'time_zone_id'      => $dto->timeZone,
            'email_verified_at' => now(),
            'referral_code'     => $this->generateUniqueReferralCode(),
            'status'            => 'ACTIVE',
        ]);

        return $user;
    }

    /**
     * Crear código único asociado a un usuario.
     *
     * @param string $userId
     * @return UniqueCode
     */
    public function createUniqueCode(string $userId): UniqueCode
    {
        return UniqueCode::create([
            'uuid_code' => (string) Str::uuid(),
            'rke_code'  => $this->generateUniqueRkeCode(),
            'user_id'   => $userId,
        ]);
    }

    /**
     * Generar código RKE único.
     *
     * @return string
     */
    protected function generateUniqueRkeCode(): string
    {
        do {
            $code = 'RKE' . strtoupper(Str::random(8));
        } while (UniqueCode::existsRKECode($code));

        return $code;
    }

    /**
     * Generar código de referido único.
     *
     * @return string
     */
    protected function generateUniqueReferralCode(): string
    {
        do {
            $code = strtoupper(Str::random(10));
        } while ($this->repository->existsByField('referral_code', $code));

        return $code;
    }
}
