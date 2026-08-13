<?php

namespace App\Services\Roles\Admin;

use App\Contracts\Roles\Player\External\ExternalPlayerRepositoryInterface;
use App\Contracts\UserRepositoryInterface;
use App\Models\Game;
use App\Models\Role;
use App\Models\Roles\Player\External\ExternalPlayer;
use App\Models\User;
use App\Services\Roles\Player\External\ExternalPlayerLinkSettings;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class ExternalPlayerAdminService
{
    private const EAGER_RELATIONS = [
        'game:id,name',
        'sellerUser:id,username,first_name,last_name,email',
        'country:id,name',
        'purchaseCode:id,purchase_code',
        'convertedUser:id,username,email',
    ];

    public function __construct(
        private readonly ExternalPlayerRepositoryInterface $repository,
        private readonly UserRepositoryInterface $userRepository,
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
     * automáticamente (ver ExternalPlayerLinkService). Alternar aquí solo cubre
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

    /**
     * Convierte un jugador externo (sin cuenta) en un User real con rol
     * 'player' — alcance mínimo: no migra historial de partidas ni compras
     * previas, solo crea la cuenta y vincula el registro para trazabilidad
     * (ExternalPlayer.converted_user_id). El admin puede cargarle saldo o
     * completar sus datos después desde /admin/users si corresponde.
     *
     * Username/email se generan a partir del teléfono porque ExternalPlayer
     * no captura email; ambos son placeholders editables — el jugador (o el
     * admin en su nombre) los actualiza tras la conversión.
     *
     * @return array{user: User, temporary_password: string}
     */
    public function convertToUser(string $playerId): array
    {
        $player = ExternalPlayer::with('country')->findOrFail($playerId);

        if ($player->converted_user_id) {
            throw new \RuntimeException('Este jugador externo ya fue convertido a usuario registrado.');
        }

        $playerRole = Role::where('name', 'player')->where('guard_name', 'web')->first();

        if (!$playerRole) {
            throw new \RuntimeException('El rol "player" no existe en el sistema.');
        }

        return DB::transaction(function () use ($player, $playerRole) {
            $temporaryPassword = Str::random(12);
            $baseHandle = 'ext_' . preg_replace('/\D/', '', $player->phone);

            $user = User::create([
                'username' => $this->uniqueHandle($baseHandle, 'username'),
                'email' => $this->uniqueHandle($baseHandle, 'email') . '@pendiente.playrke.com',
                'first_name' => $player->first_name,
                'last_name' => $player->last_name,
                'phone_number' => $player->phone,
                'country_id' => $player->country_id,
                'password' => Hash::make($temporaryPassword),
                'status' => 'ACTIVE',
                'referral_code' => $this->userRepository->generateUniqueReferralCode(),
            ]);

            $user->roles()->attach($playerRole->id);

            $player->update(['converted_user_id' => $user->id]);

            return ['user' => $user, 'temporary_password' => $temporaryPassword];
        });
    }

    /**
     * Genera un handle único agregando un sufijo aleatorio si hay colisión —
     * poco probable con el teléfono como base, pero dos jugadores externos
     * podrían compartir prefijo si el teléfono se registró con formato distinto.
     */
    private function uniqueHandle(string $base, string $column): string
    {
        $handle = $base;
        $suffix = 0;

        while (User::where($column, $column === 'email' ? "{$handle}@pendiente.playrke.com" : $handle)->exists()) {
            $suffix++;
            $handle = "{$base}_{$suffix}";
        }

        return $handle;
    }
}
