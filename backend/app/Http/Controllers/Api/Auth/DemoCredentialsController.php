<?php

namespace App\Http\Controllers\Api\Auth;

use App\Contracts\UserRepositoryInterface;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class DemoCredentialsController extends Controller
{
    protected UserRepositoryInterface $userRepository;

    public function __construct(UserRepositoryInterface $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Retorna las credenciales demo para entorno local.
     *
     * Doble gate independiente: config('app.demo_credentials_enabled') (false
     * por defecto, hay que activarlo a propósito en .env) + app()->isLocal().
     * Ninguno de los dos por sí solo puede dejar el endpoint expuesto — si se
     * activa el flag por error en staging, isLocal() sigue bloqueando.
     */
    public function index() : JsonResponse
    {
        if (! app()->isLocal() || ! config('app.demo_credentials_enabled')) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $demoUsers = $this->userRepository->getDemoUsersForFrontend();

        return response()->json([
            'success' => true,
            'data' => $demoUsers,
        ], 200);
    }
}
