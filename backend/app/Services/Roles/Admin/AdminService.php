<?php

namespace App\Services\Roles\Admin;

use Illuminate\Support\Facades\Log;

final class AdminService
{
    public function __construct(
        private readonly AdminContextService $context,
        private readonly \App\Contracts\Roles\Commerce\CommerceRepositoryInterface $commerces,
        private readonly \App\Contracts\Roles\Customer\CustomerRepositoryInterface $customers,
        private readonly \App\Contracts\Roles\Treasurer\TreasurerRepositoryInterface $treasurers,
    ) {}

    /**
     * Obtiene el contexto preparado para la visualización según rol.
     *
     * Para ciertos roles, aplica transformación de tablas con
     * IDs hasheados, columnas y keys normalizadas para frontend.
     *
     * @param string $rolePrefix Identificador del contexto (ej: 'commerces').
     * @return array Contexto listo para pasar a la vista Inertia.
     */
    public function context(string $rolePrefix): array
    {
        return $this->context->resolve($rolePrefix)->toView();
    }

    /**
     * Obtiene información de usuarios según rol
     *
     * @param string $userRole Rol del usuario
     * @param string $exclude Modelo a excluir (opcional)
     * @return \Illuminate\Http\JsonResponse
     */
    public function getUserInfo(string $userRole, string $exclude = '')
    {
        if ($userRole === 'admin') {
            return response()->json([]);
        }

        $excludedUsers = $this->resolveExcludedCreators($exclude);

        try {
            $users = match ($userRole) {
                'commerce' => $this->getFilteredUsers($this->commerces->getByStatus('ACTIVE', ['name', 'user_id']), $excludedUsers),
                'customer' => $this->getFilteredUsers($this->customers->getByStatus('ACTIVE', ['name', 'user_id']), $excludedUsers),
                'treasurer' => $this->getFilteredUsers($this->treasurers->getByStatus('ACTIVE', ['name', 'user_id']), $excludedUsers),
                default => []
            };

            return response()->json($users);
        } catch (\Throwable $e) {
            Log::error('Error obteniendo información de usuarios', [
                'user_role' => $userRole,
                'exclude' => $exclude,
                'exception' => $e->getMessage()
            ]);

            return response()->json([], 500);
        }
    }

    /**
     * Filtra usuarios excluyendo los IDs especificados
     *
     * @param \Illuminate\Database\Eloquent\Collection $users
     * @param array $excludedUserIds
     * @return \Illuminate\Database\Eloquent\Collection
     */
    protected function getFilteredUsers($users, array $excludedUserIds)
    {
        if (empty($excludedUserIds)) {
            return $users;
        }

        return $users->filter(function ($user) use ($excludedUserIds) {
            return !in_array($user->user_id ?? null, $excludedUserIds);
        })->values();
    }

    /**
     * Obtiene usuarios creadores a excluir dinámicamente
     *
     * @param string $model Nombre del modelo
     * @return array IDs de usuarios a excluir
     */
    protected function resolveExcludedCreators(string $model): array
    {
        if (!$model) {
            return [];
        }

        $modelClass = "App\\Models\\{$model}";

        if (!class_exists($modelClass)) {
            Log::warning("Modelo no existe para exclusión: {$modelClass}");
            return [];
        }

        if (!method_exists($modelClass, 'getAllCreatorUser')) {
            return [];
        }

        try {
            return $modelClass::getAllCreatorUser();
        } catch (\Throwable $e) {
            Log::error("Error obteniendo creadores del modelo {$modelClass}", [
                'exception' => $e->getMessage()
            ]);
            return [];
        }
    }
}
