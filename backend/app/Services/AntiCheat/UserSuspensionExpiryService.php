<?php

namespace App\Services\AntiCheat;

use App\Models\AntiCheat\UserSuspension;

/**
 * Lógica de negocio de auto-expiración de suspensiones temporales — usada
 * por ExpireUserSuspensionsCommand. Separado en Service (no directo en el
 * Command) para poder probarse sin pasar por la capa de consola, mismo
 * patrón que SpecificPermissionExpiryService.
 */
final class UserSuspensionExpiryService
{
    /**
     * Reactiva todos los usuarios cuya suspensión ya venció (expires_at <=
     * now), sin que un admin haya intervenido. UserSuspension::resolve()
     * ya contiene toda la lógica de reactivación (revertir User::status a
     * ACTIVE solo si no queda otra suspensión activa) — aquí solo se
     * decide A QUIÉN aplicarla y con qué actor (null = automático).
     *
     * @return int cantidad de suspensiones expiradas en esta corrida
     */
    public function expireDueSuspensions() : int
    {
        $expired = UserSuspension::query()->expired()->get();

        foreach ($expired as $suspension) {
            $suspension->resolve(null, 'Expiración automática por expires_at');
        }

        return $expired->count();
    }
}
