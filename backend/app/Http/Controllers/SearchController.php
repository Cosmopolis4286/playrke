<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\Menu\SidebarMenuService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Buscador global del navbar (⌘K / lupa). Busca sobre dos fuentes ya
 * accesibles al usuario autenticado: items del sidebar que puede ver
 * (mismo filtrado que SidebarMenuService::generateMenu(), para no listar
 * enlaces a pantallas a las que no tiene acceso) y usuarios (solo si su
 * rol ya puede buscarlos vía /users/search — UserSearchController).
 */
class SearchController extends Controller
{
    public function __construct(
        private readonly SidebarMenuService $menuService,
    ) {
    }

    public function search(Request $request) : View
    {
        $term = trim((string) $request->query('q', ''));
        $user = $request->user();

        $menuResults = [];
        if ($term !== '') {
            $visibleRoutes = $this->menuService->generateMenu()
                ->flatMap(fn (array $item) => isset($item['submenu'])
                    ? collect($item['submenu'])->push($item)
                    : [$item])
                ->filter(fn (array $item) => ! empty($item['route']))
                ->filter(fn (array $item) => str_contains(
                    mb_strtolower($item['title']),
                    mb_strtolower($term),
                ));

            $menuResults = $visibleRoutes
                ->map(fn (array $item) => [
                    'title' => $item['title'],
                    'url' => route($item['route']),
                ])
                ->values()
                ->all();
        }

        $userResults = [];
        $canSearchUsers = $user?->hasRole(['superadmin', 'admin', 'commerce', 'customer']) ?? false;
        if ($term !== '' && $canSearchUsers) {
            $userResults = User::query()
                ->select(['id', 'username', 'first_name', 'last_name', 'email'])
                ->where(function($query) use ($term) {
                    $query->where('username', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%")
                        ->orWhere('first_name', 'like', "%{$term}%")
                        ->orWhere('last_name', 'like', "%{$term}%");
                })
                ->orderBy('username')
                ->limit(15)
                ->get()
                ->map(fn (User $result) => [
                    'title' => trim("{$result->username} ({$result->first_name} {$result->last_name})"),
                    'url' => route('admin.users'),
                ])
                ->values()
                ->all();
        }

        return view('modules.shared.search.index', [
            'term' => $term,
            'menuResults' => $menuResults,
            'userResults' => $userResults,
        ]);
    }
}
