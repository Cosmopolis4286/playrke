<?php

namespace App\Domains\Games\Bingo\Services\Room\Automatic;

use App\Domains\Games\Bingo\Contracts\Card\CardPrice\BingoCardPriceRepositoryInterface;
use App\Domains\Games\Bingo\Contracts\Room\BingoRoomRepositoryInterface;
use App\Domains\Games\Bingo\DTOs\Room\BingoRoomCreateDTO;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Models\Room\BingoRoomAutomatic;
use App\Domains\Games\Bingo\Models\Room\BingoRoomConfig;
use App\Domains\Games\Bingo\Services\Room\BingoRoomAuditLogger;
use App\Domains\Games\Bingo\Services\Room\BingoRoomCrudService;
use App\Domains\Games\Bingo\ValueObjects\BingoRoomPrizeRules;
use App\Domains\Wallet\Services\WalletService;
use App\Models\Games\Common\PrizePool\PrizePoolType;
use App\Models\Games\Common\Utils\GameStatus;
use App\Services\ExchangeRate\ExchangeRateDomainService;
use App\Services\Utils\Snowflake\SnowflakeBatchService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Genera salas de bingo reales a partir de las plantillas automáticas activas.
 *
 * Extiende BingoRoomCrudService para reutilizar los métodos protegidos:
 *  - createBaseRoom  → crea BingoRoom + BingoCreator con uuid_code único
 *  - syncCardPrices  → inserta precios de cartón por moneda vía upsert batch
 *  - syncRounds      → inserta rondas + premios vía batch
 *
 * Flujo:
 *  1. Obtiene plantillas ACTIVE cuyo campo `days` incluye el día actual.
 *  2. Filtra las que tienen una hora programada dentro de la ventana de tiempo
 *     (próximas N minutos desde ahora).
 *  3. Por cada plantilla transforma sus datos al formato que esperan los métodos
 *     del padre y delega la creación.
 */
final class AutoGenerateBingoRoomsService extends BingoRoomCrudService
{
    /**
     * Ventana de tiempo (en minutos) para considerar que una sala debe generarse ahora.
     * El scheduler corre cada hora, así que 60 min es la ventana lógica.
     */
    private const TIME_WINDOW_MINUTES = 60;

    public function __construct(
        BingoRoomRepositoryInterface $bingoRooms,
        BingoCardPriceRepositoryInterface $bingoCardPrices,
        ExchangeRateDomainService $exchangeRateService,
        WalletService $walletService,
        BingoRoomAuditLogger $auditLogger,
        SnowflakeBatchService $snowflakeService,
    ) {
        parent::__construct($bingoRooms, $bingoCardPrices, $exchangeRateService, $walletService, $auditLogger, $snowflakeService);
    }

    /**
     * Crea las salas de bingo reales a partir de las plantillas automáticas.
     */
    public function createBingoRooms(?array $creator = null) : bool
    {
        $templates = $this->getActiveTemplates($creator);

        if ($templates->isEmpty()) {
            Log::info('[AutoGenerateBingoRooms] No hay plantillas activas para hoy.');

            return false;
        }

        return $this->processTemplates($templates);
    }

    private function buildDtoFromTemplate(BingoRoomAutomatic $template) : BingoRoomCreateDTO
    {
        $creatorTz = $this->resolveTemplateTimezone($template);

        return BingoRoomCreateDTO::fromArray([
            'date' => Carbon::now($creatorTz)->format('Y-m-d'),
            'time' => $template->game_time ? $template->game_time->format('H:i') : '00:00',
            'roomType' => $template->room_type_id,
            'connectionType' => $template->connection_type_id,
            'prizePoolType' => $template->prize_pool_type_id,
            'allowedCurrencies' => $template->allowed_currencies ?? [],
            'roomCommn' => (float) $template->room_commission,
            'useBaseUsd' => (bool) $template->use_base_usd,
            'timeChip' => (int) $template->time_chip,
            'rounds' => (int) ($template->rounds ?? 1),
            'useSerials' => (bool) $template->use_serials,
            'activateRoom' => true,
        ]);
    }

    /**
     * Crea la BingoRoomConfig con is_scheduled='Y' (automática).
     *
     * @param array|null $currencyOverride si se indica, limita `allowed_currencies`
     *                                     a esta(s) moneda(s) y marca `rooms_per_currency_split = true`
     */
    private function createRoomConfig(BingoRoom $room, BingoRoomAutomatic $template, ?array $currencyOverride = null) : BingoRoomConfig
    {
        $creatorTz = $this->resolveTemplateTimezone($template);
        $scheduledDate = Carbon::now($creatorTz)->format('Y-m-d');
        $scheduledTime = $template->game_time ? $template->game_time->format('H:i:s') : null;
        $unixDate = $scheduledTime
            ? Carbon::createFromFormat('Y-m-d H:i:s', "$scheduledDate $scheduledTime", $creatorTz)->timestamp
            : null;

        return BingoRoomConfig::create([
            'room_id' => $room->id,
            'template_id' => $template->id,
            'parent_automatic_id' => $template->id,
            'is_scheduled' => 'Y',
            'room_type_id' => $template->room_type_id,
            'creator_user_id' => $template->creator_user_id,
            'connection_type_id' => $template->connection_type_id,
            'in_live' => $template->in_live ?? 'N',
            'date' => $scheduledDate,
            'time' => $scheduledTime,
            'unix_date' => $unixDate,
            'rke_commission' => $template->rke_commission,
            'room_commission' => $template->room_commission,
            'use_base_usd' => $template->use_base_usd,
            'card_price_usd' => $template->card_price_usd,
            'usd_exchange_round_step' => $template->usd_exchange_round_step,
            'allowed_currencies' => $currencyOverride ?? $template->allowed_currencies,
            'rooms_per_currency_split' => $currencyOverride !== null,
            'prize_pool_type_id' => $template->prize_pool_type_id,
            'amount_added_prize_pool' => $template->amount_added_prize_pool,
            'time_chip' => $template->time_chip,
            'rounds' => $template->rounds,
            'rounds_pct' => $template->rounds_pct,
            'use_serials' => $template->use_serials,
            'serials_pct' => $template->serials_pct,
            'num_serials' => $template->num_serials,
            'jackpot_pct' => $template->jackpot_pct,
            'jackpot_type_id' => $template->jackpot_type_id,
            'min_cards_to_start' => $template->min_cards_to_start,
            'max_cards_to_start' => $template->max_cards_to_start,
            'min_players' => $template->min_players,
            'max_players' => $template->max_players,
            'allow_custom_card' => $template->allow_custom_card,
            'custom_card_price_pct' => $template->custom_card_price_pct,
            'custom_card_mode' => $template->custom_card_mode,
            'custom_card_every_x' => $template->custom_card_every_x,
            'move_free_space_mode' => $template->move_free_space_mode,
            'move_free_space_price_pct' => $template->move_free_space_price_pct,
            'allow_special_card' => $template->allow_special_card,
            'max_special_cards' => $template->max_special_cards,
        ]);
    }

    private function generatedRoomStatusId() : int
    {
        return (int) GameStatus::codeToId('INACTIVE');
    }

    /**
     * Genera el/los BingoRoom reales a partir de una plantilla automática.
     *
     * Si la plantilla tiene `rooms_per_currency` activo y permite 2+ monedas,
     * genera una sala independiente por cada moneda permitida. En caso
     * contrario, genera una única sala con todas las monedas permitidas.
     */
    private function generateRoomFromTemplate(BingoRoomAutomatic $template) : void
    {
        $currencies = $template->allowed_currencies ?? [];

        if ($template->rooms_per_currency && count($currencies) > 1) {
            foreach ($currencies as $currencyId) {
                $this->generateSingleRoomFromTemplate($template, [$currencyId]);
            }

            return;
        }

        $this->generateSingleRoomFromTemplate($template, null);
    }

    /**
     * Genera un único BingoRoom a partir de una plantilla automática.
     * Delega la creación de sala base, precios y rondas a los métodos
     * protegidos de BingoRoomCrudService.
     *
     * @param array|null $currencyOverride si se indica, limita la sala generada
     *                                     (allowed_currencies, precios de cartón y premios fijos) a esta(s)
     *                                     moneda(s) — usado por el modo "salas por moneda"
     */
    private function generateSingleRoomFromTemplate(BingoRoomAutomatic $template, ?array $currencyOverride = null) : BingoRoom
    {
        // 1. Construir DTO mínimo para createBaseRoom (solo usa activateRoom y roomCommission)
        $dto = $this->buildDtoFromTemplate($template);

        // 2. Crear BingoRoom + BingoCreator (reutiliza createBaseRoom del padre)
        $room = $this->createBaseRoom(
            (int) $template->creator_user_id,
            $dto,
            $this->generatedRoomStatusId(),
        );

        // Bote inicial: mismo cálculo que una sala creada a mano (monto
        // Fijo/Extra si el bote no es acumulativo). Antes las salas generadas
        // arrancaban siempre en 0 aunque la plantilla tuviera bote fijo.
        // Si el tipo ya no existe no se aborta la generación: se arranca en 0.
        // No aplica en acumulativo ni en Premio/Servicio puro (ver BingoRoomPrizeRules).
        $prizePoolType = PrizePoolType::find($template->prize_pool_type_id);
        $roundTypes = array_map(
            fn ($t) => match ($t) {
                'FIXED' => 'fixed', 'OTHER' => 'prize', default => 'percentage'
            },
            (array) ($template->rounds_config['prize_types'] ?? []),
        );
        $prizePool = ($prizePoolType && BingoRoomPrizeRules::usesInitialAmount($prizePoolType->code, $roundTypes))
            ? (float) ($template->amount_added_prize_pool ?? 0)
            : 0.0;

        // Establecer comisiones y bote inicial en la sala (createBaseRoom no los incluye)
        $room->update([
            'rke_commission' => $template->rke_commission,
            'room_commission' => $template->room_commission,
            'prize_pool' => $prizePool,
        ]);

        // 3. Crear configuración de sala (is_scheduled='Y' para automática)
        $config = $this->createRoomConfig($room, $template, $currencyOverride);

        // 4. Sincronizar precios de cartones (reutiliza syncCardPrices del padre)
        $this->syncCardPricesFromTemplate($room, $template, $currencyOverride);

        // 5. Sincronizar rondas con sus premios (reutiliza syncRounds del padre)
        $this->syncRoundsFromTemplate($room, $config->id, $template, $currencyOverride);

        Log::info('[AutoGenerateBingoRooms] Sala generada.', [
            'room_id' => $room->id,
            'template_id' => $template->id,
        ]);

        return $room;
    }

    /**
     * Obtiene plantillas ACTIVE para el día de hoy (en UTC) cuya hora cae en
     * la ventana de tiempo actual.
     *
     * El día se filtra en SQL usando UTC como aproximación — un desfase de
     * zona horaria solo podría mover una plantilla al día UTC adyacente
     * cerca de medianoche, no afecta la ventana de hora en sí. La hora en
     * cambio SÍ depende de la zona horaria del creador (game_time se
     * interpreta como hora local del creador, no UTC) — filtrarla en SQL
     * contra UTC directamente descartaría plantillas de creadores fuera de
     * UTC+0, así que la ventana de tiempo se evalúa en PHP por plantilla,
     * resolviendo la zona horaria real de cada una vía resolveTemplateTimezone().
     */
    private function getActiveTemplates(?array $creator) : Collection
    {
        $now = Carbon::now('UTC');
        $dayName = strtolower($now->englishDayOfWeek);

        $query = BingoRoomAutomatic::query()
            ->where('status', BingoRoomAutomatic::STATUS_ACTIVE)
            ->whereJsonContains('recurrence_days', $dayName);

        if ($creator) {
            $query->where('creator_user_id', $creator['id']);
        }

        return $query->get()->filter(fn (BingoRoomAutomatic $template) => $this->isWithinTimeWindow($template));
    }

    /**
     * Determina si la hora programada de la plantilla (interpretada en la
     * zona horaria de su creador) ya se cumplió y sigue dentro de la ventana
     * de gracia (desde ahora - TIME_WINDOW_MINUTES hasta ahora, en esa misma
     * zona). El scheduler corre cada hora, así que la ventana cubre salas
     * cuya hora programada llegó desde la última ejecución.
     */
    private function isWithinTimeWindow(BingoRoomAutomatic $template) : bool
    {
        if (! $template->game_time) {
            return false;
        }

        $creatorTz = $this->resolveTemplateTimezone($template);
        $now = Carbon::now($creatorTz);
        $windowStart = $now->copy()->subMinutes(self::TIME_WINDOW_MINUTES);

        $scheduledTime = Carbon::parse($template->game_time->format('H:i:s'), $creatorTz)
            ->setDateFrom($now);

        return $scheduledTime->betweenIncluded($windowStart, $now);
    }

    /**
     * Procesa todas las plantillas dentro de una transacción.
     */
    private function processTemplates(Collection $templates) : bool
    {
        DB::beginTransaction();

        try {
            foreach ($templates as $template) {
                $this->generateRoomFromTemplate($template);
            }
            DB::commit();

            return true;
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('[AutoGenerateBingoRooms] Error al generar salas: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }

    /**
     * Construye un BingoRoomCreateDTO mínimo desde la plantilla.
     * createBaseRoom solo usa: activateRoom y roomCommission.
     */
    private function resolveTemplateTimezone(BingoRoomAutomatic $template) : string
    {
        $tzName = \App\Models\User::where('users.id', $template->creator_user_id)
            ->join('time_zones', 'users.time_zone_id', '=', 'time_zones.id')
            ->value('time_zones.name');

        return $tzName ?? 'UTC';
    }

    /**
     * Transforma los precios de cartón de la plantilla al formato
     * que espera syncCardPrices del padre: allowedCurrencies[] + cardPriceByCurrency[].
     *
     * Usa los campos consolidados de bingo_rooms_automatic:
     * - card_currencies (monedas)
     * - card_prices (precios por moneda)
     *
     * @param array|null $currencyOverride si se indica, limita los precios sincronizados
     *                                     a esta(s) moneda(s)
     */
    private function syncCardPricesFromTemplate(BingoRoom $room, BingoRoomAutomatic $template, ?array $currencyOverride = null) : void
    {
        // Obtener precios de los campos consolidados
        $pricesConfig = $template->getCardPricesConfig();
        $cardPrices = $pricesConfig['card_prices'] ?? [];

        if (empty($cardPrices)) {
            return;
        }

        $prices = array_filter(
            $cardPrices,
            fn ($price) => $price !== null && $price !== '',
        );

        if ($currencyOverride !== null) {
            $prices = array_intersect_key($prices, array_flip($currencyOverride));
        }

        if (empty($prices)) {
            return;
        }

        // syncCardPrices espera: allowedCurrencies[], defaultCardPrice, cardPriceByCurrency[], creatorUserId
        $this->syncCardPrices(
            roomId: (string) $room->id,
            allowedCurrencies: array_keys($prices),
            defaultCardPrice: 0,
            cardPriceByCurrency: $prices,
            creatorUserId: (string) $template->creator_user_id,
        );
    }

    /**
     * Transforma las rondas de la plantilla al formato roundsConfig[]
     * que espera syncRounds del padre.
     *
     * Formato de entrada (template):
     *   prize_types[]   = ['PCT', 'FIXED', 'OTHER']
     *   bingo_patterns[] = [patternId, ...]
     *   prize_values[]  = [pct|fixedByCurrency|{prizeId,prizeName}, ...]
     *
     * Formato de salida (roundsConfig):
     *   [prizeType, bingoPattern, prizeValue?, prizeValueByCurrency?, prizeId?, prizeName?]
     *
     * Mapeo inverso: PCT→'percentage', FIXED→'fixed', OTHER→'prize'
     *
     * Usa los campos consolidados de bingo_rooms_automatic:
     * - prize_types (tipos de premios)
     * - bingo_patterns (patrones)
     * - prize_values (valores de premios)
     *
     * @param array|null $currencyOverride si se indica, limita los premios fijos
     *                                     (`prizeValueByCurrency`) a esta(s) moneda(s)
     */
    private function syncRoundsFromTemplate(BingoRoom $room, string $roomConfigId, BingoRoomAutomatic $template, ?array $currencyOverride = null) : void
    {
        // Obtener configuración de rondas de los campos consolidados
        $roundsConfig = $template->getRoundsConfig();

        $totalRounds = $template->rounds ?? 1;
        $prizeTypes = $roundsConfig['prize_types'] ?? [];
        $patterns = $roundsConfig['bingo_patterns'] ?? [];
        $prizeValues = $roundsConfig['prize_values'] ?? [];

        if ($totalRounds === 0 || empty($patterns)) {
            return;
        }

        // Mapeo inverso DB→frontend (que espera syncRounds del padre)
        $prizeTypeMap = [
            'PCT' => 'percentage',
            'FIXED' => 'fixed',
            'OTHER' => 'prize',
        ];

        $configuredRounds = [];

        for ($i = 0; $i < $totalRounds; $i++) {
            $prizeType = $prizeTypes[$i] ?? 'PCT';
            $value = $prizeValues[$i] ?? null;

            $config = [
                'prizeType' => $prizeTypeMap[$prizeType] ?? 'percentage',
                'bingoPattern' => $patterns[$i] ?? null,
            ];

            if ($prizeType === 'PCT') {
                $config['prizeValue'] = $value !== null ? (float) $value : null;
            } elseif ($prizeType === 'FIXED' && is_array($value)) {
                $config['prizeValueByCurrency'] = $currencyOverride !== null
                    ? array_intersect_key($value, array_flip($currencyOverride))
                    : $value;
            } elseif ($prizeType === 'OTHER' && is_array($value)) {
                $config['prizeId'] = $value['prizeId'] ?? null;
                $config['prizeName'] = $value['prizeName'] ?? '';
            }

            $configuredRounds[] = $config;
        }

        // Delegar al método protegido del padre
        $this->syncRounds((string) $room->id, $roomConfigId, $configuredRounds);
    }
}
