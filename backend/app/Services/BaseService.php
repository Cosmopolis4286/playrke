<?php

namespace App\Services;

use App\Models\Users\User;
use App\Models\Users\Shared\UniqueCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use InvalidArgumentException;

abstract class BaseService
{
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
        $validStatuses = ['ACTIVE', 'INACTIVE', 'BLOCKED', 'DELETED'];

        if (!in_array($status, $validStatuses, true)) {
            throw new InvalidArgumentException("Estado inválido: {$status}");
        }
    }

    /**
     * Crear usuario a partir de DTO.
     *
     * @param object $dto Objeto con datos necesarios (debe tener username, email, country, timeZone)
     * @param string $plainPassword
     * @return User
     */
    protected function createUserFromDTO(object $dto, string $plainPassword): User
    {
        // Aquí se asume que existe un repositorio o método para crear usuarios,
        // en este ejemplo se usa el modelo User directamente para simplificar.

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
     * Crear código único asociado al usuario.
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
     * Puedes personalizar la lógica según tu necesidad.
     *
     * @return string
     */
    protected function generateUniqueReferralCode(): string
    {
        // Ejemplo simple para generar código único alfanumérico
        do {
            $code = strtoupper(Str::random(10));
        } while (User::where('referral_code', $code)->exists());

        return $code;
    }


    /**
     * Cambiar estado del modelo y su usuario asociado.
     *
     * @param object $model Modelo que debe tener atributo status y relación user()
     * @param string|null $newStatus Si es null alterna entre ACTIVE/INACTIVE
     * @param string $userRelation Nombre de la relación con usuario, default 'user'
     * @return void
     */
    public function changeStatus(object $model, ?string $newStatus = null, string $userRelation = 'user'): void
    {
        $currentStatus = $model->status;
        $status = $newStatus ?? ($currentStatus === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE');

        $this->assertValidStatus($status);

        DB::transaction(function () use ($model, $status, $userRelation) {
            $model->loadMissing($userRelation);

            $model->update(['status' => $status]);

            if ($model->$userRelation) {
                $model->$userRelation->update(['status' => $status]);
            }
        });
    }

    /**
     * Resetear contraseña del usuario asociado al modelo.
     *
     * @param object $model Modelo que debe tener relación user()
     * @param int $length Longitud de la nueva contraseña
     * @param string $userRelation Nombre de la relación con usuario, default 'user'
     * @return string Nueva contraseña en texto plano
     */
    public function resetPassword(object $model, int $length = 12, string $userRelation = 'user'): string
    {
        $newPassword = Str::random($length);

        $model->loadMissing($userRelation);

        if ($model->$userRelation) {
            $model->$userRelation->update([
                'password' => Hash::make($newPassword),
            ]);
        }

        return $newPassword;
    }
}
