<?php

namespace App\Http\Controllers\Roles\Admin;

use App\Exceptions\Domain\DomainException;
use App\Http\Controllers\Controller;
use App\Models\Audit;
use App\Models\User;
use App\Services\Auth\ImpersonationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Impersonación de cuentas operativas para soporte — solo SuperAdmin (ver
 * role_protected_routes en routes/modules/admin.php). Toda la lógica de
 * dominio (incluyendo qué roles son impersonables, ver
 * ImpersonationService::ALLOWED_TARGET_ROLES) vive en ImpersonationService;
 * este controlador solo valida el request y decide a dónde redirigir.
 *
 * El parámetro de ruta se sigue llamando {player} (histórico, era
 * exclusivo de jugadores) pero el binding es por User::class — funciona
 * igual para seller/commerce/subsidiary, no hace falta tocar la ruta.
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
        $target = $player;

        try {
            $this->service->start($request->user(), $target);

            return redirect('/')->with('success', "Ahora estás viendo la plataforma como {$target->username}.");
        } catch (DomainException $e) {
            return redirect()->back()->with('error', $this->resolveErrorMessage($e));
        }
    }

    public function stop(Request $request): RedirectResponse
    {
        try {
            $this->service->stop($request);

            return redirect('/')->with('success', 'Sesión de soporte finalizada.');
        } catch (DomainException $e) {
            return redirect('/')->with('error', $this->resolveErrorMessage($e));
        }
    }

    public function historyPage(): Response
    {
        return Inertia::render('Roles/Admin/Impersonation/History', [
            'history' => $this->resolveHistory(1),
        ]);
    }

    public function history(Request $request): JsonResponse
    {
        $page = (int) $request->get('page', 1);

        return response()->json([
            'success' => true,
            ...$this->resolveHistory($page),
        ]);
    }

    /**
     * Historial consolidado: cada 'impersonation_started' emparejado con su
     * 'impersonation_ended' correspondiente (mismo admin + mismo jugador,
     * el primer 'ended' posterior al 'started'). Un 'started' sin 'ended'
     * emparejado es una sesión que sigue activa (o se cerró por otra vía sin
     * pasar por stop(), ej. expiró la sesión HTTP completa) — se marca como
     * tal en vez de ocultarse, es información operativa relevante.
     */
    private function resolveHistory(int $page): array
    {
        $perPage = 20;

        $starts = Audit::ofEvent('impersonation_started')
            ->with('user:id,username')
            ->latest('created_at')
            ->paginate($perPage, ['*'], 'page', $page);

        $rows = $starts->getCollection()->map(function (Audit $start) {
            $targetId = is_array($start->auditable_id) ? ($start->auditable_id[0] ?? null) : $start->auditable_id;

            $end = Audit::ofEvent('impersonation_ended')
                ->where('user_id', $start->user_id)
                ->whereRaw('JSON_UNQUOTE(auditable_id) = ?', [(string) $targetId])
                ->where('created_at', '>=', $start->created_at)
                ->orderBy('created_at')
                ->first();

            return [
                'id'                => (string) $start->id,
                'admin_username'    => $start->user?->username ?? $start->new_values['admin_username'] ?? '—',
                'target_username'   => $start->new_values['target_username'] ?? '—',
                'started_at'        => $start->created_at?->format('d/m/Y H:i:s'),
                'ended_at'          => $end?->created_at?->format('d/m/Y H:i:s'),
                'is_active'         => $end === null,
                'duration_minutes'  => $end ? $start->created_at->diffInMinutes($end->created_at) : null,
            ];
        });

        return [
            'columns' => [
                ['key' => 'admin_username',  'label' => 'Admin',    'sortable' => false],
                ['key' => 'target_username', 'label' => 'Jugador',  'sortable' => false],
                ['key' => 'started_at',      'label' => 'Inicio',   'sortable' => false],
                ['key' => 'ended_at',        'label' => 'Fin',      'sortable' => false],
                ['key' => 'is_active',       'label' => 'Estado',   'sortable' => false],
            ],
            'rows'         => $rows->values(),
            'total'        => $starts->total(),
            'per_page'     => $starts->perPage(),
            'current_page' => $starts->currentPage(),
            'last_page'    => $starts->lastPage(),
        ];
    }

    private function resolveErrorMessage(DomainException $e): string
    {
        return trans("errors.{$e->getMessage()}") ?: $e->getMessage();
    }
}
