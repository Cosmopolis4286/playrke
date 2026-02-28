<?php

namespace App\Services\Games\Bingo\Room\Automatic;

use App\DTOs\Games\Bingo\Room\Automatic\BingoRoomAutomaticCreateDTO;
use App\DTOs\Games\Bingo\Room\Automatic\BingoRoomAutomaticUpdateDTO;
use App\Exceptions\Domain\Games\Bingo\Room\Automatic\BingoRoomAutomaticException;
use App\Http\Resources\Games\Bingo\Room\Automatic\BingoRoomAutomaticResource;
use App\Models\Games\Bingo\Card\CardPrice\BingoCardPriceAutomatic;
use App\Models\Games\Bingo\Room\BingoRoomAutomatic;
use App\Models\Games\Bingo\Round\BingoRoundAutomatic;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Servicio CRUD para salas de bingo automáticas.
 *
 * Opera directamente sobre bingo_rooms_automatic y sus tablas relacionadas:
 * - bingo_rounds_automatic  (un registro JSON por sala)
 * - bingo_card_price_automatic (un registro JSON por sala)
 *
 * @final
 */
final class BingoRoomAutomaticCrudService
{
    /**
     * Obtener sala automática para visualización.
     */
    public function show(BingoRoomAutomatic $room): BingoRoomAutomaticResource
    {
        if (!$room->exists) {
            throw BingoRoomAutomaticException::notFound();
        }

        $room->load(['cardPriceAutomatic', 'roundAutomatic']);

        return new BingoRoomAutomaticResource($room);
    }

    /**
     * Guardar sala automática (create o update).
     *
     * @param BingoRoomAutomatic|BingoRoomAutomaticCreateDTO $roomOrDto
     * @param BingoRoomAutomaticUpdateDTO|null $updateDto
     */
    public function saveRoom(
        BingoRoomAutomatic|BingoRoomAutomaticCreateDTO $roomOrDto,
        ?BingoRoomAutomaticUpdateDTO $updateDto = null
    ): bool {
        return DB::transaction(function () use ($roomOrDto, $updateDto) {

            $userId  = Auth::id();
            $isCreate = $roomOrDto instanceof BingoRoomAutomaticCreateDTO;
            $dto     = $isCreate ? $roomOrDto : $updateDto;

            $roomData = [
                'days'                    => $dto->days ?? null,
                'time'                    => $dto->time,
                'in_live'                 => $dto->inLive ?? 'N',
                'game_mode_id'            => $dto->gameMode,
                'room_type_id'            => $dto->roomType,
                'prize_pool_type_id'      => $dto->prizePoolType,
                'amount_added_prize_pool' => $dto->amountAddedPrizePool,
                'room_commission'         => $dto->roomCommission,
                'use_base_usd'            => $dto->useBaseUsd ?? false,
                'card_price_usd'          => $dto->cardPriceUsd,
                'usd_exchange_round_step' => $dto->usdExchangeRoundStep ?? null,
                'allowed_currencies'      => $dto->allowedCurrencies,
                'use_serials'             => $dto->useSerials ?? false,
                'time_chip'               => $dto->timeChip,
                'rounds'                  => $dto->rounds,
                'rounds_pct'              => $dto->roundsPct,
                'serials_pct'             => $dto->serialsPct ?? null,
                'num_serials'             => $dto->numSerials ?? null,
                'jackpot_pct'             => $dto->jackpotPct,
                'status'                  => ($dto->isDraft ?? false) ? 'INACTIVE' : 'ACTIVE',
            ];

            if ($isCreate) {
                $roomData['creator_user_id'] = $userId;
                $room = BingoRoomAutomatic::create($roomData);
            } else {
                $room = $roomOrDto;
                $room->update($roomData);
            }

            // Sincronizar precios de cartones por moneda
            $this->syncCardPrices($room->id, $dto->allowedCurrencies, $dto->cardPriceUsd ?? 0, $dto->cardPriceByCurrency ?? [], $userId);

            // Sincronizar rondas
            $this->syncRounds($room->id, $dto->rounds, $dto->roundsConfig ?? []);

            return true;
        });
    }

    /**
     * Guardar precios de cartones en bingo_card_price_automatic.
     * Usa un único registro JSON por sala (upsert).
     */
    private function syncCardPrices(
        string $roomId,
        array $allowedCurrencies,
        float $defaultCardPrice,
        array $cardPriceByCurrency,
        string $userId
    ): void {
        if (empty($allowedCurrencies)) {
            BingoCardPriceAutomatic::where('automatic_bingo_room_id', $roomId)->delete();
            return;
        }

        // Construir mapa currency_id => precio
        $cardPrices = [];
        foreach ($allowedCurrencies as $currencyId) {
            $cardPrices[(string) $currencyId] = $cardPriceByCurrency[(string) $currencyId] ?? $defaultCardPrice;
        }

        BingoCardPriceAutomatic::updateOrCreate(
            ['automatic_bingo_room_id' => $roomId],
            [
                'creator_user_id' => $userId,
                'currencies'      => array_map('strval', $allowedCurrencies),
                'card_prices'     => $cardPrices,
                'status'          => 'ACTIVE',
            ]
        );
    }

    /**
     * Guardar configuración de rondas en bingo_rounds_automatic.
     * Usa un único registro JSON por sala (upsert).
     *
     * Mapeo prize_type (frontend → DB):
     *   percentage → PCT
     *   fixed      → FIXED
     *   prize      → OTHER
     */
    private function syncRounds(string $roomId, int $roundsCount, array $roundsConfig): void
    {
        if (empty($roundsConfig)) {
            BingoRoundAutomatic::where('automatic_bingo_room_id', $roomId)->delete();
            return;
        }

        $prizeTypeMap = [
            'percentage' => 'PCT',
            'fixed'      => 'FIXED',
            'prize'      => 'OTHER',
        ];

        $prizeTypes    = [];
        $bingoPatterns = [];
        $prizeValues   = [];

        foreach ($roundsConfig as $config) {
            $prizeTypes[]    = $prizeTypeMap[$config['prizeType'] ?? 'percentage'] ?? 'PCT';
            $bingoPatterns[] = $config['bingoPattern'] ?? null;

            // Para FIXED, almacenar mapa moneda→valor; para el resto, valor numérico
            if (($config['prizeType'] ?? '') === 'fixed') {
                $prizeValues[] = $config['prizeValueByCurrency'] ?? null;
            } elseif (($config['prizeType'] ?? '') === 'prize') {
                $prizeValues[] = ['prizeId' => $config['prizeId'] ?? null, 'prizeName' => $config['prizeName'] ?? null];
            } else {
                $prizeValues[] = $config['prizeValue'] ?? null;
            }
        }

        BingoRoundAutomatic::updateOrCreate(
            ['automatic_bingo_room_id' => $roomId],
            [
                'rounds'        => (string) $roundsCount,
                'prize_types'   => $prizeTypes,
                'bingo_patterns' => $bingoPatterns,
                'prize_values'  => $prizeValues,
                'status'        => 'ACTIVE',
            ]
        );
    }

    /**
     * Alternar estado ACTIVE / INACTIVE de una sala automática.
     */
    public function toggleStatus(BingoRoomAutomatic $room): bool
    {
        if ($room->trashed()) {
            throw BingoRoomAutomaticException::invalidStatusChange();
        }

        $newStatus = $room->status === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE';

        return (bool) $room->update(['status' => $newStatus]);
    }

    /**
     * Copia una sala automática como nuevo borrador.
     */
    public function copyRoom(BingoRoomAutomatic $room): bool
    {
        $room->load(['cardPriceAutomatic', 'roundAutomatic']);

        $cardPriceByCurrency = [];
        if ($room->cardPriceAutomatic) {
            $cardPriceByCurrency = $room->cardPriceAutomatic->card_prices ?? [];
        }

        $roundsConfig = [];
        if ($room->roundAutomatic) {
            $prizeTypeMap = ['PCT' => 'percentage', 'FIXED' => 'fixed', 'OTHER' => 'prize'];
            $prizeTypes   = $room->roundAutomatic->prize_types ?? [];
            $patterns     = $room->roundAutomatic->bingo_patterns ?? [];
            $prizeValues  = $room->roundAutomatic->prize_values ?? [];

            foreach ($prizeTypes as $i => $type) {
                $frontendType = $prizeTypeMap[$type] ?? 'percentage';
                $config = [
                    'prizeType'    => $frontendType,
                    'bingoPattern' => $patterns[$i] ?? null,
                ];

                if ($frontendType === 'fixed') {
                    $config['prizeValueByCurrency'] = $prizeValues[$i] ?? null;
                    $config['prizeValue'] = null;
                } elseif ($frontendType === 'prize') {
                    $config['prizeId']   = ($prizeValues[$i] ?? [])['prizeId'] ?? null;
                    $config['prizeName'] = ($prizeValues[$i] ?? [])['prizeName'] ?? null;
                    $config['prizeValue'] = null;
                } else {
                    $config['prizeValue'] = $prizeValues[$i] ?? null;
                }

                $roundsConfig[] = $config;
            }
        }

        $dto = BingoRoomAutomaticCreateDTO::fromArray([
            'date'                 => $room->date?->format('Y-m-d') ?? now()->format('Y-m-d'),
            'time'                 => $room->formatted_time,
            'roomType'             => $room->room_type_id,
            'gameMode'             => $room->game_mode_id,
            'prizePoolType'        => $room->prize_pool_type_id,
            'cardPriceUsd'         => $room->card_price_usd !== null ? (float) $room->card_price_usd : null,
            'allowedCurrencies'    => $room->allowed_currencies ?? [],
            'amountAddedPrizePool' => $room->amount_added_prize_pool ? (float) $room->amount_added_prize_pool : null,
            'roomCommn'            => (float) $room->room_commission,
            'jackpotPct'           => $room->jackpot_pct ? (float) $room->jackpot_pct : null,
            'timeChip'             => (int) $room->time_chip,
            'rounds'               => (int) $room->rounds,
            'roundsPct'            => $room->rounds_pct ? (float) $room->rounds_pct : null,
            'serialsPct'           => (float) $room->serials_pct,
            'numSerials'           => (int) $room->num_serials,
            'roundsConfig'         => $roundsConfig,
            'cardPriceByCurrency'  => $cardPriceByCurrency,
            'isDraft'              => true,
        ]);

        return $this->saveRoom($dto);
    }

    /**
     * Soft delete de una sala automática.
     */
    public function delete(BingoRoomAutomatic $room): bool
    {
        if ($room->trashed()) {
            throw BingoRoomAutomaticException::alreadyDeleted();
        }

        return (bool) $room->delete();
    }
}
