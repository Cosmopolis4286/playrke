<?php

namespace App\Http\Controllers;

use App\Models\UserSidebarFavorite;
use App\Services\Menu\SidebarMenuService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Toggle de accesos directos del sidebar (ver
 * SidebarMenuService::getFavorites() y sidebar-dynamic.blade.php). No
 * valida item_key contra $menuStructure aquí: un favorito a un item que
 * ya no existe o que el usuario no puede ver simplemente no se resuelve
 * en getFavorites() y queda como fila huérfana inofensiva.
 */
class SidebarFavoriteController extends Controller
{
    public function __construct(private readonly SidebarMenuService $menuService) {}

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'item_key' => 'required|string|max:150',
        ]);

        $maxOrder = UserSidebarFavorite::where('user_id', $request->user()->id)->max('sort_order');

        UserSidebarFavorite::firstOrCreate(
            ['user_id' => $request->user()->id, 'item_key' => $validated['item_key']],
            ['sort_order' => ($maxOrder ?? -1) + 1]
        );

        return response()->json(['success' => true]);
    }

    public function destroy(Request $request, string $itemKey): JsonResponse
    {
        UserSidebarFavorite::where('user_id', $request->user()->id)
            ->where('item_key', $itemKey)
            ->delete();

        return response()->json(['success' => true]);
    }

    /**
     * GET /sidebar/pending-wallet-movements-count
     *
     * Resincroniza el badge de "movimientos pendientes" del navbar/sidebar
     * (PendingMovementsBadge.vue) cuando el conteo en tiempo real puede
     * haber quedado desactualizado: el valor inicial solo se recalcula en
     * un full page load (viene embebido en auth-layout.blade.php como
     * data-initial-pending-count), y el broadcast WebSocket que lo
     * mantiene vivo es best-effort — si Reverb no está disponible o la
     * conexión se cae un momento, el evento se pierde sin reintento y el
     * badge queda congelado hasta el próximo full reload. Este endpoint
     * se llama al montar el badge y cada vez que el WebSocket reconecta,
     * para corregir cualquier drift sin depender de un evento perdido.
     */
    public function pendingWalletMovementsCount(Request $request): JsonResponse
    {
        // Mismo criterio de roles que NavbarComposer::ROLES_PENDING_MOVEMENTS
        // — sin esto, cualquier usuario autenticado (jugador, comercio...)
        // podría consultar cuántos depósitos/retiros de OTROS usuarios están
        // pendientes, un dato administrativo que no le corresponde ver.
        if (! $request->user()->hasRole(['superadmin', 'admin', 'treasurer'])) {
            return response()->json(['success' => true, 'count' => 0]);
        }

        return response()->json([
            'success' => true,
            'count'   => $this->menuService->pendingWalletMovementsCount(),
        ]);
    }
}
