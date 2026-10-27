<?php

namespace App\Traits;

use App\Models\User;

/**
 * Resuelve los superadmins activos a notificar — antes duplicado
 * literalmente en CommandService y SpecificPermissionExpiryService; se
 * extrajo aquí porque una tercera y cuarta ronda de comandos programados
 * (recuperación de salas de Bingo atascadas, reembolsos abandonados)
 * necesitan la misma resolución.
 */
trait ResolvesSuperadminIds
{
    /**
     * @return array<int, string>
     */
    protected function superadminIds() : array
    {
        return User::role('superadmin')
            ->where('status', 'ACTIVE')
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();
    }
}
