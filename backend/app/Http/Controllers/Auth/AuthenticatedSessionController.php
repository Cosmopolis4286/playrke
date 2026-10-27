<?php

namespace App\Http\Controllers\Auth;

use App\Contracts\AppParameterRepositoryInterface;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use App\Services\User\LoginStreakService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Throwable;

class AuthenticatedSessionController extends Controller
{
    /**
     * Inyecta el repositorio en el constructor
     */
    public function __construct(
        protected AppParameterRepositoryInterface $settings,
        protected LoginStreakService $loginStreakService,
    ) {
    }

    /**
     * Página de inicio / login con estado de registro
     */
    public function create() : Response
    {
        $register = $this->settings->getParameterStatus('REGISTER');

        return Inertia::render('Auth/Login', ['register' => (bool) $register]);
    }

    /**
     * Destroy an authenticated session.
     * Retorna JSON para Nuxt frontend.
     */
    public function destroy(Request $request) : JsonResponse
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
     * Maneja el login del usuario y retorna JSON para Nuxt.
     *
     * @param \App\Http\Requests\LoginRequest $request
     */
    public function store(LoginRequest $request) : JsonResponse
    {
        // Autenticar al usuario
        $request->authenticate();

        // Regenerar la sesión para evitar ataques de fijación de sesión
        $request->session()->regenerate();

        $user = \App\Models\User::with('roles:id,prefix')->find(Auth::id());

        // Obtener el rol principal del usuario (primer rol asignado)
        $rolePrefix = $user->roles->first()?->prefix;

        // Crear las variables de sesión
        $request->session()->put('userRole', $rolePrefix);

        // Registrar login y actualizar racha de días consecutivos (falla contenida:
        // no debe impedir el login si algo sale mal aquí).
        try {
            $this->loginStreakService->registerLogin($user, $request->ip());
        } catch (Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Login: fallo al registrar racha de login.', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);
        }

        // Retornar JSON con datos del usuario para Nuxt
        return response()->json(['success' => true]);
    }
}
