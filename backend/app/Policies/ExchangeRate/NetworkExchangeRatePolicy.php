<?php

namespace App\Policies\ExchangeRate;

use App\Models\User;

/**
 * Widget "Tasa de cambio de tu red": la tasa con la que se venden las salas
 * de la comercializadora del usuario. Exige exchange.view-network y un rol
 * con red o salas (el jugador no vende: no aplica).
 */
class NetworkExchangeRatePolicy
{
    public const ROLES = ['commerce', 'subsidiary', 'seller', 'customer', 'admin', 'superadmin', 'treasurer'];

    public function view(User $user): bool
    {
        return $user->checkPermissionTo('exchange.view-network') && $user->hasRole(self::ROLES);
    }
}
