<?php

namespace App\Policies\Roles\Admin;

use App\Models\Roles\Subsidiary;
use App\Models\User;
use App\Policies\Roles\BaseRolePolicy;
use Illuminate\Database\Eloquent\Model;

/**
 * Policy de autorización para el dominio Subsidiary.
 *
 * Reglas:
 * - admin/superadmin: gestiona el árbol completo de sucursales.
 * - commerce: gestiona solo SUS PROPIAS sucursales (ver ownsSubsidiary()).
 * - Sucursales en estados DELETED o BLOCKED no pueden mutar
 * - Compatible con authorizeResource()
 */
class AdminSubsidiaryPolicy extends BaseRolePolicy
{
    /**
     * Roles requeridos para operar sobre sucursales.
     *
     * @return string|string[]
     */
    protected function requiredRole(): string|array
    {
        return ['admin', 'commerce'];
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
     * true si $subsidiary pertenece al Commerce del $user autenticado.
     *
     * Antes ningún método de esta policy comparaba la sucursal objetivo
     * contra el usuario autenticado — solo el rol (fijo a 'admin', que
     * gestiona todo). Al abrir el rol a 'commerce', sin este chequeo
     * cualquier commerce podría operar sobre una Subsidiary de OTRO
     * comercio con solo cambiar el {subsidiary} de la URL — mismo riesgo
     * que ManagerSellerPolicy::ownsSeller() ya documentó y corrigió para
     * Seller (account takeover del recurso ajeno).
     *
     * - admin: gestiona el árbol completo, siempre "dueño".
     * - commerce: dueño si Subsidiary::commerce()->user_id es este user.
     */
    private function ownsSubsidiary(User $user, Subsidiary $subsidiary): bool
    {
        if ($user->hasRole('admin')) {
            return true;
        }

        if ($user->hasRole('commerce')) {
            $subsidiary->loadMissing('commerce');
            return (string) $subsidiary->commerce?->user_id === (string) $user->id;
        }

        return false;
    }

    public function view(User $user, Model $model): bool
    {
        return $this->hasAccess($user) && $this->ownsSubsidiary($user, $model);
    }

    public function update(User $user, Model $model): bool
    {
        return $this->hasAccess($user) && $this->canMutate($model) && $this->ownsSubsidiary($user, $model);
    }

    public function forceDelete(User $user, Model $model): bool
    {
        return $this->hasAccess($user) && $this->ownsSubsidiary($user, $model);
    }

    /**
     * Cambiar estado de la sucursal.
     *
     * Acción de dominio (no CRUD estándar).
     *
     * @param User $user
     * @param Subsidiary $subsidiary
     * @return bool
     */
    public function changeStatus(User $user, Subsidiary $subsidiary): bool
    {
        return $this->hasAccess($user)
            && $this->canMutate($subsidiary)
            && $this->ownsSubsidiary($user, $subsidiary);
    }

    /**
     * Cambiar contraseña asociada a la sucursal.
     *
     * Acción de dominio (no CRUD estándar).
     *
     * @param User $user
     * @param Subsidiary $subsidiary
     * @return bool
     */
    public function resetPassword(User $user, Subsidiary $subsidiary): bool
    {
        return $this->hasAccess($user)
            && $this->canMutate($subsidiary)
            && $this->ownsSubsidiary($user, $subsidiary);
    }
}
