<?php

namespace App\Domains\Games\Bingo\Services\Game;

use App\Domains\Games\Bingo\DTOs\Game\PlayerCardPricingDTO;
use App\Domains\Games\Bingo\Models\Card\CardPrize\BingoCardPrize;
use App\Domains\Games\Bingo\Models\Draw\BingoDraw;
use App\Domains\Games\Bingo\Models\Draw\BingoDrawCode;
use App\Domains\Games\Bingo\Models\Pattern\BingoPattern;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Services\Room\Utils\CheckExpiredBingoRoomsService;
use App\Domains\Games\Bingo\ValueObjects\BingoVariant;
use App\Domains\Wallet\Models\Wallet;
use App\Exceptions\Domain\ExchangeRate\ExchangeRateException;
use App\Models\Currency;
use App\Models\Games\Common\Utils\GameStatus;
use App\Services\ExchangeRate\CurrencyExchangeRateService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class BingoGameService
{
    public function __construct(
        private readonly CheckExpiredBingoRoomsService $checkExpired,
        private readonly CurrencyExchangeRateService $currencyExchangeRate,
        private readonly BingoDrawService $drawService,
        private readonly BingoPatternPositionResolver $patternResolver,
    ) {
    }

    /**
     * [DEV] Actualizar unix_date y time de la sala a now+2 minutos.
     * Si existe un sorteo previo, lo regenera.
     * Solo debe llamarse en entorno local.
     *
     * @return array Nuevo startTime, time legible y estado de regeneración
     */
    public function devResetStartTime(BingoRoom $room) : array
    {
        $newTime = now()->addMinutes(2);
        $unixDate = $newTime->timestamp;

        $hasExistingDraw = BingoDraw::where('room_id', $room->id)->exists();
        $hasExistingCode = BingoDrawCode::where('room_id', $room->id)->exists();
        $hadData = $hasExistingDraw || $hasExistingCode;

        // Full DEV reset: clear all draw data, prizes, rounds, cards and room status
        DB::transaction(function() use ($room) {
            BingoDraw::where('room_id', $room->id)->delete();
            BingoDrawCode::where('room_id', $room->id)->delete();
            BingoCardPrize::where('room_id', $room->id)->delete();

            // Restaurar cartones ganadores a ACTIVE para que el siguiente sorteo los detecte
            \App\Domains\Games\Bingo\Models\Card\BingoCard::where('room_id', (string) $room->id)
                ->where('status', \App\Domains\Games\Bingo\Models\Card\BingoCard::STATUS_WINNER)
                ->update(['status' => \App\Domains\Games\Bingo\Models\Card\BingoCard::STATUS_ACTIVE]);

            $room->rounds()->update(['status' => 'ACTIVE']);

            // Restore room to ACTIVE so the game can be re-drawn
            $statusMap = GameStatus::codesMap(['ACTIVE', 'IN_PROGRESS']);
            $restoreId = $statusMap['ACTIVE'] ?? $statusMap['IN_PROGRESS'] ?? null;
            if ($restoreId) {
                $room->update(['status_id' => $restoreId, 'finished_at' => null]);
            }
        });

        $room->config()->update([
            'unix_date' => $unixDate,
            'date' => $newTime->toDateString(),
            'time' => $newTime->format('H:i:s'),
        ]);

        Log::info('[DEV] Sala reseteada para nuevo sorteo', [
            'room_id' => $room->id,
            'had_data' => $hadData,
        ]);

        // Emitir evento para sincronizar el tiempo con todos los clientes conectados
        broadcast(new \App\Domains\Games\Bingo\Events\RoomTimeSync($room->id, $unixDate))->toOthers();

        return [
            'startTime' => $unixDate,
            'humanTime' => $newTime->format('H:i:s'),
            'hasExistingDraw' => $hadData,
            'message' => $hadData ? 'Sorteo regenerado exitosamente' : 'Nuevo sorteo iniciado',
        ];
    }

    /**
     * Estado actual del sorteo — usado por el cliente para resincronizar
     * tras una reconexión de WebSocket (eventos perdidos mientras el socket
     * estuvo caído, ya que Reverb/Pusher no reenvía el historial).
     */
    public function getDrawState(BingoRoom $room) : array
    {
        // La clave "bingo:draw-state:draw:{roomId}" es escrita por BingoDrawService en cada
        // chip con TTL = max(interval+2, 6)s. Cache::remember() con TTL fijo de 3s sobreescribiría
        // ese TTL largo en el fallback de DB, acortando la ventana útil del cache entre chips.
        // Solución: leer con get() (no toca el TTL existente); solo en cache miss, escribir
        // con TTL corto (5s) para amortiguar tormentas de reconexión sin destruir el TTL del draw.
        $cacheKey = "bingo:draw-state:draw:{$room->id}";
        $drawnNumbers = Cache::get($cacheKey);
        if ($drawnNumbers === null) {
            $drawnNumbers = BingoDraw::where('room_id', $room->id)
                ->orderBy('time')
                ->orderBy('created_at')
                ->pluck('chip')
                ->map(fn ($chip) => (int) $chip)
                ->toArray();
            // TTL corto solo cuando no hay valor previo — el draw service lo reescribirá
            // en el siguiente chip con el TTL correcto (interval+2s).
            Cache::put($cacheKey, $drawnNumbers, 5);
        }

        // Siempre recargar status desde DB — el route binding puede tener el modelo
        // en memoria con un status anterior (IN_PROGRESS) si el sorteo terminó entre
        // la resolución del binding y esta llamada (sin el reload, roomFinished sería false).
        $room->load('status');
        $roomStatusCode = $room->status?->code ?? '';
        $roomIsFinished = strtoupper($roomStatusCode) === 'FINISHED';

        // players_progress: solo cuando quedan ≤10 fichas y el sorteo sigue activo.
        // Cacheado 3s por sala+cantidad de fichas — bajo una desconexión masiva de
        // WS, muchos clientes reconectan casi a la vez y llaman a este endpoint en
        // paralelo; sin cache, cada uno recalcula ronda+patrón+progreso de todos
        // los cartones de la sala, multiplicando carga de DB en el peor momento.
        $playersProgress = [];
        if (! $room->relationLoaded('config')) {
            $room->load('config');
        }
        $variant = $room->config?->game_variant ?? BingoVariant::BINGO_75;
        $totalChips = BingoVariant::totalChips($variant);
        if (! $roomIsFinished && count($drawnNumbers) > 0 && ($totalChips - count($drawnNumbers)) <= 10) {
            $cacheKey = "bingo:draw-state:players-progress:{$room->id}:" . count($drawnNumbers);
            $playersProgress = Cache::remember($cacheKey, 3, function() use ($room, $variant, $drawnNumbers) {
                // Obtener el patrón desde la ronda activa de la sala
                $round = $room->rounds()->latest('round')->first();
                $pattern = $round?->bingo_pattern_id
                    ? BingoPattern::find($round->bingo_pattern_id)
                    : BingoPattern::where('default_pattern', 'Y')->first();

                if (! $pattern) {
                    return [];
                }

                $patternPositions = $this->patternResolver->resolve($pattern, $variant);

                return $this->drawService->computePlayersProgress($room, $patternPositions, $drawnNumbers);
            });
        }

        return [
            'draw' => $drawnNumbers,
            'totalPlayers' => (int) $room->total_players,
            'prizePool' => (float) $room->prize_pool,
            'roomFinished' => $roomIsFinished,
            'initialWinners' => $roomIsFinished ? $this->getHistoricalWinners($room) : [],
            'playersProgress' => $playersProgress,
        ];
    }

    /**
     * Estado de las compras de un ExternalPlayer para mostrar en SU link:
     * cuántos cartones esperan revisión de pago, cuántos se anularon por pago
     * rechazado, qué premios ganó y quién se los entrega.
     *
     * Sin esto el comprador veía el link vacío (pago PENDING) o sus cartones
     * desaparecer (pago rechazado) sin explicación. Los premios de compradores
     * externos se retienen para verificación y entrega manual: nunca se acreditan
     * automáticamente a la wallet del vendedor que comparte el room_user_id.
     *
     * @return array{
     *     pending_cards: int,
     *     rejected_cards: int,
     *     prizes: array<int, array{type: string, amount: float, currency_symbol: string, status: string, status_label: string}>,
     *     sold_by: ?string,
     *     commerce_name: ?string
     * }
     */
    public function getExternalPlayerPurchaseStatus(\App\Models\Roles\Player\External\ExternalPlayer $externalPlayer) : array
    {
        $cardsByStatus = \App\Domains\Games\Bingo\Models\Card\CardPayment\BingoCardPayment::query()
            ->where('external_player_id', $externalPlayer->id)
            ->whereIn('payment_status', ['PENDING', 'REJECTED'])
            ->join('bingo_card_lots', 'bingo_card_lots.id', '=', 'bingo_card_payments.card_lot_id')
            ->groupBy('bingo_card_payments.payment_status')
            ->selectRaw('bingo_card_payments.payment_status as status, COALESCE(SUM(bingo_card_lots.card_count), 0) as cards')
            ->pluck('cards', 'status');

        $cardIds = \App\Models\Roles\Player\External\ExternalPlayerParticipation::participableIdsFor(
            $externalPlayer->id,
            \App\Domains\Games\Bingo\Models\Card\BingoCard::class,
        );

        $statusLabels = [
            'waiting' => 'Por entregar',
            'processing' => 'En proceso',
            'claimed' => 'Entregado',
            'cancelled' => 'Anulado',
            'suspended' => 'En revisión',
            'disputed' => 'En disputa',
        ];

        $prizes = empty($cardIds) ? collect() : \App\Domains\Games\Bingo\Models\Card\CardPrize\BingoCardPrize::query()
            ->whereIn('winning_card_id', $cardIds)
            ->with('currency:id,symbol')
            ->orderBy('created_at')
            ->get();

        [$soldBy, $commerceName] = $this->resolveExternalPlayerSeller((string) $externalPlayer->seller_user_id);

        return [
            'pending_cards' => (int) ($cardsByStatus['PENDING'] ?? 0),
            'rejected_cards' => (int) ($cardsByStatus['REJECTED'] ?? 0),
            'prizes' => $prizes->map(fn ($prize) => [
                'type' => (string) $prize->prize_type_description,
                'amount' => (float) $prize->local_amount,
                'currency_symbol' => (string) ($prize->currency?->symbol ?? ''),
                'status' => (string) $prize->prize_status,
                'status_label' => $statusLabels[$prize->prize_status] ?? (string) $prize->prize_status,
            ])->values()->all(),
            'sold_by' => $soldBy,
            'commerce_name' => $commerceName,
        ];
    }

    /**
     * Obtener datos para la vista del juego (game).
     */
    public function getInGameData(BingoRoom $room, ?int $userId = null) : array
    {
        $this->checkExpired->handle();

        // La sala ya viene del route binding, solo necesitamos cargar las relaciones
        $bingoRoom = $room->load(['config', 'creator', 'cardPrices', 'rounds', 'status']);

        if ($bingoRoom->trashed()) {
            throw new \Illuminate\Database\Eloquent\ModelNotFoundException('BingoRoom has been deleted.');
        }

        $roomStatusCode = $bingoRoom->getRelationValue('status')?->code
            ?? $bingoRoom->status?->code
            ?? '';
        $roomIsFinished = strtoupper($roomStatusCode) === 'FINISHED';

        // Obtener cartones del jugador (incluye ganadores si la sala ya terminó)
        $cartons = $this->getUserCartons((int) $bingoRoom->id, $userId, $roomIsFinished);

        // Resolver moneda, tasa de cambio y precio del cartón según la wallet del usuario
        $pricing = $this->resolvePricingForUser($bingoRoom, $userId);

        // Preparar datos del juego
        $config = $bingoRoom->config;
        $rounds = $bingoRoom->rounds;

        // Reusar cache escrito por BingoDrawService — evita query a bingo_draws en page-load
        // cuando el sorteo está en progreso (la clave ya tiene los chips actuales con TTL activo).
        $drawCacheKey = "bingo:draw-state:draw:{$bingoRoom->id}";
        $drawnNumbers = Cache::get($drawCacheKey);
        if ($drawnNumbers === null) {
            $drawnNumbers = BingoDraw::where('room_id', $bingoRoom->id)
                ->orderBy('time')
                ->orderBy('created_at')
                ->pluck('chip')
                ->map(fn ($chip) => (int) $chip)
                ->toArray();
            Cache::put($drawCacheKey, $drawnNumbers, 5);
        }

        // Para salas finalizadas, cargar los ganadores históricos desde BingoCardPrize
        $initialWinners = $roomIsFinished ? $this->getHistoricalWinners($bingoRoom) : [];

        return [
            'room' => $bingoRoom,
            'userId' => $userId !== null ? (string) $userId : null,
            'roomId' => (string) $bingoRoom->id,
            'roomRouteKey' => $bingoRoom->getRouteKey(),
            'gameVariant' => $config?->game_variant ?? \App\Domains\Games\Bingo\ValueObjects\BingoVariant::BINGO_75,
            'cartons' => $cartons,
            'customerLogo' => null,
            'currencySym' => $pricing->currencySymbol,
            'prizePool' => (float) $bingoRoom->prize_pool,
            'exchangeRate' => $pricing->exchangeRate,
            'totalPlayers' => (int) $bingoRoom->total_players,
            'prizePoolType' => 'Premio',
            'round' => 1,
            'rounds' => (int) $rounds->count(),
            'bingoPattern' => '',
            'draw' => $drawnNumbers,
            'startTime' => (int) ($config?->unix_date ?? $bingoRoom->published_at?->timestamp ?? now()->timestamp),
            'initialWinners' => $initialWinners,
            'roomFinished' => $roomIsFinished,
        ];
    }

    /**
     * Datos para la vista del juego (game) de un ExternalPlayer — mismo
     * payload que getInGameData(), pero resolviendo cartones vía
     * ExternalPlayerParticipation (ver getUserCartonsForExternalPlayer())
     * en vez de por user_id, ya que un ExternalPlayer no tiene fila en
     * `users`.
     */
    public function getInGameDataForExternalPlayer(BingoRoom $room, \App\Models\Roles\Player\External\ExternalPlayer $externalPlayer) : array
    {
        $data = $this->getInGameData($room, null);

        $roomIsFinished = (bool) $data['roomFinished'];
        $data['cartons'] = $this->getUserCartonsForExternalPlayer((string) $externalPlayer->id, $roomIsFinished);
        $data['userId'] = null;

        return $data;
    }

    /**
     * Obtener cartones del usuario en una sala.
     *
     * @param bool $includeWinners true para salas finalizadas — incluye STATUS_WINNER además de STATUS_ACTIVE
     */
    public function getUserCartons(int $roomId, ?int $userId, bool $includeWinners = false) : array
    {
        if (! $userId) {
            return [];
        }

        $roomUser = \App\Domains\Games\Bingo\Models\Room\User\BingoRoomUser::where('room_id', (string) $roomId)
            ->where('user_id', (string) $userId)
            ->first();

        $validStatuses = $includeWinners
            ? [\App\Domains\Games\Bingo\Models\Card\BingoCard::STATUS_ACTIVE, \App\Domains\Games\Bingo\Models\Card\BingoCard::STATUS_WINNER]
            : [\App\Domains\Games\Bingo\Models\Card\BingoCard::STATUS_ACTIVE];

        $query = \App\Domains\Games\Bingo\Models\Card\BingoCard::query()
            ->whereIn('status', $validStatuses)
            ->with(['currency', 'cardLot', 'roomUser'])
            ->orderBy('serial_number');

        if ($roomUser) {
            $query->where('room_user_id', (string) $roomUser->id);
        } else {
            // Fallback: creador directo sin room_user (ej. sala propia en dev)
            $query->where('room_id', (string) $roomId)
                ->where('creator_user_id', (string) $userId);
        }

        return $query->get()
            ->map(fn ($card) => $card->toFrontendFormat())
            ->all();
    }

    /**
     * Cartones de un ExternalPlayer (comprador sin cuenta) en una sala —
     * equivalente a getUserCartons() pero filtrando por
     * ExternalPlayerParticipation en vez de user_id: varios ExternalPlayer
     * del mismo vendedor comparten el mismo BingoRoomUser
     * (bingo_room_users.user_id = creador/Commerce), así que filtrar por ese
     * room_user_id devolvería los cartones de TODOS los compradores del
     * vendedor, no solo los de este jugador específico.
     *
     * @param bool $includeWinners true para salas finalizadas — incluye STATUS_WINNER además de STATUS_ACTIVE
     */
    public function getUserCartonsForExternalPlayer(int|string $externalPlayerId, bool $includeWinners = false) : array
    {
        $cardIds = \App\Models\Roles\Player\External\ExternalPlayerParticipation::participableIdsFor(
            $externalPlayerId,
            \App\Domains\Games\Bingo\Models\Card\BingoCard::class,
        );

        if (empty($cardIds)) {
            return [];
        }

        $validStatuses = $includeWinners
            ? [\App\Domains\Games\Bingo\Models\Card\BingoCard::STATUS_ACTIVE, \App\Domains\Games\Bingo\Models\Card\BingoCard::STATUS_WINNER]
            : [\App\Domains\Games\Bingo\Models\Card\BingoCard::STATUS_ACTIVE];

        // Lotes con pago aún PENDING de revisión admin quedan ocultos — ver
        // BingoCardGenerationService::createCardPayment(). Solo se excluye el
        // lote específico con pago pendiente, no toda la sesión del
        // comprador: compras previas ya aprobadas siguen jugables con
        // normalidad.
        $pendingLotIds = \App\Domains\Games\Bingo\Models\Card\CardPayment\BingoCardPayment::query()
            ->where('external_player_id', $externalPlayerId)
            ->where('payment_status', 'PENDING')
            ->pluck('card_lot_id');

        return \App\Domains\Games\Bingo\Models\Card\BingoCard::query()
            ->whereIn('id', $cardIds)
            ->whereIn('status', $validStatuses)
            ->when($pendingLotIds->isNotEmpty(), fn ($q) => $q->whereNotIn('card_lot_id', $pendingLotIds))
            ->with(['currency', 'cardLot', 'roomUser'])
            ->orderBy('serial_number')
            ->get()
            ->map(fn ($card) => $card->toFrontendFormat())
            ->all();
    }

    private function convertToWalletCurrency(float $amount, ?int $currencyId) : float
    {
        if (! $currencyId) {
            return round($amount, 2);
        }

        return round($amount * $this->getWalletExchangeRate($currencyId), 2);
    }

    /**
     * Obtener el precio del cartón para una sala
     */
    private function getCardPrice(BingoRoom $room, ?int $walletCurrencyId = null) : float
    {
        if ($walletCurrencyId) {
            $localPrice = $room->cardPrices->firstWhere('currency_id', $walletCurrencyId);

            if ($localPrice) {
                return (float) $localPrice->card_price;
            }
        }

        // Buscar precio en USD (moneda por defecto)
        $usdId = Currency::usdId();
        $cardPrice = $usdId
            ? $room->cardPrices->firstWhere('currency_id', $usdId)
            : null;

        // Si no hay precio en USD, tomar el primero disponible
        if (! $cardPrice && $room->cardPrices->isNotEmpty()) {
            $cardPrice = $room->cardPrices->first();
        }

        $price = (float) ($cardPrice?->card_price ?? 0);
        $isWalletUsd = $walletCurrencyId && (string) $walletCurrencyId === (string) $usdId;

        return $walletCurrencyId && ! $isWalletUsd
            ? $this->convertToWalletCurrency($price, $walletCurrencyId)
            : $price;
    }

    /**
     * Ganadores históricos de una sala ya finalizada, para reconstruir el
     * estado de "sorteo terminado" al recargar la página del juego.
     *
     * @return array<int, array{user_id: string, user_name: string, card_id: string, card_serial: string, prize: float, pattern: string, round: int}>
     */
    private function getHistoricalWinners(BingoRoom $room) : array
    {
        return BingoCardPrize::where('room_id', $room->id)
            ->with(['round:id,round', 'winningCard:id,serial_number', 'roomUser.user:id,username,first_name,last_name'])
            ->get()
            ->map(fn (BingoCardPrize $cp) => [
                'user_id' => (string) ($cp->roomUser?->user_id ?? ''),
                'user_name' => $cp->roomUser?->user?->full_name ?? $cp->roomUser?->user?->username ?? '—',
                'card_id' => (string) ($cp->winning_card_id ?? ''),
                'card_serial' => $cp->winningCard?->serial_number ?? '',
                'prize' => (float) ($cp->usd_amount ?? 0),
                'pattern' => $cp->winning_pattern ?? '',
                'round' => $cp->round?->round ?? 1,
            ])
            ->values()
            ->all();
    }

    /**
     * Tasa de cambio de la billetera del jugador — delega a la fuente única
     * de verdad (CurrencyExchangeRateService → exchange_rates). Vista de solo
     * lectura (precio mostrado antes de comprar): si no hay tasa activa no se
     * bloquea la carga de la página, pero queda registrado en logs para que
     * se corrija antes de que el jugador intente comprar (ahí sí se bloquea,
     * ver BingoCardGenerateService::getPlayerCardPrice()).
     */
    private function getWalletExchangeRate(?int $currencyId) : float
    {
        if (! $currencyId) {
            return 1.0;
        }

        try {
            return $this->currencyExchangeRate->getActiveRate($currencyId);
        } catch (ExchangeRateException $e) {
            Log::warning('[BingoGameService] Sin tasa activa al resolver precio para el jugador', [
                'currency_id' => $currencyId,
                'error' => $e->getMessage(),
            ]);

            return 1.0;
        }
    }

    /**
     * Nombre del vendedor que captó al ExternalPlayer y del comercio al que
     * pertenece (directo o vía agencia) — a quién debe acudir el comprador
     * por su premio o una devolución. Solo nombres, sin datos de contacto.
     *
     * @return array{0: ?string, 1: ?string} [vendedor, comercio]
     */
    private function resolveExternalPlayerSeller(string $sellerUserId) : array
    {
        if ($sellerUserId === '') {
            return [null, null];
        }

        $seller = \App\Models\Roles\Seller::where('user_id', $sellerUserId)->first(['id', 'name', 'manager_user_id', 'subsidiary_id']);

        if ($seller) {
            return [$seller->name ?: null, $seller->resolveCommerce()?->name];
        }

        // Vendió el propio comercio (sin vendedor intermedio).
        $commerceName = \App\Models\Roles\Commerce::where('user_id', $sellerUserId)->value('name');

        return [$commerceName, $commerceName];
    }

    /**
     * Resuelve moneda, tasa de cambio y precio del cartón para el jugador,
     * según la wallet activa que tenga configurada (si tiene alguna).
     */
    private function resolvePricingForUser(BingoRoom $room, ?int $userId) : PlayerCardPricingDTO
    {
        $wallet = $this->resolveUserWallet($userId);
        $walletCurrencyId = $wallet?->currency_id ? (int) $wallet->currency_id : null;

        return new PlayerCardPricingDTO(
            currencySymbol: $wallet?->currency?->symbol ?? '$',
            currencyId: $walletCurrencyId,
            exchangeRate: $this->getWalletExchangeRate($walletCurrencyId),
            cardPrice: $this->getCardPrice($room, $walletCurrencyId),
        );
    }

    private function resolveUserWallet(?int $userId) : ?Wallet
    {
        if (! $userId) {
            return null;
        }

        return Wallet::with('currency')
            ->where('user_id', $userId)
            ->active()
            ->first();
    }
}
