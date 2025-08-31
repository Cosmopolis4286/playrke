<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

// Models
use App\Models\Role;
use App\Models\Wallet;
use App\Repositories\User\RoleRepository;
// Traits
use App\Traits\Users\UserValTrait;

// Otros
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class HomeController extends Controller
{
    use UserValTrait;

    public function index()
    {
        $user = $this->user(['user_type']);

        $data = [
            'title' => 'Tablero',
            'exchangeRate' => $exchangeRate ?? 1,
            'balance' => $this->getBalance(),
        ];

        $userType = RoleRepository::getValueById($user->user_type, 'prefix');

        // Configuración de datos adicionales para cada tipo de usuario
        $extraData = [
            'user' => [],
            'admin' => [],
            'commerce' => [],
            'seller' => [],
            'seller' => []
        ];

        // Fusionar datos adicionales si existen para el tipo de usuario actual
        $data = array_merge($data, $extraData[$userType] ?? []);

        // Construir la ruta de la vista dinámicamente
        $view = "modules/{$userType}/widgets/index";

        return view($view)->with($data);
    }

    public function getBalance()
    {
        return Wallet::where('user', Auth::id())->sum('balance');
    }
}
