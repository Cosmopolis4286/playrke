<?php

namespace App\Services\Roles\Admin;

use App\Contracts\Roles\Player\External\ExternalPlayerRepositoryInterface;
use App\Models\Game;
use App\Models\Roles\Player\External\ExternalPlayer;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;

final class ExternalPlayerAdminService
{
    private const EAGER_RELATIONS = [
        'game:id,name',
        'sellerUser:id,username,first_name,last_name,email',
        'country:id,name',
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
}
