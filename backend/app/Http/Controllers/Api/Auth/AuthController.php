<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Users\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
        // Log para detectar llamadas duplicadas
        \Log::info('📥 Login request received', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toISOString(),
            'request_id' => $request->header('X-Request-ID') ?? uniqid(),
        ]);

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
            'username' => ['required', 'string', 'min:3', 'max:50', 'unique:users', 'regex:/^[a-zA-Z0-9_]+$/'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'first_name' => ['required', 'string', 'min:2', 'max:100'],
            'last_name' => ['required', 'string', 'min:2', 'max:100'],
            'password' => ['required', 'confirmed', Password::min(8)->letters()->numbers()],
            'country_id' => ['nullable', 'exists:countries,id'],
        ], [
            'username.required' => 'El nombre de usuario es requerido.',
            'username.min' => 'El nombre de usuario debe tener al menos 3 caracteres.',
            'username.max' => 'El nombre de usuario no puede exceder 50 caracteres.',
            'username.unique' => 'Este nombre de usuario ya está en uso.',
            'username.regex' => 'El nombre de usuario solo puede contener letras, números y guiones bajos.',
            'email.required' => 'El email es requerido.',
            'email.email' => 'El email debe ser una dirección válida.',
            'email.unique' => 'Este email ya está registrado.',
            'first_name.required' => 'El nombre es requerido.',
            'first_name.min' => 'El nombre debe tener al menos 2 caracteres.',
            'last_name.required' => 'El apellido es requerido.',
            'last_name.min' => 'El apellido debe tener al menos 2 caracteres.',
            'password.required' => 'La contraseña es requerida.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'country_id.exists' => 'El país seleccionado no es válido.',
        ]);

        // Usar país por defecto si no se proporciona (Venezuela)
        $countryId = $validated['country_id'] ?? \App\Models\Countries\Country::where('iso_code_2', 'VE')->value('id');

        DB::beginTransaction();

        try {
            // Crear usuario
            $user = User::create([
                'username' => $validated['username'],
                'email' => $validated['email'],
                'first_name' => $validated['first_name'],
                'last_name' => $validated['last_name'],
                'password' => Hash::make($validated['password']),
                'country_id' => $countryId,
                'status' => 'ACTIVE',
                'referral_code' => app(\App\Repositories\Users\UserRepository::class)->generateUniqueReferralCode(),
            ]);

            // Asignar rol de player por defecto
            $user->assignRole('player');

            // Obtener la moneda del país del usuario
            $currency = \App\Models\Currencies\Currency::where('country_id', $countryId)->first();

            // Si no hay moneda específica para el país, usar VES por defecto
            if (!$currency) {
                $currency = \App\Models\Currencies\Currency::where('iso_code', 'VES')->first();
            }

            // Crear wallet para el usuario
            if ($currency) {
                \App\Models\Wallets\Wallet::create([
                    'user_id' => $user->id,
                    'currency_id' => $currency->id,
                    'balance' => 0.00,
                    'status' => 'ACTIVE',
                ]);
            }

            DB::commit();

            // Hacer login automático
            Auth::login($user);

            // Regenerar sesión
            $request->session()->regenerate();

            // Cargar relaciones
            $user->load(['roles', 'country', 'wallets.currency']);

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

        } catch (\Exception $e) {
            DB::rollBack();

            \Log::error('Error en registro de usuario', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Error al registrar usuario. Por favor intenta nuevamente.',
                'errors' => ['general' => ['Ocurrió un error inesperado. Por favor contacta al soporte.']],
            ], 500);
        }
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

        // Devuelve directamente el objeto user (compatibilidad con frontend que espera User)
        return response()->json([
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
        ]);
    }

    /**
     * Alias específico para /api/auth/me
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function me(Request $request): JsonResponse
    {
        // Reutiliza la lógica existente en user() para consistencia
        return $this->user($request);
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
