<?php

namespace App\Services\Games\Bingo\Room\Automatic;

use App\Domains\Games\Bingo\DTOs\Room\Automatic\BingoRoomAutomaticCreateDTO;
use App\Domains\Games\Bingo\DTOs\Room\Automatic\BingoRoomAutomaticUpdateDTO;
use App\Domains\Games\Bingo\Exceptions\Room\Automatic\BingoRoomAutomaticException;
use App\Http\Resources\Games\Bingo\Room\Automatic\BingoRoomAutomaticResource;
use App\Domains\Games\Bingo\Models\Room\BingoRoomAutomatic;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Servicio CRUD para salas de bingo automáticas.
 *
 * Opera directamente sobre bingo_rooms_automatic con campos JSON consolidados:
 * - rounds_config, prize_types, bingo_patterns, prize_values (configuración de rondas)
 * - card_currencies, card_prices (precios de cartones)
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
                'rooms_per_currency'      => $dto->roomsPerCurrency ?? false,
                'use_serials'             => $dto->useSerials ?? false,
                'time_chip'               => $dto->timeChip,
                'rounds'                  => $dto->rounds,
                'rounds_pct'              => $dto->roundsPct,
                'serials_pct'             => $dto->serialsPct ?? null,
                'num_serials'             => $dto->numSerials ?? null,
                'jackpot_pct'             => $dto->jackpotPct,
                'status'                  => ($dto->activateRoom ?? false) ? 'ACTIVE' : 'INACTIVE',
                // New scheduling fields
                'recurrence_type'         => $dto->recurrenceType ?? 'WEEKLY',
                'recurrence_days'         => $dto->recurrenceDays ?? null,
                'game_time'               => $dto->gameTime ?? null,
                'start_date'              => $dto->startDate ?? null,
                'end_date'                => $dto->endDate ?? null,
                'default_config'          => $dto->defaultConfig ?? null,
                'default_card_price'      => $dto->defaultCardPrice ?? null,
                'default_currency_id'     => $dto->defaultCurrencyId ?? null,
                'auto_create_room'        => $dto->autoCreateRoom ?? true,
                'max_rooms_per_day'       => $dto->maxRoomsPerDay ?? null,
                'min_cards_to_start'      => $dto->minCardsToStart ?? null,
                'sales_start_offset_days' => $dto->salesStartOffsetDays ?? null,
            ];

            if ($isCreate) {
                $roomData['creator_user_id'] = $userId;
                $room = BingoRoomAutomatic::create($roomData);
            } else {
                $room = $roomOrDto;
                $room->update($roomData);
            }

            // Sincronizar precios de cartones por moneda
            $this->syncCardPrices($room->id, $dto->allowedCurrencies, $dto->cardPriceUsd ?? 0, $dto->cardPriceByCurrency ?? []);

            // Sincronizar rondas
            $this->syncRounds($room->id, $dto->rounds, $dto->roundsConfig ?? []);

            return true;
        });
    }

    /**
     * Guardar precios de cartones en campos consolidados de bingo_rooms_automatic.
     * Escribe directamente en card_currencies y card_prices (JSON).
     */
    private function syncCardPrices(
        string $roomId,
        array $allowedCurrencies,
        float $defaultCardPrice,
        array $cardPriceByCurrency
    ): void {
        if (empty($allowedCurrencies)) {
            BingoRoomAutomatic::where('id', $roomId)->update([
                'card_currencies' => null,
                'card_prices' => null,
            ]);
            return;
        }

        // Construir mapa currency_id => precio
        $cardPrices = [];
        foreach ($allowedCurrencies as $currencyId) {
            $cardPrices[(string) $currencyId] = $cardPriceByCurrency[(string) $currencyId] ?? $defaultCardPrice;
        }

        // Escribir en campos consolidados
        BingoRoomAutomatic::where('id', $roomId)->update([
            'card_currencies' => array_map('strval', $allowedCurrencies),
            'card_prices' => $cardPrices,
        ]);
    }

    /**
     * Guardar configuración de rondas en campos consolidados de bingo_rooms_automatic.
     * Escribe directamente en prize_types, bingo_patterns, prize_values, rounds_config (JSON).
     *
     * Mapeo prize_type (frontend → DB):
     *   percentage → PCT
     *   fixed      → FIXED
     *   prize      → OTHER
     */
    private function syncRounds(string $roomId, int $roundsCount, array $roundsConfig): void
    {
        if (empty($roundsConfig)) {
            BingoRoomAutomatic::where('id', $roomId)->update([
                'rounds_config' => null,
                'prize_types' => null,
                'bingo_patterns' => null,
                'prize_values' => null,
            ]);
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

        // Escribir en campos consolidados
        BingoRoomAutomatic::where('id', $roomId)->update([
            'rounds_config' => [
                'rounds' => (string) $roundsCount,
                'prize_types' => $prizeTypes,
                'bingo_patterns' => $bingoPatterns,
                'prize_values' => $prizeValues,
                'status' => 'ACTIVE',
            ],
            'prize_types'   => $prizeTypes,
            'bingo_patterns' => $bingoPatterns,
            'prize_values'  => $prizeValues,
        ]);
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

        $cardPriceByCurrency = $room->getCardPricesConfig()['card_prices'] ?? [];
        $roundsConfig = [];

        $roundsState = $room->getRoundsConfig();
        $prizeTypeMap = ['PCT' => 'percentage', 'FIXED' => 'fixed', 'OTHER' => 'prize'];
        $prizeTypes   = $roundsState['prize_types'] ?? [];
        $patterns     = $roundsState['bingo_patterns'] ?? [];
        $prizeValues  = $roundsState['prize_values'] ?? [];

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

        $dto = BingoRoomAutomaticCreateDTO::fromArray([
            'days'                 => $room->days ?? [],
            'time'                 => $room->formatted_time,
            'roomType'             => $room->room_type_id,
            'gameMode'             => $room->game_mode_id,
            'prizePoolType'        => $room->prize_pool_type_id,
            'allowedCurrencies'    => $room->allowed_currencies ?? [],
            'amountAddedPrizePool' => $room->amount_added_prize_pool ? (float) $room->amount_added_prize_pool : null,
            'roomCommn'            => (float) $room->room_commission,
            'useBaseUsd'           => (bool) $room->use_base_usd,
            'cardPriceUsd'         => $room->card_price_usd !== null ? (float) $room->card_price_usd : null,
            'usdExchangeRoundStep' => $room->usd_exchange_round_step !== null ? (float) $room->usd_exchange_round_step : null,
            'cardPriceByCurrency'  => $cardPriceByCurrency,
            'jackpotPct'           => $room->jackpot_pct ? (float) $room->jackpot_pct : null,
            'timeChip'             => (int) $room->time_chip,
            'rounds'               => (int) $room->rounds,
            'roundsPct'            => $room->rounds_pct ? (float) $room->rounds_pct : null,
            'useSerials'           => (bool) $room->use_serials,
            'serialsPct'           => $room->serials_pct ? (float) $room->serials_pct : null,
            'numSerials'           => $room->num_serials ? (int) $room->num_serials : null,
            'roundsConfig'         => $roundsConfig,
            'activateRoom'              => true,
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
