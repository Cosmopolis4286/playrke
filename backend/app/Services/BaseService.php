<?php

namespace App\Services;

use App\Models\Users\User;
use App\Models\Users\Shared\UniqueCode;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

abstract class BaseService
{
    /* =====================================================
     | Validaciones
     |=====================================================*/

    /**
     * Estados válidos del sistema.
     */
    protected const VALID_STATUSES = [
        'ACTIVE',
        'INACTIVE',
        'BLOCKED',
        'DELETED',
    ];

    /**
     * Validar estados permitidos.
     *
     * @param string $status
     * @return void
     *
     * @throws InvalidArgumentException
     */
    protected function assertValidStatus(string $status): void
    {
        if (!in_array($status, self::VALID_STATUSES, true)) {
            throw new InvalidArgumentException("Estado inválido: {$status}");
        }
    }

    /* =====================================================
     | Creación de usuarios y códigos
     |=====================================================*/

    /**
     * Crear usuario a partir de DTO.
     *
     * @param object $dto Debe contener username, email, country, timeZone
     * @param string $plainPassword
     * @return User
     */
    protected function createUserFromDTO(object $dto, string $plainPassword): User
    {
        return User::create([
            'username'          => $dto->username,
            'email'             => $dto->email,
            'password'          => Hash::make($plainPassword),
            'country_id'        => $dto->country,
            'time_zone_id'      => $dto->timeZone,
            'email_verified_at' => now(),
            'referral_code'     => $this->generateUniqueReferralCode(),
            'status'            => 'ACTIVE',
        ]);
    }

    /**
     * Crear código único asociado a un usuario.
     *
     * @param string $userId
     * @return UniqueCode
     */
    protected function createUniqueCode(string $userId): UniqueCode
    {
        return UniqueCode::create([
            'uuid_code' => (string) Str::uuid(),
            'rke_code'  => $this->generateUniqueRkeCode(),
            'user_id'   => $userId,
        ]);
    }

    /**
     * Generar RKE único.
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
        } while (User::where('referral_code', $code)->exists());

        return $code;
    }

    /* =====================================================
     | Acciones comunes sobre modelos
     |=====================================================*/

    /**
     * Cambiar estado de un modelo y su usuario asociado.
     *
     * @param Model $model Modelo con atributo status
     * @param string|null $newStatus Si es null alterna ACTIVE/INACTIVE
     * @param string $userRelation Relación BelongsTo con User
     * @return void
     */
    protected function changeStatus(
        Model $model,
        ?string $newStatus = null,
        string $userRelation = 'user'
    ): void {
        $status = $newStatus ?? ($model->status === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE');

        $this->assertValidStatus($status);

        DB::transaction(function () use ($model, $status, $userRelation) {
            $model->update(['status' => $status]);

            if ($model->relationLoaded($userRelation) === false) {
                $model->load($userRelation);
            }

            $relation = $model->$userRelation;

            if ($relation instanceof Model) {
                $relation->update(['status' => $status]);
            }
        });
    }

    /**
     * Resetear contraseña del usuario asociado.
     *
     * @param Model $model Modelo con relación user()
     * @param int $length Longitud de la contraseña
     * @param string $userRelation Relación BelongsTo con User
     * @return string Contraseña en texto plano
     */
    protected function resetPassword(
        Model $model,
        int $length = 12,
        string $userRelation = 'user'
    ): string {
        $newPassword = Str::random($length);

        if ($model->relationLoaded($userRelation) === false) {
            $model->load($userRelation);
        }

        $relation = $model->$userRelation;

        if (!$relation instanceof Model) {
            throw new RuntimeException('Relación de usuario no encontrada');
        }

        $relation->update([
            'password' => Hash::make($newPassword),
        ]);

        return $newPassword;
    }
}
