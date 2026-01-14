<?php

namespace App\Http\Controllers\Api\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Models\Countries\Country;
use App\Models\Currencies\Currency;
use App\Models\Users\User;
use App\Models\Wallets\Wallet;
use App\Repositories\Users\UserRepository;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    protected UserRepository $userRepository;

    public function __construct(UserRepository $userRepository)
    {
        // Inyección de dependencia para generar códigos únicos
        $this->userRepository = $userRepository;
    }

    /**
     * Handle login request
     *
     * @param LoginRequest $request
     * @return JsonResponse
     */
    public function login(LoginRequest $request): JsonResponse
    {
        Log::info('📥 Login request received', [
            'ip' => $request->ip(),
            'user_agent' => $request->userAgent(),
            'timestamp' => now()->toISOString(),
            'request_id' => $request->header('X-Request-ID') ?? uniqid(),
        ]);

        try {
            $request->authenticate();
            $request->session()->regenerate();

            $user = Auth::user();
            /** @var \App\Models\Users\User $user */
            $user->load(['roles:id,prefix', 'country:id,iso_code_2', 'wallets']); // Solo campos necesarios

            $role = $user->roles->first();

            return response()->json([
                'success' => true,
                'message' => 'Autenticación exitosa',
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    // 'first_name' => $user->first_name,
                    // 'last_name' => $user->last_name,
                    // 'full_name' => trim($user->first_name . ' ' . $user->last_name),
                    'avatar' => $user->avatar_url,
                    'country_code' => $user->country?->iso_code_2,
                    'role' => $role?->prefix,
                    'roles' => $user->roles->pluck('prefix'),
                    'verified' => !is_null($user->email_verified_at),
                    'created_at' => $user->created_at->toISOString(),
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
            'first_name' => ['nullable', 'string', 'min:2', 'max:100'],
            'last_name' => ['nullable', 'string', 'min:2', 'max:100'],
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
            //'first_name.required' => 'El nombre es requerido.',
            //'first_name.min' => 'El nombre debe tener al menos 2 caracteres.',
            //'last_name.required' => 'El apellido es requerido.',
            //'last_name.min' => 'El apellido debe tener al menos 2 caracteres.',
            'password.required' => 'La contraseña es requerida.',
            'password.confirmed' => 'Las contraseñas no coinciden.',
            'password.min' => 'La contraseña debe tener al menos 8 caracteres.',
            'country_id.exists' => 'El país seleccionado no es válido.',
        ]);

        $countryId = $validated['country_id'] ?? Country::where('iso_code_2', 'VE')->value('id');

        DB::beginTransaction();

        try {
            $user = User::create([
                'username' => $validated['username'],
                'email' => $validated['email'],
                'first_name' => $validated['first_name'] ?? null,
                'last_name' => $validated['last_name'] ?? null,
                'password' => Hash::make($validated['password']),
                'country_id' => $countryId,
                'status' => 'ACTIVE',
                'referral_code' => $this->userRepository->generateUniqueReferralCode(),
            ]);

            $user->assignRole('player');

            $currency = Currency::where('country_id', $countryId)->first()
                ?? Currency::where('iso_code', 'VES')->first();

            if ($currency) {
                Wallet::create([
                    'user_id' => $user->id,
                    'currency_id' => $currency->id,
                    'balance' => 0.00,
                    'status' => 'ACTIVE',
                ]);
            }

            DB::commit();

            Auth::login($user);
            $request->session()->regenerate();

            $user->load(['roles:id,prefix', 'country:id,iso_code_2', 'wallets.currency:id,iso_code']);

            return response()->json([
                'success' => true,
                'message' => 'Usuario registrado exitosamente',
                'user' => [
                    'id' => $user->id,
                    'username' => $user->username,
                    'email' => $user->email,
                    'first_name' => $user->first_name,
                    'last_name' => $user->last_name,
                    'full_name' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')),
                    'country_code' => $user->country?->iso_code_2,
                    'role' => 'player',
                    'roles' => ['player'],
                    'verified' => false,
                    'created_at' => $user->created_at->toISOString(),
                ],
            ], 201);
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Error en registro de usuario', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
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

        $user->load(['roles:id,prefix', 'country:id,iso_code_2', 'wallets']);

        $role = $user->roles->first();

        return response()->json([
            'id' => $user->id,
            'username' => $user->username,
            'email' => $user->email,
            'first_name' => $user->first_name,
            'last_name' => $user->last_name,
            'full_name' => trim(($user->first_name ?? '') . ' ' . ($user->last_name ?? '')),
            'avatar' => $user->avatar_url,
            'country_code' => $user->country?->iso_code_2,
            'role' => $role?->prefix,
            'roles' => $user->roles->pluck('prefix'),
            'verified' => !is_null($user->email_verified_at),
            'created_at' => $user->created_at->toISOString(),
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

        // TODO: Implementar lógica real para reseteo de contraseña, como envío de email

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

        // TODO: Implementar lógica real para restablecer la contraseña con token válido

        return response()->json([
            'success' => true,
            'message' => 'Contraseña restablecida exitosamente',
        ]);
    }
}
