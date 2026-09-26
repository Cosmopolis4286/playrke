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
     * Acción de dominio (no CRUD estándar) — el nombre debe coincidir
     * exactamente con la acción del controller (toggleStatus()), ya que
     * authorizeResource() no la cubre automáticamente y por lo tanto
     * requiere un $this->authorize('toggleStatus', ...) explícito que
     * resuelve este método por nombre.
     *
     * @param User $user
     * @param Subsidiary $subsidiary
     * @return bool
     */
    public function toggleStatus(User $user, Subsidiary $subsidiary): bool
    {
        return $this->hasAccess($user)
            && $this->canMutate($subsidiary)
            && $this->ownsSubsidiary($user, $subsidiary);
    }

    /**
     * Eliminar (soft delete) la sucursal — acción de dominio 'delete', no
     * 'destroy' (el hard delete, ya cubierto por authorizeResource()).
     *
     * Tipo del segundo parámetro forzado a Model (no Subsidiary): este
     * método SÍ sobrescribe BaseRolePolicy::delete(User, Model), a
     * diferencia de toggleStatus()/resetPassword() (métodos propios, sin
     * equivalente en la clase base) — estrechar el tipo aquí rompe PHP por
     * contravarianza (fatal error en el bootstrap de CUALQUIER request, no
     * solo los que llaman authorize('delete', ...), confirmado en runtime).
     *
     * @param User $user
     * @param Model $model
     * @return bool
     */
    public function delete(User $user, Model $model): bool
    {
        return $this->hasAccess($user)
            && $this->canMutate($model)
            && $this->ownsSubsidiary($user, $model);
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
