<?php

namespace App\Services\Roles\Admin;

use App\Contracts\Roles\Player\External\ExternalPlayerRepositoryInterface;
use App\Models\Game;
use App\Models\Roles\Player\External\ExternalPlayer;
use App\Services\Roles\Player\External\ExternalPlayerLinkSettings;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class ExternalPlayerAdminService
{
    private const EAGER_RELATIONS = [
        'game:id,name',
        'sellerUser:id,username,first_name,last_name,email',
        'country:id,name',
        'purchaseCode:id,purchase_code',
    ];

    public function __construct(
        private readonly ExternalPlayerRepositoryInterface $repository,
    ) {}

    /**
     * Paginado real (antes: ->limit(200)->get() cargaba hasta 200 filas
     * completas en memoria en cada request, sin cursor ni total real para
     * el frontend). $filters acepta: search, status, game_id, country_id,
     * seller_user_id — ver ExternalPlayerSearchRequest::filters().
     */
    public function search(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return $this->buildQuery($filters)
            ->paginate($perPage)
            ->withQueryString();
    }

    private function buildQuery(array $filters): Builder
    {
        $query = ExternalPlayer::query()
            ->with(self::EAGER_RELATIONS)
            ->latest();

        if (!empty($filters['status'])) {
            $query->where('status', $filters['status']);
        }

        if (!empty($filters['game_id'])) {
            $query->where('game_id', $filters['game_id']);
        }

        if (!empty($filters['country_id'])) {
            $query->where('country_id', $filters['country_id']);
        }

        if (!empty($filters['seller_user_id'])) {
            $query->where('seller_user_id', $filters['seller_user_id']);
        }

        /**
         * Filtro por texto libre del vendedor (nombre/email) — antes el
         * backend solo aceptaba seller_user_id exacto, sin exponerse en la
         * UI porque el admin no conoce esos IDs de memoria.
         */
        if (!empty($filters['seller_search'])) {
            $term = $filters['seller_search'];
            $query->whereHas('sellerUser', function (Builder $q) use ($term) {
                $q->where('first_name', 'LIKE', "%{$term}%")
                    ->orWhere('last_name', 'LIKE', "%{$term}%")
                    ->orWhere('email', 'LIKE', "%{$term}%")
                    ->orWhere('username', 'LIKE', "%{$term}%");
            });
        }

        if (!empty($filters['search'])) {
            $term = $filters['search'];
            $query->where(function (Builder $q) use ($term) {
                $q->where('phone', 'LIKE', "%{$term}%")
                    ->orWhere('first_name', 'LIKE', "%{$term}%")
                    ->orWhere('last_name', 'LIKE', "%{$term}%")
                    ->orWhere('identification', 'LIKE', "%{$term}%")
                    ->orWhere('link', $term);
            });
        }

        return $query;
    }

    /**
     * Cursor sobre TODOS los resultados que coincidan con los filtros (sin
     * paginar) — usado por ExternalPlayerAdminController::export() para
     * generar el CSV completo sin materializar la colección entera en
     * memoria como haría ->get().
     *
     * @return \Illuminate\Support\LazyCollection<int, ExternalPlayer>
     */
    public function searchForExport(array $filters): \Illuminate\Support\LazyCollection
    {
        return $this->buildQuery($filters)->cursor();
    }

    /**
     * Catálogo de juegos para el selector de filtro — un jugador externo no
     * es exclusivo de Bingo, cualquier juego con venta asistida por vendedor
     * puede generar registros en external_players (game_id ya lo soporta a
     * nivel de schema, solo faltaba exponerse en el panel).
     */
    public function availableGames(): Collection
    {
        return Game::query()->where('status', 'ACTIVE')->orderBy('name')->get(['id', 'name']);
    }

    /**
     * Antes: 4 (u 8, si se filtraba por juego) queries COUNT separadas —
     * una por cada estado más el total. Colapsado en una sola query con
     * SUM/CASE, delegado a ExternalPlayerRepository::getPlayerStatistics().
     */
    public function statistics(?string $gameId = null): array
    {
        return $this->repository->getPlayerStatistics($gameId);
    }

    /**
     * BLOCKED es el único estado que un admin fija manualmente (mal uso,
     * fraude sospechado); ACTIVE/INACTIVE los gestiona el flujo de venta
     * automáticamente (ver PlayerLinkTrait). Alternar aquí solo cubre
     * ACTIVE <-> BLOCKED — no reactivamos un INACTIVE por expiración desde
     * el panel, eso requeriría además extender expiration_date, que está
     * fuera del alcance de un simple toggle de estado.
     *
     * updatePlayerStatus() ya valida la existencia vía el UPDATE mismo
     * (retorna false si no afectó filas) — evita el find-then-update de
     * dos queries que tenía la versión anterior (findOrFail + fresh()).
     */
    public function toggleBlock(string $playerId): ExternalPlayer
    {
        $current = $this->repository->getValueById($playerId, 'status');

        if ($current === null) {
            throw (new \Illuminate\Database\Eloquent\ModelNotFoundException())
                ->setModel(ExternalPlayer::class, [$playerId]);
        }

        $newStatus = $current === 'BLOCKED' ? 'ACTIVE' : 'BLOCKED';

        if (!$this->repository->updatePlayerStatus($playerId, $newStatus)) {
            throw new \RuntimeException('No se pudo actualizar el estado del jugador externo.');
        }

        return ExternalPlayer::with(self::EAGER_RELATIONS)->findOrFail($playerId);
    }

    /**
     * Reemplaza el link vigente por uno nuevo y reinicia la expiración — para
     * cuando un link se compartió por error o se sospecha filtrado, sin tener
     * que bloquear al jugador entero (antes la única acción disponible era
     * BLOCKED, que revoca acceso completo en vez de solo invalidar el link).
     */
    public function regenerateLink(string $playerId): ExternalPlayer
    {
        $player = $this->repository->regenerateLink($playerId, ExternalPlayerLinkSettings::expirationDays());

        if (!$player) {
            throw (new \Illuminate\Database\Eloquent\ModelNotFoundException())
                ->setModel(ExternalPlayer::class, [$playerId]);
        }

        return $player->load(self::EAGER_RELATIONS);
    }

    /**
     * Reactiva manualmente un jugador INACTIVE (expirado) extendiendo su
     * expiration_date — antes el panel solo permitía alternar ACTIVE/BLOCKED,
     * sin forma de recuperar un jugador que expiró por tiempo (ver
     * toggleBlock() arriba). No aplica a BLOCKED: ese estado se revierte
     * exclusivamente con toggleBlock(), es una decisión deliberada del admin,
     * no una expiración por tiempo.
     */
    public function reactivate(string $playerId): ExternalPlayer
    {
        $current = $this->repository->getValueById($playerId, 'status');

        if ($current === null) {
            throw (new \Illuminate\Database\Eloquent\ModelNotFoundException())
                ->setModel(ExternalPlayer::class, [$playerId]);
        }

        if ($current !== 'INACTIVE') {
            throw new \RuntimeException('Solo se puede reactivar un jugador externo expirado (INACTIVE).');
        }

        $player = $this->repository->regenerateLink($playerId, ExternalPlayerLinkSettings::expirationDays());
        $this->repository->updatePlayerStatus($playerId, 'ACTIVE');

        return $player->fresh(self::EAGER_RELATIONS);
    }
}
