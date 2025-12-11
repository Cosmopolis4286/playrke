<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Repositories\Users\Contracts\UserRepositoryContract;
use Illuminate\Http\JsonResponse;

/**
 * Controlador para exponer credenciales demo para desarrollo local.
 *
 * @group Demo Credentials
 *
 * Obtiene los usuarios marcados como "is_demo_user"
 * y devuelve información legible por el frontend.
 */
class DemoCredentialsController extends Controller
{
    /**
     * @var UserRepositoryContract Repositorio de usuarios
     */
    protected UserRepositoryContract $userRepository;

    /**
     * Constructor del controlador.
     *
     * @param UserRepositoryContract $userRepository
     */
    public function __construct(UserRepositoryContract $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Obtener listado de usuarios demo formateados para el frontend.
     *
     * Solo funciona en entorno local (APP_ENV=local).
     *
     * @return JsonResponse
     */
    public function index(): JsonResponse
    {
        if (!app()->isLocal()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized'
            ], 401);
        }

        // ⚠️ Aquí se usa el método correcto para enviar datos al frontend
        $demoUsers = $this->userRepository->getDemoUsersForFrontend();

        return response()->json([
            'success' => true,
            'data'    => $demoUsers,
        ]);
    }
}
