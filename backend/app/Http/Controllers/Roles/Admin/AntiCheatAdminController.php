<?php

namespace App\Http\Controllers\Roles\Admin;

use App\Http\Controllers\Controller;
use App\Models\AntiCheat\AntiCheatFlag;
use App\Models\AntiCheat\UserSuspension;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

/**
 * Gestión admin de suspensiones/flags anti-cheat.
 *
 * AntiCheatService (App\Services\Games\AntiCheatService) ya detecta señales y
 * auto-suspende jugadores reincidentes; UserSuspension::resolve() ya sabe
 * reactivar un usuario con motivo/admin/notas. Este controlador es lo que
 * faltaba: el punto de entrada admin para ver flags, resolver una suspensión
 * activa, o suspender manualmente (antes solo existía la vía automática).
 */
final class AntiCheatAdminController extends Controller
{
    private const FLAGS_PAGE_SIZE = 20;

    public function flagsForUser(User $user, Request $request): JsonResponse
    {
        return $this->handleServiceJson(function () use ($user, $request) {
            $page = (int) $request->get('page', 1);

            $paginator = AntiCheatFlag::forUser((string) $user->id)
                ->latest('created_at')
                ->paginate(self::FLAGS_PAGE_SIZE, ['*'], 'page', $page);

            return [
                'data' => [
                    'rows' => $paginator->getCollection()->map(fn (AntiCheatFlag $flag) => [
                        'id'          => (string) $flag->id,
                        'game_slug'   => $flag->game_slug,
                        'signal_type' => $flag->signal_type->value,
                        'severity'    => $flag->severity,
                        'triggered_suspension' => $flag->triggered_suspension,
                        'created_at'  => $flag->created_at?->format('d/m/Y H:i:s'),
                    ])->values(),
                    'has_more'     => $paginator->hasMorePages(),
                    'current_page' => $paginator->currentPage(),
                ],
            ];
        });
    }

    /**
     * Reactiva un jugador suspendido — UserSuspension::resolve() ya
     * contenía toda la lógica (registrar admin/notas, revertir status a
     * ACTIVE si no queda otra suspensión activa), solo faltaba este endpoint
     * que la invocara.
     */
    public function resolveSuspension(UserSuspension $suspension, Request $request): JsonResponse
    {
        $request->validate(['notes' => 'required|string|max:500']);

        return $this->handleServiceJson(function () use ($suspension, $request) {
            $suspension->resolve(Auth::user(), $request->input('notes'));

            return ['message' => 'Suspensión resuelta. El jugador vuelve a estar activo.'];
        });
    }

    /**
     * Suspensión manual — hasta ahora la única forma de suspender a un
     * jugador era automática (AntiCheatService::evaluateAutoSuspension());
     * un admin no tenía forma de suspender directamente por fuera del
     * sistema anti-cheat (ej. reporte de un usuario, fraude detectado por
     * soporte).
     */
    public function suspendManually(User $user, Request $request): JsonResponse
    {
        $request->validate(['reason' => 'required|string|max:500']);

        return $this->handleServiceJson(function () use ($user, $request) {
            if ($user->status === 'SUSPENDED') {
                return ['message' => 'El jugador ya está suspendido.'];
            }

            UserSuspension::create([
                'user_id'              => $user->id,
                'origin'                => UserSuspension::ORIGIN_MANUAL_ADMIN,
                'reason'                => $request->input('reason'),
                'suspended_by_user_id'  => Auth::id(),
            ]);

            $user->update(['status' => 'SUSPENDED']);

            return ['message' => 'Jugador suspendido.'];
        });
    }
}
