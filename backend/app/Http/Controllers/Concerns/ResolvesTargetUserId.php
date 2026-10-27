<?php

namespace App\Http\Controllers\Concerns;

use Illuminate\Http\Request;
use Vinkla\Hashids\Facades\Hashids;

/**
 * Resuelve target_user_id desde query/body de la request — usado por los 3
 * controladores de "Mi Perfil" (Seller/Commerce/Subsidiary) para que
 * admin/superadmin puedan inspeccionar la ficha de un usuario específico
 * (ver AdminProfileAccessService).
 *
 * El frontend nunca expone el Snowflake ID crudo de otro usuario — enlaza
 * con el mismo hashed_id que usa en toda la UI admin (ej. "Ver perfil" en
 * los listados de sellers/comercios/agencias) — así que se decodifica con
 * Hashids, mismo patrón que HasHashedRouteKey::resolveRouteBinding() y
 * DirectoryController::resolveTargetUserId(). Si no decodifica (valor ya
 * numérico, o hash inválido) se deja pasar tal cual: el *ProfileService
 * será quien determine si el ID resultante es válido.
 */
trait ResolvesTargetUserId
{
    private function resolveTargetUserId(Request $request) : ?string
    {
        $target = $request->input('target_user_id') ?? $request->query('target_user_id');

        if (! $target) {
            return null;
        }

        $decoded = Hashids::connection('main')->decode((string) $target);

        return $decoded ? (string) $decoded[0] : (string) $target;
    }
}
