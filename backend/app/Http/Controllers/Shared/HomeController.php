<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\Wallets\Wallet;
use App\Traits\Users\UserTrait;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\JsonResponse;
use Illuminate\View\View;

/**
 * Controlador para la página principal / dashboard.
 * Ahora solo API - Nuxt maneja las vistas
 */
class HomeController extends Controller
{
    use UserTrait;

    /**
     * Retorna datos del dashboard para el usuario autenticado.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(): View
    {
        dd(33);
        $user = Auth::user();
        $firstRole = $user->roles->first();
        $userRole = $firstRole?->prefix ?? 'user';

        $data = [
            'title' => 'Dashboard',
            'exchangeRate' => 1,
            'balance' => $this->getBalance($user->id),
            'userRole' => $userRole,
            'user' => [
                'id' => (string)$user->id,
                'username' => $user->username,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'email' => $user->email,
                'avatar' => $user->avatar ?? null,
                'roles' => [$userRole],
                'permissions' => [],
            ],
        ];

        $view = "modules/users/roles/{$userRole}/widgets/index";

        return view($view, $data);
    }

    /**
     * Obtener el balance total del usuario.
     *
     * @param int|null $userId
     * @return float
     */
    public function getBalance(?int $userId = null): float
    {
        $userId ??= Auth::id();

        return Wallet::where('user_id', $userId)->sum('balance');
    }
}
