<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class Authenticate
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string|null  ...$guards
     * @return mixed
     */
    public function handle(Request $request, Closure $next, ...$guards)
    {
        if (Auth::guard()->guest()) {
            return $this->unauthenticated($request);
        }

        // Verificar que el usuario esté activo
        $user = Auth::user();
        if ($user && !$user->isActive()) {
            Auth::guard()->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return $this->accountDisabled($request, $user->status);
        }

        return $next($request);
    }

    /**
     * Manejar usuario no autenticado.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return mixed
     */
    protected function unauthenticated(Request $request)
    {
        if ($request->expectsJson()) {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return redirect()->route('login');
    }

    /**
     * Manejar cuenta deshabilitada.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  string|null  $status
     * @return mixed
     */
    protected function accountDisabled(Request $request, ?string $status)
    {
        $message = match ($status) {
            'INACTIVE' => trans('auth.inactive'),
            'SUSPENDED' => trans('auth.suspended'),
            'PENDING_VERIFICATION' => trans('auth.pending_verification'),
            default => trans('auth.account_disabled'),
        };

        if ($request->expectsJson()) {
            return response()->json([
                'message' => $message,
                'status' => $status,
            ], 403);
        }

        return redirect()->route('login')->withErrors(['login' => $message]);
    }
}
