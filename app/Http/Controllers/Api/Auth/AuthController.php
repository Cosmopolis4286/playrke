<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    /**
     * Handle login request
     *
     * @param LoginRequest $request
     * @return JsonResponse
     */
    public function login(LoginRequest $request): JsonResponse
    {
        try {
            // Authenticate the user
            $request->authenticate();

            // Regenerate session
            $request->session()->regenerate();

            $user = Auth::user();

            // Load relationships
            $user->load(['roles', 'country', 'wallets']);

            // Get user's primary role
            $role = $user->roles->first();

            return response()->json([
                'success' => true,
                'message' => 'Autenticación exitosa',
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'full_name' => $user->first_name . ' ' . $user->last_name,
                    'avatar' => $user->avatar_url ?? null,
                    'country_code' => $user->country?->iso_code_2,
                    'role' => $role?->prefix,
                    'roles' => $user->roles->pluck('prefix'),
                    'verified' => !is_null($user->email_verified_at),
                    'created_at' => $user->created_at,
                ],
            ]);
        } catch (ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Credenciales inválidas',
                'errors' => $e->errors(),
            ], 422);
        }
    }

    /**
     * Handle registration request
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function register(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'username' => ['required', 'string', 'max:255', 'unique:users'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'country_id' => ['required', 'exists:countries,id'],
            'terms' => ['required', 'accepted'],
        ]);

        $user = User::create([
            'username' => $validated['username'],
            'email' => $validated['email'],
            'first_name' => $validated['first_name'],
            'last_name' => $validated['last_name'],
            'password' => Hash::make($validated['password']),
            'country_id' => $validated['country_id'],
        ]);

        // Assign default player role
        $user->assignRole('player');

        // Login the user
        Auth::login($user);

        // Load relationships
        $user->load(['roles', 'country']);

        return response()->json([
            'success' => true,
            'message' => 'Usuario registrado exitosamente',
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'full_name' => $user->first_name . ' ' . $user->last_name,
                'country_code' => $user->country?->iso_code_2,
                'role' => 'player',
                'roles' => ['player'],
                'verified' => false,
                'created_at' => $user->created_at,
            ],
        ], 201);
    }

    /**
     * Handle logout request
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json([
            'success' => true,
            'message' => 'Sesión cerrada exitosamente',
        ]);
    }

    /**
     * Get authenticated user
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function user(Request $request): JsonResponse
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'No autenticado',
            ], 401);
        }

        // Load relationships
        $user->load(['roles', 'country', 'wallets']);

        // Get user's primary role
        $role = $user->roles->first();

        return response()->json([
            'success' => true,
            'user' => [
                'id' => $user->id,
                'username' => $user->username,
                'email' => $user->email,
                'first_name' => $user->first_name,
                'last_name' => $user->last_name,
                'full_name' => $user->first_name . ' ' . $user->last_name,
                'avatar' => $user->avatar_url ?? null,
                'country_code' => $user->country?->iso_code_2,
                'role' => $role?->prefix,
                'roles' => $user->roles->pluck('prefix'),
                'verified' => !is_null($user->email_verified_at),
                'created_at' => $user->created_at,
            ],
        ]);
    }

    /**
     * Handle forgot password request
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function forgotPassword(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // TODO: Implement password reset logic
        // This would typically send a password reset email

        return response()->json([
            'success' => true,
            'message' => 'Si el email existe, recibirás un enlace de recuperación',
        ]);
    }

    /**
     * Handle password reset request
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function resetPassword(Request $request): JsonResponse
    {
        $request->validate([
            'token' => ['required'],
            'email' => ['required', 'email'],
            'password' => ['required', 'confirmed', Password::defaults()],
        ]);

        // TODO: Implement password reset logic

        return response()->json([
            'success' => true,
            'message' => 'Contraseña restablecida exitosamente',
        ]);
    }
}
