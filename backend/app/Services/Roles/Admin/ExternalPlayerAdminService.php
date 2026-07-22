<?php

namespace App\Services\Roles\Admin;

use App\Contracts\Roles\Player\External\ExternalPlayerRepositoryInterface;
use App\Models\Roles\Player\External\ExternalPlayer;
use Illuminate\Database\Eloquent\Collection;

final class ExternalPlayerAdminService
{
    public function __construct(
        private readonly ExternalPlayerRepositoryInterface $repository,
    ) {}

    public function search(?string $term, ?string $status, ?string $gameId): Collection
    {
        $query = ExternalPlayer::query()
            ->with(['game:id,name', 'sellerUser:id,name,email', 'country:id,name'])
            ->latest();

        if ($status) {
            $query->where('status', $status);
        }

        if ($gameId) {
            $query->where('game_id', $gameId);
        }

        if ($term) {
            $query->where(function ($q) use ($term) {
                $q->where('phone', 'LIKE', "%{$term}%")
                    ->orWhere('first_name', 'LIKE', "%{$term}%")
                    ->orWhere('last_name', 'LIKE', "%{$term}%")
                    ->orWhere('identification', 'LIKE', "%{$term}%")
                    ->orWhere('link', $term);
            });
        }

        return $query->limit(200)->get();
    }

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
     */
    public function toggleBlock(string $playerId): ExternalPlayer
    {
        $player = ExternalPlayer::findOrFail($playerId);

        $newStatus = $player->status === 'BLOCKED' ? 'ACTIVE' : 'BLOCKED';
        $this->repository->updatePlayerStatus($playerId, $newStatus);

        return $player->fresh();
    }
}
