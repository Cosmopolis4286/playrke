<?php

namespace App\Domains\Wallet\Policies;

use App\Domains\Wallet\Models\WalletMovement;
use App\Models\MovementType;
use App\Models\User;
use App\Policies\Roles\BaseRolePolicy;

/**
 * Policy de autorización para movimientos de billetera.
 *
 * Solo superadmin y admin pueden ver y gestionar movimientos.
 */
class WalletMovementPolicy extends BaseRolePolicy
{
    protected function requiredRole(): string|array
    {
        return ['superadmin', 'admin'];
    }

    public function approve(User $user, WalletMovement $movement): bool
    {
        return $this->hasAccess($user)
            && $movement->status === WalletMovement::STATUS_PENDING
            && (bool) (MovementType::catalog()->get($movement->type)?->requires_approval ?? false);
    }

    public function reject(User $user, WalletMovement $movement): bool
    {
        return $this->hasAccess($user)
            && $movement->status === WalletMovement::STATUS_PENDING
            && (bool) (MovementType::catalog()->get($movement->type)?->requires_approval ?? false);
    }
}
