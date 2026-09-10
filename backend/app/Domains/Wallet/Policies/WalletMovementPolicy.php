<?php

declare(strict_types=1);

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
    public function approve(User $user, WalletMovement $movement) : bool
    {
        return $this->hasAccess($user)
            && $movement->status === WalletMovement::STATUS_PENDING
            && (bool) (MovementType::catalog()->get($movement->type)?->requires_approval ?? false);
    }

    public function reject(User $user, WalletMovement $movement) : bool
    {
        return $this->hasAccess($user)
            && $movement->status === WalletMovement::STATUS_PENDING
            && (bool) (MovementType::catalog()->get($movement->type)?->requires_approval ?? false);
    }

    /**
     * Tomar/liberar un movimiento PENDING como "en revisión propia" — acción
     * de dominio con regla propia (no CRUD genérico), mismo criterio que
     * EscrowPolicy::resolve(): no hereda de update()/delete() de
     * BaseRolePolicy porque no es una mutación de campos arbitraria. La
     * misma regla autoriza tanto assign() como unassign(); el detalle de
     * "quién puede liberar el caso de quién" depende del actor vs. el dueño
     * actual de la asignación y se resuelve en WalletService, no aquí.
     */
    public function assign(User $user, WalletMovement $movement) : bool
    {
        return $this->hasAccess($user) && $movement->status === WalletMovement::STATUS_PENDING;
    }

    protected function requiredRole() : string|array
    {
        return ['superadmin', 'admin'];
    }
}
