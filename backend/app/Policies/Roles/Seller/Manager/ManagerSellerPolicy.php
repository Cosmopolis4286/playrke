<?php

namespace App\Policies\Roles\Seller\Manager;

use App\Models\Roles\Seller;
use App\Models\Roles\Subsidiary;
use App\Models\User;
use App\Policies\Roles\BaseRolePolicy;
use Illuminate\Database\Eloquent\Model;

/**
 * Policy de autorización para el dominio Seller Manager.
 *
 * Reglas:
 * - Solo usuarios con roles permitidos pueden operar
 * - Vendedores en estados DELETED o BLOCKED no pueden mutar
 * - Solo sobre vendedores que pertenecen al árbol del manager autenticado
 * - Compatible con authorizeResource()
 */
class ManagerSellerPolicy extends BaseRolePolicy
{
    /**
     * Roles requeridos para operar sobre vendedores.
     *
     * @return string|string[]
     */
    protected function requiredRole(): string|array
    {
        // 'admin' agregado: gestiona el árbol completo de vendedores desde
        // /admin/sellers — antes solo pasaba hasAccess() un commerce/
        // subsidiary/customer, y un admin (no superadmin) recibía 403 en
        // cualquier acción CRUD real pese a poder ver el listado.
        return ['commerce', 'subsidiary', 'customer', 'admin'];
    }

    /**
     * Estados que bloquean operaciones mutables.
     *
     * @return string[]
     */
    protected function forbiddenStatuses(): array
    {
        return ['DELETED', 'BLOCKED'];
    }

    /**
     * true si $seller pertenece al árbol de negocio del $user autenticado.
     *
     * Antes ningún método de esta policy comparaba el vendedor objetivo
     * contra el usuario autenticado — solo el rol y el status del modelo.
     * Cualquier commerce/subsidiary/customer podía operar sobre un Seller
     * de OTRO comercio/agencia con solo cambiar el {seller} de la URL,
     * incluyendo resetPassword() (account takeover del vendedor ajeno).
     *
     * - admin: gestiona el árbol completo, no un subárbol propio — siempre
     *   "dueño" de cualquier Seller (mismo criterio que superadmin, que ya
     *   tiene bypass total vía RoleAuthorizationService::isSuperadmin() antes
     *   de llegar aquí).
     * - commerce: dueño si Seller::resolveCommerce() (cubre tanto
     *   manager_user_id directo como vía Subsidiary) resuelve a este user.
     * - subsidiary: dueño si el seller cuelga de una Subsidiary cuyo
     *   user_id es este user.
     * - customer: rama independiente sin sellers propios (ver docblock de
     *   Customer) — nunca es dueño de ningún Seller.
     */
    private function ownsSeller(User $user, Seller $seller): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('commerce')) {
            return (string) $seller->resolveCommerce()?->user_id === (string) $user->id;
        }

        if ($user->hasRole('subsidiary')) {
            if (!$seller->subsidiary_id) {
                return false;
            }

            return Subsidiary::where('id', $seller->subsidiary_id)
                ->where('user_id', $user->id)
                ->exists();
        }

        return false;
    }

    public function view(User $user, Model $model): bool
    {
        return $this->hasAccess($user) && $this->ownsSeller($user, $model);
    }

    public function update(User $user, Model $model): bool
    {
        return $this->hasAccess($user) && $this->canMutate($model) && $this->ownsSeller($user, $model);
    }

    public function forceDelete(User $user, Model $model): bool
    {
        return $this->hasAccess($user) && $this->ownsSeller($user, $model);
    }

    /**
     * Alternar estado del vendedor.
     */
    public function toggleStatus(User $user, Seller $seller): bool
    {
        return $this->hasAccess($user)
            && $this->canMutate($seller)
            && $this->ownsSeller($user, $seller);
    }

    /**
     * Cambiar contraseña asociada al vendedor.
     */
    public function resetPassword(User $user, Seller $seller): bool
    {
        return $this->hasAccess($user)
            && $this->canMutate($seller)
            && $this->ownsSeller($user, $seller);
    }
}
