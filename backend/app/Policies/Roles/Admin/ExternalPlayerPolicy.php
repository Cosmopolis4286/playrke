<?php

namespace App\Policies\Roles\Admin;

use App\Models\Roles\Player\External\ExternalPlayer;
use App\Models\Roles\Seller;
use App\Models\User;
use App\Services\Auth\RoleAuthorizationService;

/**
 * Policy de autorización para la administración de Jugadores Externos
 * (compradores de cartones de Bingo sin cuenta, ver App\Services\Roles\Player\External\ExternalPlayerLinkService).
 *
 * A diferencia del resto de policies de Roles/Admin, NO extiende BaseRolePolicy
 * (que autoriza por rol fijo): usa permisos granulares `external-players.*`
 * (ver PermissionSeeder/RolePermissionSeeder) para que admin/superadmin sigan
 * teniendo acceso por defecto, pero también sea otorgable a commerce/seller
 * desde /admin/permissions sin tocar código.
 */
class ExternalPlayerPolicy
{
    public function create(User $user) : bool
    {
        return $this->hasPermission($user, 'external-players.create');
    }

    public function export(User $user) : bool
    {
        return $this->hasPermission($user, 'external-players.export');
    }

    public function fetchList(User $user) : bool
    {
        return $this->hasPermission($user, 'external-players.view');
    }

    public function toggleBlock(User $user, ExternalPlayer $externalPlayer) : bool
    {
        return $this->hasPermission($user, 'external-players.block')
            && $this->ownsExternalPlayer($user, $externalPlayer);
    }

    public function update(User $user, ExternalPlayer $externalPlayer) : bool
    {
        return $this->hasPermission($user, 'external-players.manage')
            && $this->ownsExternalPlayer($user, $externalPlayer);
    }

    public function view(User $user, ExternalPlayer $externalPlayer) : bool
    {
        return $this->hasPermission($user, 'external-players.view')
            && $this->ownsExternalPlayer($user, $externalPlayer);
    }

    public function viewAny(User $user) : bool
    {
        return $this->hasPermission($user, 'external-players.view');
    }

    private function hasPermission(User $user, string $permission) : bool
    {
        return app(RoleAuthorizationService::class)->hasAnyPermission($user, $permission);
    }

    private function isAdminLike(User $user) : bool
    {
        return $user->hasRole('admin') || $user->hasRole('superadmin');
    }

    /**
     * true si $externalPlayer fue captado por un Seller del árbol de
     * negocio del $user autenticado.
     *
     * Antes ningún método de esta policy comparaba el jugador objetivo
     * contra el usuario autenticado — solo el permiso. Mismo riesgo que
     * ManagerSellerPolicy::ownsSeller() ya documentó y corrigió para
     * Seller: exponer esta ruta a Commerce/Subsidiary sin este chequeo
     * permitiría mutar (bloquear/reactivar/convertir) jugadores externos
     * de CUALQUIER otro comercio/agencia con solo cambiar el
     * {externalPlayer} de la URL.
     *
     * - admin/superadmin: sin restricción adicional.
     * - commerce: dueño si el Seller que lo captó (seller_user_id) resuelve,
     *   vía Seller::resolveCommerce(), al Commerce de este user.
     * - seller: dueño solo de los que captó él mismo (seller_user_id = su user).
     * - subsidiary: dueño si el Seller que lo captó cuelga (subsidiary_id)
     *   de la Subsidiary de este user — un seller que reporta directo al
     *   commerce (sin subsidiary_id) nunca es de una agencia, así que
     *   ninguna subsidiary es "dueña" de esos jugadores.
     */
    private function ownsExternalPlayer(User $user, ExternalPlayer $externalPlayer) : bool
    {
        if ($this->isAdminLike($user)) {
            return true;
        }

        if (! $externalPlayer->seller_user_id) {
            return false;
        }

        // Vendedor: dueño solo de los compradores que captó él mismo (mismo
        // criterio que ExternalPlayerAdminService::applyOwnershipFilter()).
        // Sus acciones siguen limitadas por permiso (view/create, no
        // manage/block), así que esto no le permite modificarlos.
        if ($user->hasRole('seller')) {
            return (string) $externalPlayer->seller_user_id === (string) $user->id;
        }

        $seller = Seller::where('user_id', $externalPlayer->seller_user_id)->first();

        if ($user->hasRole('commerce')) {
            return (string) $seller?->resolveCommerce()?->user_id === (string) $user->id;
        }

        if ($user->hasRole('subsidiary')) {
            if (! $seller?->subsidiary_id) {
                return false;
            }

            return \App\Models\Roles\Subsidiary::where('id', $seller->subsidiary_id)
                ->where('user_id', $user->id)
                ->exists();
        }

        return false;
    }
}
