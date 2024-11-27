<?php

namespace App\Http\Middleware;

use App\Models\UserType;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class Customer
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Symfony\Component\HttpFoundation\Response
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verificar si el usuario está autenticado
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Por favor, inicie sesión para continuar.');
        }

        $allowedPrefixes = ['admin', 'customer'];

        // Verificar si el tipo de usuario es permitido
        if (in_array(UserType::getValueById(Auth::user()->user_type, 'prefix'), $allowedPrefixes)) {
            return $next($request); // Permite el acceso al siguiente middleware o ruta
        }

        // Redirigir al dashboard si el usuario no tiene permiso
        return redirect()->route('dashboard')->with('error', 'Acceso denegado.');
    }
}
