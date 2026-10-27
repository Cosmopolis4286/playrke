<?php

namespace App\Http\Controllers\Users;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\PermissionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Búsqueda ligera de usuarios para selectores async (ej. anfitrión de sala
 * privada de Bingo). Reutilizable por cualquier feature que necesite un
 * picker de usuarios — no acoplado a un dominio de juego específico.
 */
class UserSearchController extends Controller
{
    /**
     * @return JsonResponse {data: {value: string, label: string}[]}
     */
    public function search(Request $request) : JsonResponse
    {
        if (! PermissionService::checkPrefix(['superadmin', 'admin', 'commerce', 'customer'])) {
            abort(403);
        }

        $term = trim((string) $request->query('search', ''));

        $users = User::query()
            ->select(['id', 'username', 'first_name', 'last_name', 'email'])
            ->when($term !== '', function($query) use ($term) {
                $query->where(function($q) use ($term) {
                    $q->where('username', 'like', "%{$term}%")
                        ->orWhere('email', 'like', "%{$term}%")
                        ->orWhere('first_name', 'like', "%{$term}%")
                        ->orWhere('last_name', 'like', "%{$term}%");
                });
            })
            ->orderBy('username')
            ->limit(15)
            ->get();

        return $this->handleServiceJson(fn () => [
            'data' => $users->map(fn (User $user) => [
                'value' => (string) $user->id,
                'label' => trim("{$user->username} ({$user->first_name} {$user->last_name})"),
            ])->values(),
        ]);
    }
}
