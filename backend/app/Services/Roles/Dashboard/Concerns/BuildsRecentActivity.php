<?php

namespace App\Services\Roles\Dashboard\Concerns;

use App\Models\User;

/**
 * Actividad reciente (últimos usuarios registrados), compartida entre los
 * dashboards de Admin y SuperAdmin — única diferencia entre ambos es si se
 * excluyen las cuentas de sistema.
 */
trait BuildsRecentActivity
{
    private function buildRecentActivity(bool $excludeSystemAccounts = false): array
    {
        $query = User::with('roles:id,name')->latest();

        if ($excludeSystemAccounts) {
            $query->excludeSystemAccounts();
        }

        return $query
            ->limit(10)
            ->get(['id', 'first_name', 'last_name', 'username', 'status', 'created_at'])
            ->map(function (User $user) {
                $name = trim("{$user->first_name} {$user->last_name}") ?: $user->username;
                $role = $user->roles->first()?->name ?? 'sin rol';

                return [
                    'id'          => (string) $user->getKey(),
                    'type'        => $user->status === 'ACTIVE' ? 'success' : 'info',
                    'title'       => "Nuevo usuario: {$name}",
                    'description' => "Rol: " . ucfirst($role),
                    'timestamp'   => $user->created_at->toIso8601String(),
                    'actor'       => $name,
                ];
            })
            ->all();
    }
}
