<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Contracts\UserRepositoryInterface;
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
