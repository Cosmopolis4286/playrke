<?php

namespace App\Services;

use App\Models\User;
use App\Services\BaseService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class ProfileService extends BaseService
{
    public function __construct() {}

    /**
     * Actualiza la contraseña de un usuario de forma transaccional.
     *
     * Ejecuta el cambio de contraseña dentro de una transacción de base de datos
     * para garantizar consistencia. La contraseña se almacena siempre hasheada.
     *
     * @param int $userId ID del usuario al que se le actualizará la contraseña
     * @param string $newPassword Nueva contraseña en texto plano (se hashea internamente)
     *
     * @return void
     *
     * @throws \Throwable Si ocurre un error durante la transacción
     */
    public function changePassword(int $userId, string $newPassword): void
    {
        DB::transaction(function () use ($userId, $newPassword): void {
            User::whereKey($userId)->update([
                'password' => Hash::make($newPassword),
            ]);
        });
    }
}
