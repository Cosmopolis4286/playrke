<?php

namespace App\Http\Controllers;

use App\Models\UserSidebarFavorite;
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
}
