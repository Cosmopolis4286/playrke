<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class Provider
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

        // Verificar si el usuario es de tipo 2 (admin) o tipo 5 (proveedor)
        if (Auth::user()->user_type == 2 || Auth::user()->user_type == 5) {
            return $next($request); // Permite el acceso al siguiente middleware o ruta
        }

        // Redirigir al dashboard si el usuario no es de tipo 2 ni tipo 5
        return redirect()->route('dashboard')->with('error', 'Acceso denegado.');
    }
}
