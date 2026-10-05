<?php

namespace App\Http\Controllers\Roles\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Roles\Admin\ExternalPlayer\ExternalPlayerSearchRequest;
use App\Models\Roles\Player\External\ExternalPlayer;
use App\Services\Roles\Admin\ExternalPlayerAdminService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Inertia\Inertia;
use Inertia\Response;

/**
 * Panel admin de Jugadores Externos (compradores sin cuenta en la
 * plataforma, captados por un vendedor — no exclusivo de Bingo, ver
 * App\Services\Roles\Player\External\ExternalPlayerLinkService). Protegido por middleware
 * `role:admin,superadmin` en la ruta y por ExternalPlayerPolicy.
 */
final class ExternalPlayerAdminController extends Controller
{
    public function __construct(
        protected readonly ExternalPlayerAdminService $service,
    ) {}

    public function index(ExternalPlayerSearchRequest $request): Response
    {
        $this->authorize('viewAny', ExternalPlayer::class);

        $filters = $request->filters();

        return Inertia::render('Roles/Admin/ExternalPlayer/Index', [
            'players'    => $this->service->search($filters, $request->perPage()),
            // Sin filtros de estado/comercio/agencia: las tarjetas de
            // resumen reflejan el universo completo accesible al actor
            // (ya escopado por rol dentro de statistics()), no la búsqueda
            // puntual — mismo criterio que ya tenía este índice.
            'statistics' => $this->service->statistics($filters['game_id'] ?? null, [
                'commerce_id' => $filters['commerce_id'] ?? null,
                'subsidiary_id' => $filters['subsidiary_id'] ?? null,
            ]),
            'games'      => $this->service->availableGames(),
            'hierarchy'  => $this->service->hierarchyOptions(),
        ]);
    }

    public function fetchList(ExternalPlayerSearchRequest $request): JsonResponse
    {
        $this->authorize('viewAny', ExternalPlayer::class);

        $filters = $request->filters();

        return response()->json([
            'players'    => $this->service->search($filters, $request->perPage()),
            'statistics' => $this->service->statistics($filters['game_id'] ?? null, [
                'commerce_id' => $filters['commerce_id'] ?? null,
                'subsidiary_id' => $filters['subsidiary_id'] ?? null,
            ]),
        ]);
    }

    /**
     * Exporta a CSV todos los jugadores externos que coincidan con los
     * filtros actuales (sin límite de paginación) — usa cursor() vía el
     * service para no materializar el resultado completo en memoria.
     */
    public function export(ExternalPlayerSearchRequest $request): StreamedResponse
    {
        $this->authorize('export', ExternalPlayer::class);

        $filters = $request->filters();

        return response()->streamDownload(function () use ($filters) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, ['Nombre', 'Apellido', 'Teléfono', 'Identificación', 'Estado', 'Juego', 'País', 'Vendedor', 'Agencia', 'Comercio', 'Expira', 'Link', 'Código de compra']);

            foreach ($this->service->searchForExport($filters) as $player) {
                fputcsv($handle, [
                    $player->first_name,
                    $player->last_name,
                    $player->phone,
                    $player->identification,
                    $player->status,
                    $player->game?->name,
                    $player->country?->name,
                    $player->sellerUser ? "{$player->sellerUser->first_name} {$player->sellerUser->last_name}" : null,
                    $player->subsidiary['name'] ?? null,
                    $player->commerce['name'] ?? null,
                    $player->expiration_date?->format('Y-m-d'),
                    $player->link,
                    $player->purchaseCode?->purchase_code,
                ]);
            }

            fclose($handle);
        }, 'jugadores-externos-' . now()->format('Y-m-d-His') . '.csv', [
            'Content-Type' => 'text/csv',
        ]);
    }

    /**
     * Ficha de detalle de un jugador externo — datos básicos + preview de
     * compras/premios/auditoría. Autorizado vía ExternalPlayerPolicy::view()
     * (ya escopa por ownership: admin ve todos, commerce/subsidiary/seller
     * solo los de su propio árbol).
     */
    public function show(string $externalPlayer): JsonResponse
    {
        $player = ExternalPlayer::findOrFail($externalPlayer);
        $this->authorize('view', $player);

        return response()->json([
            'player' => $this->service->getPlayerDetail($player),
        ]);
    }

    /**
     * GET /admin/external-players/{externalPlayer}/purchase-history?page=1
     * "Cargar más" del bloque de compras del modal de ficha.
     */
    public function purchaseHistory(Request $request, string $externalPlayer): JsonResponse
    {
        $player = ExternalPlayer::findOrFail($externalPlayer);
        $this->authorize('view', $player);

        return response()->json(
            $this->service->getPlayerPurchaseHistory($player, (int) $request->query('page', 1))
        );
    }

    /**
     * GET /admin/external-players/{externalPlayer}/prize-history?page=1
     */
    public function prizeHistory(Request $request, string $externalPlayer): JsonResponse
    {
        $player = ExternalPlayer::findOrFail($externalPlayer);
        $this->authorize('view', $player);

        return response()->json(
            $this->service->getPlayerPrizeHistory($player, (int) $request->query('page', 1))
        );
    }

    /**
     * GET /admin/external-players/{externalPlayer}/audit-history?page=1
     */
    public function auditHistory(Request $request, string $externalPlayer): JsonResponse
    {
        $player = ExternalPlayer::findOrFail($externalPlayer);
        $this->authorize('view', $player);

        return response()->json(
            $this->service->getPlayerAuditHistory($player, (int) $request->query('page', 1))
        );
    }

    public function toggleBlock(string $externalPlayer): JsonResponse
    {
        $player = ExternalPlayer::findOrFail($externalPlayer);
        $this->authorize('toggleBlock', $player);

        $updated = $this->service->toggleBlock($externalPlayer);

        return response()->json([
            'message' => 'Jugador actualizado correctamente',
            'player'  => $updated,
        ]);
    }

    /**
     * Regenera el link vigente (para links filtrados/compartidos por error)
     * sin bloquear al jugador entero.
     */
    public function regenerateLink(string $externalPlayer): JsonResponse
    {
        $player = ExternalPlayer::findOrFail($externalPlayer);
        $this->authorize('update', $player);

        $updated = $this->service->regenerateLink($externalPlayer);

        return response()->json([
            'message' => 'Link regenerado correctamente',
            'player'  => $updated,
        ]);
    }

    /**
     * Reactiva manualmente un jugador INACTIVE (expirado por tiempo).
     */
    public function reactivate(string $externalPlayer): JsonResponse
    {
        $player = ExternalPlayer::findOrFail($externalPlayer);
        $this->authorize('update', $player);

        $updated = $this->service->reactivate($externalPlayer);

        return response()->json([
            'message' => 'Jugador reactivado correctamente',
            'player'  => $updated,
        ]);
    }

    /**
     * Convierte un jugador externo en un usuario registrado con rol player.
     * Devuelve la contraseña temporal una sola vez (no se puede recuperar
     * después) — mismo patrón que resetPassword de AdminUserController.
     */
    public function convertToUser(string $externalPlayer): JsonResponse
    {
        $player = ExternalPlayer::findOrFail($externalPlayer);
        $this->authorize('update', $player);

        $result = $this->service->convertToUser($externalPlayer);

        return response()->json([
            'message'             => 'Jugador convertido a usuario registrado correctamente',
            'user'                => $result['user'],
            'temporary_password'  => $result['temporary_password'],
        ]);
    }
}
