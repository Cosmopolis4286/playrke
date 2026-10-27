<?php

namespace App\Policies\Prizes;

use App\Models\Prize;
use App\Models\User;
use App\Policies\Roles\BaseRolePolicy;
use App\Services\Auth\RoleAuthorizationService;
use Illuminate\Database\Eloquent\Model;

/**
 * Policy de autorización para el dominio Prizes.
 *
 * Reglas:
 * - Solo usuarios con rol admin pueden operar
 * - Solo sobre premios propios (salvo admin/superadmin, que ven todos)
 * - Compatible con authorizeResource()
 */
class PrizePolicy extends BaseRolePolicy
{
    public function forceDelete(User $user, Model $model) : bool
    {
        return $this->hasAccess($user) && $this->ownsPrize($user, $model);
    }

    /**
     * Cambiar estado del premio (ACTIVE / INACTIVE).
     */
    public function toggleStatus(User $user, Prize $prize) : bool
    {
        return $this->hasAccess($user) && $this->ownsPrize($user, $prize);
    }

    public function update(User $user, Model $model) : bool
    {
        return $this->hasAccess($user) && $this->canMutate($model) && $this->ownsPrize($user, $model);
    }

    public function view(User $user, Model $model) : bool
    {
        return $this->hasAccess($user) && $this->ownsPrize($user, $model);
    }

    /**
     * Estados que bloquean operaciones mutables.
     *
     * @return string[]
     */
    protected function forbiddenStatuses() : array
    {
        return [];
    }

    /**
     * Rol requerido para operar sobre premios.
     *
     * @return string|string[]
     */
    protected function requiredRole() : string|array
    {
        return ['superadmin', 'admin', 'commerce', 'customer', 'subsidiary', 'treasurer'];
    }

    /**
     * true si $prize pertenece al $user autenticado, o si el usuario tiene
     * rol elevado (admin/superadmin ven/gestionan todos los premios).
     *
     * Antes view/update/delete/forceDelete heredaban de BaseRolePolicy sin
     * overridear — solo verificaban el rol requerido, nunca comparaban
     * $prize->user_id contra el usuario autenticado. Cualquier
     * commerce/customer/subsidiary/treasurer podía ver/editar/eliminar el
     * premio de OTRO comercio con solo cambiar el {prize} de la URL
     * (routes/modules/utils.php solo exige 'auth', sin scope). El modelo ya
     * tenía user_id y scopeByUser() sin usar — y PrizeCrudService::create()/
     * PrizeService::search() sí filtraban correctamente, confirmando que
     * era un descuido puntual, no diseño intencional.
     */
    private function ownsPrize(User $user, Prize $prize) : bool
    {
        if (app(RoleAuthorizationService::class)->hasAnyRole($user, ['superadmin', 'admin'])) {
            return true;
        }

        return (string) $prize->user_id === (string) $user->id;
    }
}
