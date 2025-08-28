<?php

namespace App\Repositories;

use App\Models\User;
use App\Models\UserType;
use Illuminate\Support\Str;

/**
 * Repositorio para manejar operaciones de consulta relacionadas con usuarios.
 *
 * Este repositorio centraliza todas las queries, manteniendo el modelo limpio.
 */
class CommerceRepository
{
    /**
     * Obtener un usuario por ID.
     *
     * @param int $userId
     * @param array<int,string> $fields
     * @return User|null
     */
    public static function getById(int $userId, array $fields = ['*']): ?User
    {
        return User::select($fields)->where('id', $userId)->first();
    }

    /**
     * Obtener un valor específico de un usuario por ID.
     *
     * @param int $id
     * @param string $column
     * @return mixed
     */
    public static function getValueById(int $id, string $column): mixed
    {
        return User::where('id', $id)->value($column);
    }

    /**
     * Obtener un valor específico de un usuario por email.
     *
     * @param string $email
     * @param string $column
     * @return mixed
     */
    public static function getValueByEmail(string $email, string $column): mixed
    {
        return User::where('email', $email)->value($column);
    }

    /**
     * Generar un código de referido único.
     *
     * @return string
     */
    public static function generateUniqueReferralCode(): string
    {
        do {
            $code = Str::random(12);
        } while (User::where('referral_code', $code)->exists());

        return $code;
    }

    /**
     * Obtener el prefijo del tipo de usuario de un usuario.
     *
     * @param int $userId
     * @return string|null
     */
    public static function getUserTypePrefix(int $userId): ?string
    {
        $userTypeId = self::getValueById($userId, 'user_type');
        return $userTypeId ? UserType::where('id', $userTypeId)->value('prefix') : null;
    }

    /**
     * Obtener el "creator" asociado a un usuario.
     *
     * @param int $userId
     * @param string|bool|null $userType
     * @param bool $returnFull
     * @return int|array|null
     */
    public static function getCreatorUser(int $userId, $userType = false, bool $returnFull = false): mixed
    {
        if (!$userType) {
            $userType = self::getUserTypePrefix($userId);
        }

        $prefix = UserType::where('id', $userType)->value('prefix');
        $modelName = ucfirst(is_string($userType) ? $userType : $prefix);

        $creatorId = app("App\\Models\\$modelName")::where('user', $userId)->value('id');

        return $returnFull
            ? ['type' => $userType, 'id' => $creatorId, 'userId' => $userId]
            : $creatorId;
    }
}
