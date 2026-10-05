<?php

namespace App\Services\Roles\Admin;

use App\Contracts\Roles\Commerce\CommerceRepositoryInterface;
use App\Contracts\Roles\Player\External\ExternalPlayerRepositoryInterface;
use App\Contracts\Roles\Subsidiary\SubsidiaryRepositoryInterface;
use App\Contracts\UserRepositoryInterface;
use App\Domains\Games\Bingo\Models\Card\CardPayment\BingoCardPayment;
use App\Domains\Games\Bingo\Models\Card\CardPrize\BingoCardPrizePayment;
use App\Models\Audit;
use App\Models\Game;
use App\Models\Role;
use App\Models\Roles\Player\External\ExternalPlayer;
use App\Models\Roles\Seller;
use App\Models\User;
use App\Services\Roles\Player\External\ExternalPlayerLinkSettings;
use App\Services\Roles\RoleUserService;
use App\Services\Security\UserContextService;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

final class ExternalPlayerAdminService
{
    /** Preview embebido en getPlayerDetail() — "Cargar más" usa *History() paginado con este mismo tamaño de página. */
    private const RECENT_ACTIVITY_LIMIT = 10;
    private const ACTIVITY_HISTORY_PAGE_SIZE = 15;

    private const EAGER_RELATIONS = [
        'game:id,name',
        'sellerUser:id,username,first_name,last_name,email',
        'country:id,name',
        'purchaseCode:id,purchase_code',
        'convertedUser:id,username,email',
        // Jerarquía del vendedor (Seller → Subsidiary → Commerce, o Seller →
        // Commerce directo) — ExternalPlayer no tiene FK directa a Commerce,
        // se resuelve por esta cadena. Ver ExternalPlayer::getCommerceAttribute()/
        // getSubsidiaryAttribute() para cómo se aplana a { commerce, subsidiary }
        // en la respuesta serializada.
        'seller:id,user_id,name,subsidiary_id,manager_user_id',
        'seller.subsidiary:id,name,commerce_id',
        'seller.subsidiary.commerce:id,name',
        'seller.managerCommerce:id,user_id,name',
    ];

    public function __construct(
        private readonly ExternalPlayerRepositoryInterface $repository,
        private readonly UserRepositoryInterface $userRepository,
        private readonly RoleUserService $roleUserService,
        private readonly UserContextService $userContext,
        private readonly CommerceRepositoryInterface $commerceRepository,
        private readonly SubsidiaryRepositoryInterface $subsidiaryRepository,
    ) {}

    /**
     * Árbol de user_id de Sellers propios de un Commerce (directos vía
     * manager_user_id + los que cuelgan de cualquiera de sus Subsidiaries) —
     * ExternalPlayer no tiene FK directa a Commerce, solo a seller_user_id,
     * así que hay que resolver el árbol completo para escopar por comercio.
     *
     * @return string[]
     */
    private function ownSellerUserIds(int $commerceUserId): array
    {
        $commerce = $this->commerceRepository->getByUserId($commerceUserId, ['id']);
        if (!$commerce) {
            return [];
        }

        $subsidiaryIds = $this->subsidiaryRepository->getByCommerce($commerce->id)->pluck('id');

        return Seller::query()
            ->where('manager_user_id', $commerceUserId)
            ->orWhereIn('subsidiary_id', $subsidiaryIds)
            ->pluck('user_id')
            ->map(fn ($id) => (string) $id)
            ->all();
    }

    /**
     * Resuelve el rol activo (superadmin normalizado a admin) y el user_id
     * del actor autenticado — punto único usado por applyOwnershipFilter()
     * y statistics() para que ambos apliquen el mismo criterio de scoping.
     *
     * @return array{0: string, 1: int}
     */
    private function resolveActorRole(): array
    {
        $userId = $this->userContext->resolveUserId();
        $role = $this->roleUserService->getActiveUserRolePrefix($userId);
        if ($role === 'superadmin') {
            $role = 'admin';
        }
        return [$role, $userId];
    }

    /**
     * user_id de los Sellers propios de la Subsidiary indicada — usado por
     * el filtro subsidiary_id (tanto para admin filtrando libremente como
     * para un commerce acotando dentro de su propia agencia), y también
     * para escopar a un actor 'subsidiary' sobre SU PROPIA agencia.
     *
     * @return string[]
     */
    private function subsidiarySellerUserIds(string $subsidiaryId): array
    {
        return Seller::query()
            ->where('subsidiary_id', $subsidiaryId)
            ->pluck('user_id')
            ->map(fn ($id) => (string) $id)
            ->all();
    }

    /**
     * Resuelve el id de la Subsidiary propia de un usuario 'subsidiary' —
     * null si no tiene un registro Subsidiary asociado (inconsistencia de
     * datos), en cuyo caso el caller debe tratarlo como "sin árbol propio"
     * (cero resultados, nunca "todo").
     */
    private function ownSubsidiaryId(int $subsidiaryUserId): ?string
    {
        return $this->subsidiaryRepository->getByUserId($subsidiaryUserId, ['id'])?->id;
    }

    /**
     * Escopa los filtros al árbol de negocio del actor autenticado —
     * admin/superadmin (ya normalizados a 'admin' por resolveActorRole())
     * son los ÚNICOS sin restricción; cualquier otro rol con el permiso
     * `external-players.*` (commerce, subsidiary) queda SIEMPRE acotado a
     * su propio árbol, nunca "todo por defecto".
     *
     * Antes el chequeo era `if ($role !== 'commerce')` — una exclusión de
     * un solo rol, no una whitelist. Un actor 'subsidiary' (que ya tiene
     * el permiso `external-players.view`, idéntico al de commerce salvo
     * subsidiaries.*) caía en la rama "sin restricción" y veía TODOS los
     * jugadores externos del sistema, de cualquier comercio/agencia — fuga
     * de datos real, corregida aquí invirtiendo la lógica a whitelist.
     *
     * También traduce los filtros de jerarquía (commerce_id/subsidiary_id,
     * ver ExternalPlayerSearchRequest::filters()) a la misma condición
     * seller_user_id_in que ya entiende buildQuery() — un commerce/subsidiary
     * que además filtra por esos campos queda intersectado con su propio
     * árbol (no puede ampliar su alcance filtrando por jerarquía ajena).
     */
    private function applyOwnershipFilter(array $filters): array
    {
        [$role, $userId] = $this->resolveActorRole();

        $sellerIdsFromHierarchyFilter = null;

        if (!empty($filters['subsidiary_id'])) {
            $sellerIdsFromHierarchyFilter = $this->subsidiarySellerUserIds($filters['subsidiary_id']);
        } elseif (!empty($filters['commerce_id'])) {
            $commerceUserId = $this->commerceRepository->getValueById($filters['commerce_id'], 'user_id');
            $sellerIdsFromHierarchyFilter = $commerceUserId ? $this->ownSellerUserIds((int) $commerceUserId) : [];
        }

        unset($filters['commerce_id'], $filters['subsidiary_id']);

        if ($role === 'admin') {
            if ($sellerIdsFromHierarchyFilter !== null) {
                $filters['seller_user_id_in'] = $sellerIdsFromHierarchyFilter ?: ['0'];
            }
            return $filters;
        }

        $ownSellerIds = match ($role) {
            'commerce'   => $this->ownSellerUserIds($userId),
            'subsidiary' => ($subsidiaryId = $this->ownSubsidiaryId($userId)) ? $this->subsidiarySellerUserIds($subsidiaryId) : [],
            // El vendedor ve SOLO los compradores que captó él mismo. Antes
            // caía en default (cero resultados): tiene external-players.view
            // y entraba a la pantalla, pero la lista salía siempre vacía.
            'seller'     => [(string) $userId],
            default      => [], // cualquier otro rol con el permiso pero sin jerarquía propia: cero resultados, nunca "todo"
        };

        $effectiveSellerIds = $sellerIdsFromHierarchyFilter !== null
            ? array_values(array_intersect($ownSellerIds, $sellerIdsFromHierarchyFilter))
            : $ownSellerIds;

        $filters['seller_user_id_in'] = $effectiveSellerIds ?: ['0']; // fuerza cero resultados si no tiene sellers

        return $filters;
    }

    /**
     * Paginado real (antes: ->limit(200)->get() cargaba hasta 200 filas
     * completas en memoria en cada request, sin cursor ni total real para
     * el frontend). $filters acepta: search, status, game_id, country_id,
     * seller_user_id — ver ExternalPlayerSearchRequest::filters().
     */
    public function search(array $filters, int $perPage = 20): LengthAwarePaginator
    {
        return $this->buildQuery($this->applyOwnershipFilter($filters))
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

        if (!empty($filters['seller_user_id_in'])) {
            $query->whereIn('seller_user_id', $filters['seller_user_id_in']);
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
        return $this->buildQuery($this->applyOwnershipFilter($filters))->cursor();
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
     *
     * $filters (opcional) acepta las mismas claves de jerarquía que
     * search() (commerce_id/subsidiary_id) — sin pasar por
     * applyOwnershipFilter() aquí, un commerce vería el conteo TOTAL del
     * sistema en vez del suyo (bug real: las stats no aplicaban ningún
     * scoping antes de este fix, pese a que search() sí lo hacía).
     */
    public function statistics(?string $gameId = null, array $filters = []): array
    {
        $scopedFilters = $this->applyOwnershipFilter($filters);

        return $this->repository->getPlayerStatistics($gameId, $scopedFilters['seller_user_id_in'] ?? null);
    }

    /**
     * Catálogo de comercios/agencias para los selects de filtro por
     * jerarquía — acotado por rol, mismo criterio que applyOwnershipFilter():
     * admin ve todos los comercios y todas las agencias; commerce ve solo su
     * propio comercio y SUS PROPIAS agencias (nunca las de otro comercio);
     * subsidiary ve solo SU PROPIA agencia (sin comercios — no le sirve
     * filtrar por comercio, ya está acotado a su propia agencia siempre).
     *
     * @return array{commerces: array<int, array{id: string, name: string}>, subsidiaries: array<int, array{id: string, name: string, commerce_id: string}>}
     */
    public function hierarchyOptions(): array
    {
        [$role, $userId] = $this->resolveActorRole();

        if ($role === 'admin') {
            $commerces = \App\Models\Roles\Commerce::query()->orderBy('name')->get(['id', 'name']);
            $subsidiaries = \App\Models\Roles\Subsidiary::query()->orderBy('name')->get(['id', 'name', 'commerce_id']);
        } elseif ($role === 'subsidiary') {
            $subsidiary = \App\Models\Roles\Subsidiary::where('user_id', $userId)->first(['id', 'name', 'commerce_id']);
            $commerces = collect();
            $subsidiaries = $subsidiary ? collect([$subsidiary]) : collect();
        } else {
            $commerce = $this->commerceRepository->getByUserId($userId, ['id', 'name']);
            $commerces = $commerce ? collect([$commerce]) : collect();
            $subsidiaries = $commerce
                ? $this->subsidiaryRepository->getByCommerce($commerce->id)->sortBy('name')->values()
                : collect();
        }

        return [
            'commerces' => $commerces->map(fn ($c) => ['id' => (string) $c->id, 'name' => $c->name])->values()->all(),
            'subsidiaries' => $subsidiaries->map(fn ($s) => [
                'id' => (string) $s->id,
                'name' => $s->name,
                'commerce_id' => (string) $s->commerce_id,
            ])->values()->all(),
        ];
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
     * Detalle consolidado de un jugador externo para el modal de ficha —
     * datos básicos (ya cargados vía EAGER_RELATIONS) + preview de las 3
     * fuentes de "qué hizo"/"qué le pasó": compras, premios, y auditoría de
     * cambios de estado (bloqueo, regeneración de link, conversión a
     * usuario — capturados automáticamente por el trait Auditable en
     * ExternalPlayer). Mismo patrón que AdminUserService::getUserDetail().
     */
    public function getPlayerDetail(ExternalPlayer $player): array
    {
        $player->load(self::EAGER_RELATIONS);

        $recentPurchases = $this->recentPurchases($player);
        $recentPrizes    = $this->recentPrizes($player);
        $recentAudits    = $this->recentAudits($player);

        return [
            ...$player->toArray(),
            'recent_purchases'    => $recentPurchases,
            'recent_prizes'       => $recentPrizes,
            'recent_audits'       => $recentAudits,
            'has_more_purchases'  => $recentPurchases->count() >= self::RECENT_ACTIVITY_LIMIT
                && BingoCardPayment::byExternalPlayer($player->id)->count() > self::RECENT_ACTIVITY_LIMIT,
            'has_more_prizes'     => $recentPrizes->count() >= self::RECENT_ACTIVITY_LIMIT
                && BingoCardPrizePayment::byExternalPlayer($player->id)->count() > self::RECENT_ACTIVITY_LIMIT,
            'has_more_audits'     => $recentAudits->count() >= self::RECENT_ACTIVITY_LIMIT
                && Audit::ofAuditable(ExternalPlayer::class, $player->id)->count() > self::RECENT_ACTIVITY_LIMIT,
        ];
    }

    /**
     * \Illuminate\Support\Collection (no Eloquent\Collection, que es el tipo
     * de BingoCardPayment::get()) — ->map() siempre degrada a la colección
     * base de Support en vez de preservar el tipo de modelo, porque el
     * resultado mapeado (un array plano) ya no es una colección de modelos.
     * El type hint equivocado reventaba en runtime (TypeError) en cuanto
     * había al menos una fila real que mapear.
     */
    private function recentPurchases(ExternalPlayer $player): \Illuminate\Support\Collection
    {
        return BingoCardPayment::byExternalPlayer($player->id)
            ->with('room:id,uuid_code')
            ->latest('created_at')
            ->limit(self::RECENT_ACTIVITY_LIMIT)
            ->get()
            ->map(fn (BingoCardPayment $payment) => $this->mapPurchaseRow($payment));
    }

    private function recentPrizes(ExternalPlayer $player): \Illuminate\Support\Collection
    {
        return BingoCardPrizePayment::byExternalPlayer($player->id)
            ->latest('created_at')
            ->limit(self::RECENT_ACTIVITY_LIMIT)
            ->get()
            ->map(fn (BingoCardPrizePayment $payment) => $this->mapPrizeRow($payment));
    }

    private function recentAudits(ExternalPlayer $player): \Illuminate\Support\Collection
    {
        return Audit::ofAuditable(ExternalPlayer::class, $player->id)
            ->with('user:id,first_name,last_name,email')
            ->latest('created_at')
            ->limit(self::RECENT_ACTIVITY_LIMIT)
            ->get()
            ->map(fn (Audit $audit) => $this->mapAuditRow($audit));
    }

    /**
     * Historial de compras paginado — botón "Cargar más" cuando el preview
     * de getPlayerDetail() no basta. Mismo patrón que
     * AdminUserService::getWalletMovementHistory().
     */
    public function getPlayerPurchaseHistory(ExternalPlayer $player, int $page = 1): array
    {
        $paginator = BingoCardPayment::byExternalPlayer($player->id)
            ->with('room:id,uuid_code')
            ->latest('created_at')
            ->paginate(self::ACTIVITY_HISTORY_PAGE_SIZE, ['*'], 'page', $page);

        return [
            'rows'         => $paginator->getCollection()->map(fn (BingoCardPayment $p) => $this->mapPurchaseRow($p))->values(),
            'has_more'     => $paginator->hasMorePages(),
            'current_page' => $paginator->currentPage(),
        ];
    }

    public function getPlayerPrizeHistory(ExternalPlayer $player, int $page = 1): array
    {
        $paginator = BingoCardPrizePayment::byExternalPlayer($player->id)
            ->latest('created_at')
            ->paginate(self::ACTIVITY_HISTORY_PAGE_SIZE, ['*'], 'page', $page);

        return [
            'rows'         => $paginator->getCollection()->map(fn (BingoCardPrizePayment $p) => $this->mapPrizeRow($p))->values(),
            'has_more'     => $paginator->hasMorePages(),
            'current_page' => $paginator->currentPage(),
        ];
    }

    public function getPlayerAuditHistory(ExternalPlayer $player, int $page = 1): array
    {
        $paginator = Audit::ofAuditable(ExternalPlayer::class, $player->id)
            ->with('user:id,first_name,last_name,email')
            ->latest('created_at')
            ->paginate(self::ACTIVITY_HISTORY_PAGE_SIZE, ['*'], 'page', $page);

        return [
            'rows'         => $paginator->getCollection()->map(fn (Audit $a) => $this->mapAuditRow($a))->values(),
            'has_more'     => $paginator->hasMorePages(),
            'current_page' => $paginator->currentPage(),
        ];
    }

    private function mapPurchaseRow(BingoCardPayment $payment): array
    {
        return [
            'id'     => (string) $payment->id,
            'date'   => $payment->created_at?->format('d/m/Y H:i:s'),
            'amount' => (float) $payment->payment_amount,
            'status' => $payment->payment_status,
            'room'   => $payment->room?->uuid_code,
        ];
    }

    private function mapPrizeRow(BingoCardPrizePayment $payment): array
    {
        return [
            'id'     => (string) $payment->id,
            'date'   => $payment->created_at?->format('d/m/Y H:i:s'),
            'amount' => (float) $payment->payment_amount,
            'status' => $payment->payment_status,
            'method' => $payment->payment_method_description,
        ];
    }

    private function mapAuditRow(Audit $audit): array
    {
        return [
            'id'    => (string) $audit->id,
            'date'  => $audit->created_at?->format('d/m/Y H:i:s'),
            'actor' => $audit->user?->full_name ?? 'Sistema',
            'event' => $audit->event,
        ];
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
