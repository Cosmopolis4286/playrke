<?php

namespace App\Http\Middleware;

use App\Models\UserType;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class Seller
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @return \Illuminate\Http\Response|\Illuminate\Http\RedirectResponse
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Verificar si el usuario está autenticado
        if (!Auth::check()) {
            return redirect()->route('login')->with('error', 'Por favor, inicie sesión para continuar.');
        }

        // Verificar si el usuario es de tipo 'seller' (vendedor)
        if (UserType::getValueById(Auth::user()->user_type, 'prefix') == 'seller') {
            return $next($request); // Permite el acceso al siguiente middleware o ruta
        }

        // Redirigir al dashboard si el usuario no es de tipo 4
        return redirect()->route('dashboard')->with('error', 'Acceso denegado.');
    }
}
