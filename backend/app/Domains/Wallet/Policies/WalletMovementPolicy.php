<?php

declare(strict_types=1);

namespace App\Domains\Wallet\Policies;

use App\Domains\Wallet\Models\WalletMovement;
use App\Models\MovementType;
use App\Models\User;
use App\Policies\Roles\BaseRolePolicy;
use Illuminate\Auth\Access\Response;

/**
 * Policy de autorización para movimientos de billetera.
 *
 * superadmin, admin y treasurer (rol financiero) pueden ver y gestionar
 * movimientos; ver vs. aprobar lo separa además el permiso de la ruta
 * (wallet.movements.view / wallet.movements.manage).
 */
class WalletMovementPolicy extends BaseRolePolicy
{
    public function approve(User $user, WalletMovement $movement) : bool|Response
    {
        if (! ($this->hasAccess($user)
            && $movement->status === WalletMovement::STATUS_PENDING
            && (bool) (MovementType::catalog()->get($movement->type)?->requires_approval ?? false))) {
            return false;
        }

        // Separación de funciones: nadie aprueba movimientos de su propia
        // billetera (acreditarse un depósito o liberar su propio retiro),
        // tampoco superadmin. Rechazar sí se permite: no mueve dinero a su favor.
        if ((string) $movement->user_id === (string) $user->id) {
            return Response::deny('No puedes aprobar movimientos de tu propia billetera: debe hacerlo otro administrador.');
        }

        return true;
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

    public function reject(User $user, WalletMovement $movement) : bool
    {
        return $this->hasAccess($user)
            && $movement->status === WalletMovement::STATUS_PENDING
            && (bool) (MovementType::catalog()->get($movement->type)?->requires_approval ?? false);
    }

    protected function requiredRole() : string|array
    {
        return ['superadmin', 'admin', 'treasurer'];
    }
}
