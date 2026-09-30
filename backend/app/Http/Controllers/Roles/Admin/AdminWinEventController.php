<?php

namespace App\Http\Controllers\Roles\Admin;

use App\DTOs\Roles\Admin\WinEvent\AdminWinEventDTO;
use App\Domains\Platform\WinEvent\Models\WinEvent;
use App\Domains\Platform\WinEvent\Services\WinEventDetailService;
use App\Domains\Platform\WinEvent\Services\WinEventService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Roles\Admin\WinEvent\AdminWinEventRequest;
use App\Services\Security\UserContextService;
use Illuminate\Http\JsonResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Panel admin para registrar manualmente eventos de ganancia — pensado
 * para premios físicos/objetos/servicios entregados fuera de la
 * plataforma (rifas presenciales, torneos offline, etc).
 *
 * Es un log de eventos, no una entidad editable: solo expone crear, listar
 * y ver el detalle.
 */
final class AdminWinEventController extends Controller
{
    public function __construct(
        private readonly WinEventService $winEventService,
        private readonly UserContextService $userContext,
        private readonly WinEventDetailService $detailService,
    ) {}

    /**
     * Vista del panel: formulario de registro manual + listado.
     */
    public function index(): Response
    {
        return $this->handleServiceInertia(
            fn () => Inertia::render('Roles/Admin/WinEvent/Index')
        );
    }

    /**
     * Detalle completo de una ganancia (cualquier juego): datos del evento,
     * ganador sin enmascarar y las secciones que aporte el resolver del
     * juego (ver WinEventDetailResolver). Se llega desde el ticker "En vivo"
     * o desde el listado de este panel.
     */
    public function show(string $id): Response
    {
        $this->authorize('viewAny', WinEvent::class);

        $winEvent = WinEvent::find($id);

        // Un evento anunciado pero inexistente (ej. broadcast de una transacción
        // revertida) muestra un estado vacío en la propia vista, en vez del 404
        // genérico — funciona igual desde el ticker (navegación Inertia) que
        // con la URL abierta directamente.
        if (!$winEvent) {
            return Inertia::render('Roles/Admin/WinEvent/Show', ['detail' => null, 'missingId' => $id]);
        }

        $this->authorize('view', $winEvent);

        return $this->handleServiceInertia(
            fn () => Inertia::render('Roles/Admin/WinEvent/Show', [
                'detail' => $this->detailService->build($winEvent),
            ])
        );
    }

    /**
     * Registra manualmente un evento de ganancia.
     */
    public function store(AdminWinEventRequest $request): JsonResponse
    {
        $this->authorize('create', WinEvent::class);

        return $this->handleServiceJson(function () use ($request) {
            $dto = $request->toDTO(AdminWinEventDTO::class);
            $adminUserId = (string) $this->userContext->resolveUserId();

            $winEvent = $dto->prizeKind === WinEvent::PRIZE_MONEY
                ? $this->winEventService->reportMoney(
                    userId: $dto->userId,
                    winnerNameOverride: $dto->winnerName,
                    gameSlug: $dto->gameSlug,
                    gameName: $dto->gameName,
                    amountUsd: $dto->amountUsd ?? 0,
                    amountLocal: $dto->amountLocal ?? $dto->amountUsd ?? 0,
                    currencyId: $dto->currencyId,
                    sourceType: WinEvent::SOURCE_ADMIN_MANUAL,
                    reportedByUserId: $adminUserId,
                    metadata: $dto->notes ? ['notes' => $dto->notes] : null,
                )
                : $this->winEventService->reportNonMoney(
                    userId: $dto->userId,
                    winnerNameOverride: $dto->winnerName,
                    gameSlug: $dto->gameSlug,
                    gameName: $dto->gameName,
                    prizeKind: $dto->prizeKind,
                    prizeLabel: $dto->prizeLabel ?? '',
                    sourceType: WinEvent::SOURCE_ADMIN_MANUAL,
                    reportedByUserId: $adminUserId,
                    metadata: $dto->notes ? ['notes' => $dto->notes] : null,
                );

            return ['win_event_id' => (string) $winEvent->id];
        });
    }

    /**
     * Listado paginado de eventos de ganancia registrados (auditoría).
     */
    public function fetchList(): JsonResponse
    {
        $this->authorize('viewAny', WinEvent::class);

        return $this->handleServiceJson(function () {
            $events = WinEvent::query()
                ->with([
                    'user:id,first_name,last_name,username',
                    'reportedByUser:id,first_name,last_name,username',
                    'currency:id,iso_code,symbol,decimal_places',
                ])
                ->latest('created_at')
                ->paginate(20);

            return [
                'data' => $events->through(fn (WinEvent $e) => [
                    'id'                => (string) $e->id,
                    'winner_name'       => $e->resolveWinnerName(),
                    'game_name'         => $e->game_name,
                    'game_slug'         => $e->game_slug,
                    'prize_kind'        => $e->prize_kind,
                    'prize_label'       => $e->prize_label,
                    'amount_usd'        => $e->amount_usd !== null ? (float) $e->amount_usd : null,
                    'source_type'       => $e->source_type,
                    'reported_by'       => $e->reportedByUser?->full_name,
                    'created_at'        => $e->created_at?->toISOString(),
                ]),
            ];
        });
    }
}
