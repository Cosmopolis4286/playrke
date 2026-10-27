<?php

namespace App\Domains\Games\Bingo\Policies;

use App\Models\User;
use App\Policies\Roles\BaseRolePolicy;

/**
 * Policy base común a las salas de Bingo (manuales y automáticas).
 *
 * Centraliza el rol y los estados requeridos, compartidos por
 * BingoRoomPolicy y BingoRoomAutomaticPolicy.
 */
abstract class BingoBasePolicy extends BaseRolePolicy
{
    protected function forbiddenStatuses() : array
    {
        return ['DELETED', 'BLOCKED'];
    }

    /**
     * true si el usuario puede GESTIONAR salas (crear/editar/eliminar/cambiar
     * estado) — distinto de hasAccess(), que solo valida el rol de negocio.
     *
     * Antes requiredRole() por sí solo autorizaba create()/update()/delete()
     * (heredados de BaseRolePolicy) para CUALQUIER commerce/subsidiary/
     * customer, sin exigir ningún permiso explícito — la ruta sí lo exigía
     * (multiPermission:games.manage,games.bingo.manage) pero la Policy la
     * ignoraba por completo. superadmin/admin pasan por hasRole('admin') sin
     * depender del permiso explícito, para que un admin recién creado sin
     * pasar por el seeder no quede bloqueado.
     */
    protected function hasManagePermission(User $user) : bool
    {
        if ($user->hasRole(['superadmin', 'admin'])) {
            return true;
        }

        // Sin bloqueo por rol: el rol 'subsidiary' NO trae games.bingo.manage
        // por defecto (las agencias no gestionan salas), pero una agencia
        // concreta puede recibirlo como permiso individual
        // (UserSpecificPermissionGrant) — hasPermissionTo() ya lo contempla.
        return $user->hasPermissionTo('games.bingo.manage');
    }

    protected function requiredRole() : string|array
    {
        return ['superadmin', 'admin', 'commerce', 'customer', 'subsidiary'];
    }
}
