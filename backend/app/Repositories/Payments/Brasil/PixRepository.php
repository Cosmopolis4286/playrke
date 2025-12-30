<?php

namespace App\Repositories\Payments\Brasil;

use App\Models\Payments\Brasil\Pix;

class PixRepository
{
    /**
     * Obtener un Pix por ID.
     *
     * @param int $id
     * @param array $fields
     * @return Pix|null
     */
    public function getById(int $id, array $fields = ['*']): ?Pix
    {
        return Pix::select($fields)->where('id', $id)->first();
    }

    /**
     * Obtener un registro por valor de clave PIX.
     *
     * @param string $pixKeyValue
     * @return Pix|null
     */
    public function getByPixKeyValue(string $pixKeyValue): ?Pix
    {
        return Pix::where('pix_key_value', $pixKeyValue)->first();
    }

    /**
     * Verificar si existe una clave PIX.
     *
     * @param int $pixKey
     * @param string $pixKeyValue
     * @param int|null $userId
     * @return bool
     */
    public function existsPixKey(int $pixKey, string $pixKeyValue, ?int $userId = null): bool
    {
        return Pix::where('pix_key', $pixKey)
            ->where('pix_key_value', $pixKeyValue)
            ->when($userId, fn($query) => $query->where('user_id', '<>', $userId))
            ->exists();
    }
}
