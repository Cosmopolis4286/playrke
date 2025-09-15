<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\Wallets\Wallet;
use App\Repositories\User\Roles\RoleRepository;
use App\Traits\Users\UserTrait;
use Illuminate\Support\Facades\Auth;

/**
 * Controlador para la página principal / dashboard.
 */
class HomeController extends Controller
{
    use UserTrait;

    /**
     * Mostrar el tablero principal del usuario.
     *
     * @return \Illuminate\View\View
     */
    public function index()
    {
        $user = $this->user(); // Obtener usuario actual con toda la info

        // Obtener rol principal (primer rol asignado) del usuario
        $firstRoleId = $user->roles->first()?->id;
        $userRole = $firstRoleId ? RoleRepository::getValueById($firstRoleId, 'prefix') : 'user';

        // Datos base
        $data = [
            'title' => 'Tablero',
            'exchangeRate' => 1, // Valor por defecto si no hay otro cálculo
            'balance' => $this->getBalance($user->id),
        ];

        // Configuración de datos adicionales según tipo de usuario
        $extraData = [
            'user' => [],
            'admin' => [],
            'commerce' => [],
            'seller' => [],
        ];

        // Fusionar datos adicionales si existen para el tipo de usuario actual
        $data = array_merge($data, $extraData[$userRole] ?? []);

        // Construir la ruta de la vista dinámicamente
        $view = "modules/users/roles/{$userRole}/widgets/index";

        return view($view)->with($data);
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
