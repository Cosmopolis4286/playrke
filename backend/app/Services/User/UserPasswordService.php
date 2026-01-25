<?php

namespace App\Http\Services\User;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

final class UserPasswordService
{

    /**
     * Resetear contraseña del usuario asociado.
     */
    public function resetPassword(
        Model $model,
        int $length = 12,
        string $userRelation = 'user'
    ): string {
        $newPassword = Str::random($length);

        $model->loadMissing($userRelation);

        if (!$model->$userRelation instanceof Model) {
            throw new RuntimeException('Relación de usuario no encontrada');
        }

        $model->$userRelation->update([
            'password' => Hash::make($newPassword),
        ]);

        return $newPassword;
    }
}
