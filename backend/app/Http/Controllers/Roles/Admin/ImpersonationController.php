<?php

namespace App\Http\Controllers\Roles\Admin;

use App\Exceptions\Domain\DomainException;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Auth\ImpersonationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Impersonación de jugadores para soporte — solo SuperAdmin (ver
 * role_protected_routes en routes/modules/admin.php). Toda la lógica de
 * dominio vive en ImpersonationService; este controlador solo valida el
 * request y decide a dónde redirigir.
 *
 * DomainException se captura aquí (no se deja propagar al Handler global)
 * porque estas dos rutas son navegación de browser normal (form POST +
 * redirect), no fetch/AJAX — el render(DomainException) global en
 * bootstrap/app.php siempre devuelve JSON, que rompería esta navegación.
 */
final class ImpersonationController extends Controller
{
    public function __construct(
        private readonly ImpersonationService $service
    ) {}

    public function start(User $player, Request $request): RedirectResponse
    {
        try {
            $this->service->start($request->user(), $player);

            return redirect('/')->with('success', "Ahora estás viendo la plataforma como {$player->username}.");
        } catch (DomainException $e) {
            return redirect('/admin/players')->with('error', $this->resolveErrorMessage($e));
        }
    }

    public function stop(Request $request): RedirectResponse
    {
        try {
            $this->service->stop($request);

            return redirect('/admin/players')->with('success', 'Sesión de soporte finalizada.');
        } catch (DomainException $e) {
            return redirect('/')->with('error', $this->resolveErrorMessage($e));
        }
    }

    private function resolveErrorMessage(DomainException $e): string
    {
        return trans("errors.{$e->getMessage()}") ?: $e->getMessage();
    }
}
