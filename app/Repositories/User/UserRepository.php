<?php

namespace App\Repositories\User;

use App\Models\Users\User;
use Illuminate\Support\Str;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repositorio para manejar operaciones de consulta relacionadas con usuarios.
 *
 * Este repositorio centraliza todas las queries, manteniendo el modelo limpio.
 */
class UserRepository
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
            $code = Str::random(16);
        } while (User::where('referral_code', $code)->exists());

        return $code;
    }

    /**
     * Obtener todos los roles de un usuario.
     *
     * @param int $userId
     * @return Collection<int, Role>
     */
    public static function getRoles(int $userId): Collection
    {
        $user = User::find($userId);
        return $user ? $user->roles : collect();
    }

    /**
     * Verificar si un usuario tiene un rol específico por prefijo.
     *
     * @param int $userId
     * @param string $rolePrefix
     * @return bool
     */
    public static function hasRole(int $userId, string $rolePrefix): bool
    {
        $user = User::find($userId);
        return $user ? $user->roles->contains(fn($role) => $role->prefix === $rolePrefix) : false;
    }

    /**
     * Obtener las entidades asociadas a un usuario según sus roles (commerce, seller, etc.).
     *
     * @param int $userId
     * @return array<string, mixed>
     */
    public static function getEntities(int $userId): array
    {
        $user = User::with(['roles', 'commerce_id', 'seller'])->find($userId);
        if (!$user) return [];

        $entities = [];
        foreach ($user->roles as $role) {
            $entities[$role->prefix] = match ($role->prefix) {
                'commerce_id' => $user->commerce,
                'seller'   => $user->seller,
                default    => null,
            };
        }

        return $entities;
    }

    /**
     * Obtener el "creator" asociado a un usuario según un rol específico.
     *
     * @param int $userId
     * @param string|null $rolePrefix Prefijo del rol
     * @param bool $returnFull Retornar array con más detalles
     * @return int|array|null
     */
    public static function getCreatorUser(int $userId, string|null $rolePrefix = null, bool $returnFull = false): mixed
    {
        $entities = self::getEntities($userId);

        if ($rolePrefix && isset($entities[$rolePrefix])) {
            $entity = $entities[$rolePrefix];
        } else {
            $entity = count($entities) > 0 ? reset($entities) : null;
            $rolePrefix = $rolePrefix ?? key($entities);
        }

        if (!$entity) return null;

        $creatorId = $entity->id;

        return $returnFull
            ? ['type' => $rolePrefix, 'id' => $creatorId, 'userId' => $userId]
            : $creatorId;
    }
}
