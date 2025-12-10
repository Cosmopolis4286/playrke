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

    public function index(): JsonResponse
    {
        if (!app()->isLocal()) {
            return response()->json(['error' => 'Unauthorized'], 401);
        }

        $demoUsers = $this->userRepository->getDemoUsers();

        return response()->json($demoUsers);
    }
}
