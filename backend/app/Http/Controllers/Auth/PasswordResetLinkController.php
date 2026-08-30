<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Password;
use Inertia\Inertia;
use Inertia\Response;

class PasswordResetLinkController extends Controller
{
    /**
     * Display the password reset link request view.
     */
    public function create(): Response
    {
        return Inertia::render('Auth/ForgotPassword');
    }

    /**
     * Handle an incoming password reset link request.
     *
     * Responde JSON — el formulario real (useForgotPassword.ts) hace un POST
     * por axios y espera un status HTTP claro (200/422/429), mismo bug que
     * tenía RegisteredUserController::store() antes de corregirse: un
     * RedirectResponse aquí hacía que axios siempre recibiera un 200 (HTML
     * de la página, seguido automáticamente) — el toast de éxito se disparaba
     * siempre, y el mensaje "No encontramos una cuenta con ese correo" nunca
     * llegaba a mostrarse porque nunca se generaba un 422 real.
     *
     * No se distingue en la respuesta si el email existe o no (siempre
     * "genérico" salvo error real de validación/servidor) — evita
     * enumeración de cuentas registradas vía este endpoint.
     */
    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'email' => ['required', 'email'],
        ]);

        // No se usa el $status de sendResetLink() para diferenciar la
        // respuesta HTTP: Laravel intenta el envío igual sin importar si el
        // email existe, y responder distinto según el resultado real sería
        // exactamente la enumeración de cuentas que se busca evitar aquí.
        Password::sendResetLink($request->only('email'));

        return response()->json([
            'success' => true,
            'message' => 'Si el correo está registrado, te enviamos un link de recuperación.',
        ]);
    }
}
