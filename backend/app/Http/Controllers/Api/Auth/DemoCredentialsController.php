<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Repositories\Users\Contracts\UserRepositoryContract;
use Illuminate\Http\JsonResponse;

class DemoCredentialsController extends Controller
{
    protected UserRepositoryContract $userRepository;

    public function __construct(UserRepositoryContract $userRepository)
    {
        $this->userRepository = $userRepository;
    }

    /**
     * Retorna las credenciales demo para entorno local.
     */
    public function index(): JsonResponse
    {
        if (!app()->isLocal()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 401);
        }

        $demoUsers = $this->userRepository->getDemoUsersForFrontend();

        return response()->json([
            'success' => true,
            'data'    => $demoUsers,
        ], 200);
    }
}
