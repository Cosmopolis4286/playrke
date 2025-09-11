<?php

namespace App\Traits\Games;

// Models
use App\Models\BingoCreator;
use App\Models\BingoCreatorSale;
use App\Models\SellerRoomLimits;
use App\Models\BingoCtns;
use App\Models\BingoDraw;
use App\Models\BingoDrawCode;
use App\Models\BingoCtnsLot;
use App\Models\BingoCtnsPayment;
use App\Models\BingoPatterns;
use App\Models\BingoPrizeCtns;
use App\Models\BingoPrizeCtnsPayment;
use App\Models\BingoRoomConfig;
use App\Models\BingoRound;
use App\Models\BingoRoundDraft;
use App\Models\BingoRoomUser;
use App\Models\Currencies\CurrencyGameRoom;
use App\Models\Country;
use App\Models\Customer;
use App\Models\CustomerUser;
use App\Models\GameExchangeRate;
use App\Models\GamePrizes;
use App\Models\GameRoomJackpot;
use App\Models\Games;
use App\Models\ExternalPlayers;
use App\Models\Games\Bingo\Rooms\BingoRoom;
use App\Models\Seller;
use App\Models\User;
use App\Models\Role;
use App\Models\Wallet;

// Services
use App\Services\Games\Bingo\HandleBingoRooms;
use App\Services\Users\Customer\HandleTransactions;

// Traits
use App\Traits\Users\ExternalPlayersTrait;
use App\Traits\Users\UserValTrait;

// Otros
use Exception;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\Log;

trait BingoTrait
{
    use GameTrait, ExternalPlayersTrait, SeamlessWallet, UserValTrait;

    private function encryptDraw($room)
    {
        // Revisa qué números ya tienen símbolo en esta sala
        $existingNums = BingoDrawCode::where('room', $room)->pluck('num')->all();

        // Si ya existen los 10 números codificados (0 a 9), no hacer nada
        if (count($existingNums) >= 10) return false;

        $newSymbols = collect();
        $timestamp = now();

        DB::beginTransaction();

        try {
            for ($i = 0; $i <= 9; $i++) {
                if (in_array($i, $existingNums)) {
                    continue;
                }

                do {
                    $code = Str::random(3);
                    $exists = BingoDrawCode::where('room', $room)->where('sym', $code)->exists();
                } while ($exists); // Reintentar hasta que sea único

                $newSymbols->push([
                    'num' => $i,
                    'sym' => $code,
                    'room' => $room,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ]);
            }

            if ($newSymbols->isNotEmpty()) {
                BingoDrawCode::insert($newSymbols->toArray());
            }

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Error al crear códigos de símbolos para la sala de bingo {$room}: {$e->getMessage()}");
            return false;
        }
    }

    public function handleDraw($room, array $roomUser = [])
    {
        $roomStatus = BingoRoom::where('id', $room)->value('status');
        if ($roomStatus != 2) return true;

        if (!BingoRoomUser::where('room', $room)->exists()) {
            BingoRoom::where('id', $room)->update(['status' => 1]);
            return ['Error' => 'No hay jugadores en la sala'];
        }

        $roomInfo = BingoRoomConfig::getByRoom($room, ['date', 'time', 'rounds']);
        $dataTime = strtotime("{$roomInfo->date} {$roomInfo->time}");
        $rounds = (int) $roomInfo->rounds;
        $timeChip = BingoRoomConfig::getValueByRoom($room, 'time_chip') ?? 6;

        // Verificar si existen draws previos
        $hasDraws = BingoDraw::where('room', $room)->where('time', '>', 0)->exists();

        // Si no hay draws generados, los creamos para cada ronda
        if (!$hasDraws) {

            $this->encryptDraw($room);

            $symbols = BingoDrawCode::where('room', $room)->pluck('sym', 'num');
            $bingoRoundDraft = BingoRoundDraft::where('bingo_room_id', $room)->first();

            $allDraws = [];
            $allRounds = [];

            $lastDrawTime = $dataTime;

            for ($round = 1; $round <= $rounds; $round++) {

                $currentTimestamp = now();

                // Preparar info de las rondas
                $playType = $bingoRoundDraft->bingo_patterns[$round - 1];
                $prizeType = $bingoRoundDraft->prize_types[$round - 1];
                $prizeValue = $bingoRoundDraft->prize_values[$round - 1];

                $allRounds[] = [
                    'room' => $room,
                    'round' => $round,
                    'start_time' => $lastDrawTime,
                    'bingo_pattern_id' => $playType,
                    'prize_type' => $prizeType,
                    'prize_value' => $prizeValue,
                    'status' => 'ACTIVE',
                    'created_at' => $currentTimestamp,
                    'updated_at' => $currentTimestamp,
                ];

                $availableChips = range(1, 75);
                shuffle($availableChips);

                foreach ($availableChips as $chip) {
                    $encoded = implode('', array_map(fn($d) => $symbols[$d == '0' ? 10 : $d] ?? '', str_split($chip)));
                    $lastDrawTime += ($chip == reset($availableChips)) ? 8 : $timeChip;

                    $allDraws[] = [
                        'chip' => $chip,
                        'code' => $encoded,
                        'room' => $room,
                        'round' => $round,
                        'time' => $lastDrawTime,
                        'created_at' => $currentTimestamp,
                        'updated_at' => $currentTimestamp,
                    ];
                }
            }

            DB::beginTransaction();

            try {
                BingoDraw::insert($allDraws);
                BingoRound::insert($allRounds);
                $bingoRoundDraft->update(['status' => 'FINISHED']);
                DB::commit();
            } catch (\Throwable $e) {
                DB::rollBack();
                Log::error('Error al insertar draws: ' . $e->getMessage());
            }
        }

        // Si hay draws existentes y hay un usuario de sala y es tipo 2
        if ($hasDraws && !empty($roomUser) && $this->user(['user_type']) == 2) {

            // Cantidad de fichas aleatorias a insertar al principio
            $randomChipCount = $roomUser['randomChip'] ?? 4;

            // Determinar la ronda a usar
            $roundToUse = $rounds > 1
                ? BingoDraw::where('room', $room)->max('round')
                : 1;

            // Obtener el cartón del usuario
            $ctn = BingoCtns::where('room', $room)
                ->where('id', $roomUser[''])
                ->inRandomOrder()
                ->first();

            if (!$ctn) return false;

            // Campos de BINGO (excepto el centro 'n3')
            $fields = collect(['b', 'i', 'n', 'g', 'o'])
                ->flatMap(fn($col) => $col === 'n'
                    ? ["{$col}1", "{$col}2", "{$col}3", "{$col}4"]
                    : ["{$col}1", "{$col}2", "{$col}3", "{$col}4", "{$col}5"]);

            // Fichas del cartón
            $ctnChips = collect($ctn)->only($fields)->values()->shuffle()->all();

            // Fichas que no están en el cartón del usuario, en esta ronda
            $drawChips = BingoDraw::where('room', $room)
                ->where('round', $roundToUse)
                ->whereNotIn('chip', $ctnChips)
                ->inRandomOrder()
                ->pluck('chip')
                ->toArray();

            // Armar nuevo orden: aleatorias + del cartón + resto
            $newDrawOrder = array_merge(
                array_slice($drawChips, 0, $randomChipCount),
                $ctnChips,
                array_slice($drawChips, $randomChipCount)
            );

            // Obtener IDs de la ronda
            $drawIds = BingoDraw::where('room', $room)
                ->where('round', $roundToUse)->pluck('id')->toArray();

            // Asociar IDs con nuevo orden de chips
            $updateData = array_combine($drawIds, array_map('intval', $newDrawOrder));

            DB::beginTransaction();
            try {
                $sql = 'UPDATE bingo_draws SET chip = CASE id ';
                $bindings = [];

                foreach ($updateData as $id => $chip) {
                    $sql .= 'WHEN ? THEN ? ';
                    $bindings[] = $id;
                    $bindings[] = $chip;
                }

                $sql .= 'END WHERE room = ? AND round = ?';
                $bindings[] = $room;
                $bindings[] = $roundToUse;

                DB::update($sql, $bindings);
                DB::commit();

                return true;
            } catch (\Throwable $e) {
                DB::rollBack();
                Log::error('Error actualizando chips para usuario en ronda ' . $roundToUse . ': ' . $e->getMessage());
            }
        }

        if (!BingoDraw::getWinningChip($room) || $roomUser) {
            return $this->chipWin($room);
        }

        return false;
    }

    private static function buildChipIndex(int $roomId, int $playType, int $chunkSize = 100): array
    {
        $bingoPattern = ($playType == 1)
            ? ['type' => 'UNIQUE', 'columns' => array_diff(BingoCtns::ALL_BOXES, ['N3'])]
            : BingoPatterns::getColumnsBoxesById($playType);

        if (empty($bingoPattern['type']) || empty($bingoPattern['columns'])) {
            throw new ModelNotFoundException("No se encontró patrón válido para playType: $playType");
        }

        $chipIndex = [];

        try {
            if ($bingoPattern['type'] === 'GROUP') {
                $groupedColumns = $bingoPattern['columns'];
                $columnsToSelect = array_unique(array_merge(['id'], ...array_values($groupedColumns)));

                BingoCtns::where('room', $roomId)
                    ->select($columnsToSelect)
                    ->chunk($chunkSize, function ($cartons) use (&$chipIndex, $groupedColumns, $playType) {
                        foreach ($cartons as $carton) {
                            foreach ($groupedColumns as $groupId => $columns) {
                                foreach ($columns as $column) {
                                    $chip = $carton->$column ?? null;
                                    if ($chip !== null) {
                                        $chipIndex[$playType][$groupId][$column][$chip][] = $carton->id;
                                    }
                                }
                            }
                        }
                    });
            } else {
                $columns = array_unique(array_merge(['id'], $bingoPattern['columns']));

                BingoCtns::where('room', $roomId)
                    ->select($columns)
                    ->chunk($chunkSize, function ($cartons) use (&$chipIndex, $playType, $bingoPattern) {
                        foreach ($cartons as $carton) {
                            foreach ($bingoPattern['columns'] as $column) {
                                $chip = $carton->$column ?? null;
                                if ($chip !== null) {
                                    $chipIndex[$playType][$column][$chip][] = $carton->id;
                                }
                            }
                        }
                    });
            }
        } catch (\Exception $e) {
            Log::error("Error al construir chipIndex: " . $e->getMessage(), compact('roomId', 'playType'));
            throw $e;
        }

        return $chipIndex;
    }

    /**
     * Obtiene la última ficha que causó al menos una coincidencia ganadora en un cartón.
     * La función detecta si los datos están organizados por grupos (GROUP) o son únicos (UNIQUE)
     * y llama a `getLastMatchingChip` con el formato adecuado.
     *
     * @param int $roomId   ID de la sala de juego.
     * @param int $round    Número de ronda actual.
     * @param int $playType Tipo de juego (usado para obtener el índice correcto).
     * @return string|null  Última ficha válida que causó ganancia, o null si no hay coincidencias.
     */
    private function getLastWinningChip(int $roomId, int $round, int $playType): ?string
    {
        // Construye el índice de fichas para la sala y tipo de juego
        $chipIndex = $this->buildChipIndex($roomId, $playType);

        // Verifica que haya datos
        if (empty($chipIndex[$playType])) {
            Log::warning("Índice de fichas vacío para roomId={$roomId}, playType={$playType}");
            return null;
        }

        $indexedData = $chipIndex[$playType];

        // Detecta si se trata de un formato GROUP (grupoId => columnas => chips)
        $firstSub = reset($indexedData);
        $isGroup = is_array($firstSub) && is_array(reset($firstSub));

        // Llama a la función que obtiene la última ficha que generó ganancia
        return $this->getLastMatchingChip($roomId, $round, $indexedData, $isGroup);
    }

    /**
     * Devuelve la última ficha jugada (cronológicamente más antigua) que provocó una coincidencia válida en al menos un cartón.
     * Soporta tanto estructuras simples como estructuras agrupadas por grupo.
     *
     * @param int $roomId      ID de la sala.
     * @param int $round       Ronda actual.
     * @param array $chipIndex Índice de fichas. Puede ser:
     *                          - [chip => [cartonIds]] para tipo UNIQUE
     *                          - [groupId => [col => [chip => [cartonIds]]]] para tipo GROUP
     * @param bool $isGroup    Indica si se trata de un índice agrupado (GROUP).
     * @return string|null     Última ficha que generó coincidencia, o null si ninguna lo hizo.
     */
    private function getLastMatchingChip(int $roomId, int $round, array $chipIndex, bool $isGroup = false): ?string
    {
        // Obtiene las fichas jugadas en orden descendente (última jugada primero)
        $drawnChips = BingoDraw::where('room', $roomId)
            ->where('round', $round)
            ->orderByDesc('id')
            ->pluck('chip')
            ->toArray();

        // Modo GROUP: recorrer cada grupo y calcular la última ficha válida de cada uno
        if ($isGroup) {
            $lastChipsOfEachGroup = [];

            foreach ($chipIndex as $groupId => $columns) {
                $flatGroupChipIndex = [];

                // Aplana el índice de fichas del grupo actual
                foreach ($columns as $chips) {
                    foreach ($chips as $chip => $cartonIds) {
                        if (!isset($flatGroupChipIndex[$chip])) {
                            $flatGroupChipIndex[$chip] = [];
                        }

                        // Combina y elimina duplicados
                        // Forzar que siempre sea un array
                        $flatGroupChipIndex[$chip] = array_unique(array_merge(
                            $flatGroupChipIndex[$chip],
                            (array) $cartonIds
                        ));
                    }
                }

                // Recursivamente obtiene la última ficha válida del grupo actual
                $lastChipsOfEachGroup[$groupId] = $this->getLastMatchingChip($roomId, $round, $flatGroupChipIndex, false);
            }

            // Filtra las fichas válidas (no null) y elimina duplicados
            $validChips = array_filter($lastChipsOfEachGroup);
            $validChips = array_unique(array_values($validChips));

            // Recorre las fichas jugadas en orden original (primera jugada primero)
            // para encontrar cuál fue la última que causó coincidencia
            foreach (array_reverse($drawnChips) as $chip) {
                if (in_array($chip, $validChips, true)) {
                    return $chip;
                }
            }

            return null;
        }

        // Modo UNIQUE: índice plano de chip => [cartonIds]
        $matchedCartons = [];  // Evita contar el mismo cartón varias veces
        $winningChips = [];    // Fichas que han causado coincidencia

        foreach ($drawnChips as $chip) {
            if (empty($chipIndex[$chip])) {
                continue;
            }

            foreach ($chipIndex[$chip] as $ctnId) {
                // Si este cartón no ha sido contado antes
                if (!isset($matchedCartons[$ctnId])) {
                    $matchedCartons[$ctnId] = true;
                    $winningChips[$chip] = true;
                }
            }
        }

        // Recorre las fichas jugadas en orden original (primera jugada primero)
        foreach (array_reverse($drawnChips) as $chip) {
            if (isset($winningChips[$chip])) {
                return $chip;
            }
        }

        return null;
    }

    /**
     * Procesa las fichas ganadoras para cada ronda en una sala de Bingo.
     * Marca la ficha ganadora como "ganadora" y actualiza la información de la ronda.
     *
     * Si no hay jugadores o falta información esencial, la transacción se cancela.
     *
     * @param int $roomId ID de la sala
     * @return array|string|null Devuelve la primera ficha ganadora o un mensaje de error
     */
    public function chipWin($roomId)
    {
        DB::beginTransaction();

        try {
            // Verificar si hay jugadores en la sala
            if (!BingoRoomUser::where('room', $roomId)->exists()) {
                // No hay jugadores: marcar sala como "abierta" y limpiar tiempos de fichas
                BingoRoom::where('id', $roomId)->update(['status' => 1]);
                BingoDraw::where('room', $roomId)->update(['time' => 0]);

                DB::commit(); // Confirmar cambios antes de salir
                return ['Error' => 'No hay jugadores en la sala'];
            }

            // Obtener cantidad de rondas y tipos de juego asociados a cada una
            $roundsCount = (int) BingoRoomConfig::getValueByRoom($roomId, 'rounds');
            $roundPlayTypes = BingoRound::where('room', $roomId)->pluck('bingo_pattern_id', 'round');

            $firstChipDraw = null;

            // Recorrer todas las rondas
            for ($round = 1; $round <= $roundsCount; $round++) {
                $playType = $roundPlayTypes[$round] ?? null;

                // Si no hay tipo de juego para esta ronda, abortar
                if (!$playType) {
                    DB::rollBack();
                    return ['Error' => "No se encontró un tipo de juego para la ronda #{$round}"];
                }

                // Obtener la última ficha que provocó una coincidencia ganadora
                $winningChip = $this->getLastWinningChip($roomId, $round, $playType);

                // Obtener información de la ficha ganadora
                $chipDraw = BingoDraw::select('id', 'time', 'chip', 'status')
                    ->where('room', $roomId)
                    ->where('round', $round)
                    ->where('chip', $winningChip)
                    ->first();

                // Validar que se haya encontrado una ficha
                if (!$chipDraw) {
                    DB::rollBack();
                    return ['Error' => "No se encontró la ficha ganadora para la ronda #{$round}"];
                }

                // Marcar la ficha como "ganadora"
                $chipDraw->update(['status' => 3]);

                // Actualizar información de la ronda
                BingoRound::where('room', $roomId)
                    ->where('round', $round)
                    ->update([
                        'start_time' => $chipDraw->time,
                        'last_chip' => $chipDraw->chip,
                    ]);

                // Guardar la primera ficha ganadora
                if ($round === 1) {
                    $firstChipDraw = $chipDraw;
                }
            }

            // Registrar tasa de cambio del juego
            $this->insertGameExchangeRate($roomId, 'BINGO');

            DB::commit(); // Confirmar todos los cambios
            return $firstChipDraw;
        } catch (\Exception $e) {
            // Si ocurre cualquier error, revertir la transacción
            DB::rollBack();
            Log::error("Error al procesar chipWin para sala {$roomId}: {$e->getMessage()}");
            return null;
        }
    }

    public function getInfoWinners($data, $roomUser)
    {
        $roomId = BingoRoom::getValueByEncrypted($data['room'], 'id');

        $this->handleWinners($roomId);

        $winners = $this->getWinners('ALL', $roomId, $data['currency'], $roomUser);

        return $winners;
    }

    public function generateRandomNumbers($start, $end, $count)
    {
        $numbers = [];
        while (count($numbers) < $count) {
            $randomNumber = mt_rand($start, $end);
            if (!in_array($randomNumber, $numbers)) {
                $numbers[] = $randomNumber;
            }
        }
        return $numbers;
    }

    // Función para obtener detalles de la sala
    private function getRoomDetails($roomId)
    {
        return BingoRoom::with('Config:room,game_mode')->select(
            'id',
            'players',
            'ctns',
            'all_sale',
            'rke_commn',
            'room_commn',
            'seller_commn',
            'prize_pool',
            'full_house_prize',
        )->where('status', 2)->where('id', $roomId)->first();
    }

    // Función para calcular la comisión del vendedor
    private function calculateSellerCommission($seller, $saleAmount, $roomId)
    {
        if (!$seller) return 0;

        $commnSeller = SellerRoomLimits::getGameRoomValueBySeller('BINGO', $seller, $roomId, 'commission') ?? Seller::getValueById($seller, 'commission');
        // Registar la comision del vendedor para la sala de jeugo
        SellerRoomLimits::firstOrCreate(['game' => 1, 'room' => $roomId, 'seller' => $seller], ['commission' => $commnSeller]);

        $commnSeller = ($saleAmount * $commnSeller) / 100;

        return $commnSeller ? round($commnSeller, 2) : 0;
    }

    // Función para crear el lote de cartones
    private function createCtnsLot($arrCtn, $saleAmount)
    {
        $seller = $arrCtn['seller'] ?? null;

        if ($seller) {
            $sellerCommn = Seller::getValueById($seller, 'commission') / 100;
            $sellerCommn = round($saleAmount * $sellerCommn, 2);
        }

        $creatorUser = $arrCtn['creator']['userId'];
        $roomId = $arrCtn['room'];
        $currency = $arrCtn['currency'];

        $currentTime = now();

        try {

            $BingoCtnsLotID = BingoCtnsLot::insertGetId([
                'creator_user_id' => $creatorUser,
                'seller' => $seller,
                'player_user' => $arrCtn['playerUser'] ?? null,
                'room_user' => $arrCtn['roomUser'],
                'room' => $roomId,
                'ctns' => $arrCtn['numOfCtns'],
                'currency' => $currency,
                'card_price' => $arrCtn['ctnPrice'],
                'exchange_rate' => $arrCtn['exchangeRate'],
                'sale_amount' => $saleAmount,
                'seller_commn' => $sellerCommn ?? null,
                'status' => 0,
                'created_at' => $currentTime,
                'updated_at' => $currentTime,
            ]);

            // Actualizar monto de venta total por monedas
            BingoCreatorSale::updateOrCreateSale($roomId, $creatorUser, $currency, $saleAmount, $sellerCommn);

            return $BingoCtnsLotID;
        } catch (Exception $e) {
            Log::error('Error al crear lote de cartones: ' . $e->getMessage());
        }
    }

    // Función optimizada para generar y almacenar cartones de bingo usando hash
    private function generateAndStoreCtns($arrCtn, $lotID)
    {
        try {
            // Iniciales del cartón
            $initials = $arrCtn['initials'];

            $creatorType = $arrCtn['creator']['type'];

            if ($creatorType === 'customer') {
                $creatorId = $arrCtn['creator']['id'];
                $country = CustomerUser::getValueByUserId($creatorId, $arrCtn['playerUser'], 'country');
            } else {
                $country = Country::getValue($arrCtn['country'], 'iso_code_2');
            }

            // Obtener el último número de cartón creado
            $numLastCtn = BingoCtns::orderByDesc('id')->value('id') ?? 0;

            // Array para insertar registros en una sola operación
            $recordsToInsert = [];

            // Usar un set para evitar duplicados dentro del mismo lote
            $generatedHashes = [];

            for ($x = 1; $x <= $arrCtn['numOfCtns']; $x++) {
                // Generar nuevo cartón de bingo
                $nwCtn = $this->generateCompleteBingoCard();

                // Generar hash del cartón
                $cartonHash = hash('sha256', implode('-', $nwCtn));

                // Verificar si el hash ya existe en la base de datos o dentro del mismo lote generado
                if (!BingoCtns::existsCartonHashRoom($arrCtn['room'], $cartonHash) && !isset($generatedHashes[$cartonHash])) {
                    $numLastCtn++;
                    $serialCtn = "$initials$numLastCtn$country";

                    // Preparar el cartón para la inserción
                    $recordsToInsert[] = $this->prepareCtnRecord($arrCtn, $lotID, $nwCtn, $serialCtn, $cartonHash);

                    // Añadir el hash a los hashes generados en este ciclo
                    $generatedHashes[$cartonHash] = true;
                }
            }

            // Insertar todos los registros en la base de datos en una sola operación
            if (!empty($recordsToInsert)) {
                BingoCtns::insert($recordsToInsert);
            }
            return true;
        } catch (Exception $e) {
            Log::error('Error al generar cartones: ' . $e->getMessage());
            return false;
        }
    }

    // Genera un cartón completo de bingo
    private function generateCompleteBingoCard()
    {
        return array_merge(
            $this->generateRandomNumbers(1, 15, 5),
            $this->generateRandomNumbers(16, 30, 5),
            $this->generateRandomNumbers(31, 45, 4),
            $this->generateRandomNumbers(46, 60, 5),
            $this->generateRandomNumbers(61, 75, 5)
        );
    }

    // Función para preparar el registro del cartón con hash
    private function prepareCtnRecord($arrCtn, $lotID, $nwCtn, $serialCtn, $cartonHash)
    {
        return [
            'room' => $arrCtn['room'],
            'creator_user_id' => $arrCtn['creator']['userId'],
            'seller' => $arrCtn['seller'] ?? null,
            'player_user' => $arrCtn['playerUser'] ?? null,
            'room_user' => $arrCtn['roomUser'],
            'currency' => $arrCtn['currency'],
            'lot' => $lotID,
            'serial' => $serialCtn,
            'b1' => $nwCtn[0],
            'b2' => $nwCtn[1],
            'b3' => $nwCtn[2],
            'b4' => $nwCtn[3],
            'b5' => $nwCtn[4],
            'i1' => $nwCtn[5],
            'i2' => $nwCtn[6],
            'i3' => $nwCtn[7],
            'i4' => $nwCtn[8],
            'i5' => $nwCtn[9],
            'n1' => $nwCtn[10],
            'n2' => $nwCtn[11],
            'n4' => $nwCtn[12],
            'n5' => $nwCtn[13],
            'g1' => $nwCtn[14],
            'g2' => $nwCtn[15],
            'g3' => $nwCtn[16],
            'g4' => $nwCtn[17],
            'g5' => $nwCtn[18],
            'o1' => $nwCtn[19],
            'o2' => $nwCtn[20],
            'o3' => $nwCtn[21],
            'o4' => $nwCtn[22],
            'o5' => $nwCtn[23],
            'hash' => $cartonHash,
            'created_at' => now(),
            'updated_at' => now(),
        ];
    }

    // Función para registrar o actualizar el usuario de la sala
    private function registerOrUpdateRoomUser($room, array $arrCtn)
    {
        try {
            // Definir los valores por defecto para crear un nuevo registro
            $defaults = [
                'room' => $arrCtn['room'],
                'creator_user_id' => $arrCtn['creator']['userId'],
                'player_user' => $arrCtn['playerUser'] ?? null,
                'ctns' => $arrCtn['numOfCtns'],
                'automarker' => 1,
            ];

            // Buscar o crear el usuario en la sala
            $roomUser = BingoRoomUser::find($arrCtn['roomUser'] ?? null);

            if (!$roomUser) {
                $roomUser = BingoRoomUser::create($defaults);
                $room->increment('players');
            } else {
                $roomUser->increment('ctns', $arrCtn['numOfCtns']);
            }

            return $roomUser->id;
        } catch (\Throwable $e) {
            Log::error('Error al registrar o actualizar el usuario de sala: ' . $e->getMessage());
            return false;
        }
    }

    // Función para actualizar el bote acumulado y otras métricas de la sala
    private function updateRoomMetrics($room, $arrCtn, $saleAmount, $sellerSaleCommn)
    {
        try {
            // Recalcular venta y comisiones en base a USDT
            $exchangeRate = $arrCtn['exchangeRate'];

            $saleAmount = round($saleAmount / $exchangeRate, 3);
            $sellerSaleCommn = round($sellerSaleCommn / $exchangeRate, 3);

            $bingoConfig = BingoRoomConfig::getByRoom($room->id, ['rke_commn', 'room_commn', 'prize_pool_type']);

            $prizePoolType = $bingoConfig->prize_pool_type;

            // Si el bote acumulado no es tipo "Premio"
            if ($prizePoolType != 2) {
                // Obtener comision del creador/casa (comision por sala)\
                $creatorCommn = round(($saleAmount * $bingoConfig->room_commn) / 100, 3);
                // Comision de PlayRKE
                $rkeCommn = round(($saleAmount * $bingoConfig->rke_commn) / 100, 3);
                // Restar al bote las comisiones 
                $prizePool = $saleAmount - $rkeCommn - $creatorCommn - $sellerSaleCommn;
            }

            // Preparar los datos para actualizar la sala
            $updateData = [
                'all_sale' => $room->all_sale + $saleAmount,
                'ctns' => $room->ctns + $arrCtn['numOfCtns'],
                'rke_commn' => $room->rke_commn + $rkeCommn,
                'room_commn' => $room->room_commn + $creatorCommn
            ];

            if ($prizePoolType != 2) {
                $updateData['prize_pool'] = $room->prize_pool + $prizePool;
                $updateData['full_house_prize'] = $room->full_house_prize + $prizePool;
            }

            if (isset($arrCtn['seller'])) {
                $updateData['seller_commn'] = $room->seller_commn + $sellerSaleCommn;
            }

            // Actualiza la sala con los datos preparados
            return $room->update($updateData);
        } catch (Exception $e) {
            Log::error('Error al actualizar los datos de la sala: ' . $e->getMessage());
            return false;
        }
    }

    public function calculateCtnPrice($bingoCtnPrice, $setPrice = false, $exchangeRate = 1)
    {
        // Calcular precio del carton en USDT 
        $formattedPrice = round($exchangeRate * $bingoCtnPrice, 2);

        if ($setPrice) {
            // Calcular precio del carton en USDT y redondear de acuerdo a la moneda 
            $formattedPrice = round($formattedPrice / $exchangeRate, 3);

            if (fmod($formattedPrice, 1) == 0) {
                $formattedPrice = round($formattedPrice, 2);
            }
        }

        return $formattedPrice;
    }

    public function cartonStore($arrCtn)
    {
        app(HandleBingoRooms::class)->checkRoomClosure();

        $roomId = $arrCtn['room'];

        $room = $this->getRoomDetails($roomId);

        if (!$room) return ['Error' => 'Sala no disponible'];

        try {
            // Inicialización de variables
            $creator = $arrCtn['creator'];
            $seller = $arrCtn['seller'] ?? null;
            $playerUser = $arrCtn['playerUser'] ?? null;
            $currency = $arrCtn['currency'];
            $numOfCtns = $arrCtn['numOfCtns'];

            // Formatear Monto de Venta
            $saleAmount = round($numOfCtns * $arrCtn['ctnPrice'], 2);

            // Registrar o actualizar el usuario en la sala
            $roomUser = $this->registerOrUpdateRoomUser($room, $arrCtn);

            if (!$roomUser) {
                return ['Error' => 'Registrar o actualizar el usuario en la sala'];
            }

            // Anexar atributo al array
            $arrCtn['roomUser'] = $roomUser;

            $gameMode = $room->Config->game_mode;

            if ($gameMode != 1 && ($creator['type'] === 'commerce_id' || $seller)) {
                // Calcular monto total de venta y comisiones si es un vendedor
                $sellerSaleCommn = $this->calculateSellerCommission($seller, $saleAmount, $roomId);
            }

            // Actualizar acumulado y comisiones de la sala
            $updateRoomMetrics = $this->updateRoomMetrics($room, $arrCtn, $saleAmount, $sellerSaleCommn ?? 0);

            // Verificar si se actualizó los valores de la sala
            if (!$updateRoomMetrics) {
                return ['Error' => 'Error al actualizar los valores de la sala'];
            }

            // Crear lote de cartones
            $lotID = $this->createCtnsLot($arrCtn, $saleAmount);

            // Generar y almacenar los cartones de bingo
            $generateAndStoreCtns = $this->generateAndStoreCtns($arrCtn, $lotID);

            if (!$generateAndStoreCtns) {
                return ['Error' => 'Error al generar cartones'];
            }

            // Si no es modo offline y el creador es <> a un customer 
            if ($gameMode != 2 && $creator['type'] !== 'customer') {
                // Determinar la columna de saldo en función de la moneda
                $columnBalance = $currency == 1 ? 'usdt' : 'balance';

                // Descontar monto del saldo del usuario
                $walletUpdated = Wallet::where('user', $playerUser)->decrement($columnBalance, $saleAmount);

                // Verificar si se actualizó algún registro
                if ($walletUpdated === 0) {
                    return ['Error' => 'Error al descontar saldo del usuario'];
                }
            }

            // Definir variables dobre el juego
            $game = ['name' => 'BINGO', 'game_mode' => $gameMode];

            //Registrar nueva moneda en juego de ser necesario
            if (!CurrencyGameRoom::existsCurrencyGameRoom($currency, $roomId, 'BINGO', $gameMode)) {
                CurrencyGameRoom::createNewCurrencyInRoom($game, $roomId, $currency);
            }

            // Si la sala es de un cliente
            if ($creator['type'] === 'customer') {
                // Registrar transacción (se consulta nuvamente el saldo del juagdor)
                $roomId = BingoRoom::getValueById($roomId, 'uuid_code') ?? $this->getCodeUUID('BingoRoom');

                $transactionBet = $this->handleTransactionByBET(
                    $game,
                    $creator['id'],
                    $roomId,
                    $playerUser,
                    $currency,
                    $saleAmount
                );

                if (isset($transactionBet['Error'])) {
                    return ['Error' => 'Ha ocurrido un error'];
                }
            }

            // Generar un nuevo sorteo si aplica
            if (isset($arrCtn['ctnWin'])) {
                $this->handleDraw($roomId, $roomUser);
            }

            return $roomUser;
        } catch (Exception $e) {
            Log::error('Error al crear los cartones cartonStore(): ' . $e->getMessage());
            return ['Error' => 'Error al crear los cartones'];
        }
    }

    public function convertPrizeValue($roomId, $currency, $value)
    {
        $room = BingoRoom::getById($roomId, ['creator_user_id', 'prize_pool']);

        $creatorUser = $room->creator_user;
        $userCreator = User::getCreatorUser($creatorUser, false, true);

        $creator = ['type' => $userCreator['type'], 'id' => $userCreator['id'], 'userId' => $creatorUser];

        $data = ['currency' => $currency, 'value' => $value, 'creator' => $creator];

        return $this->convertValueByGame('BINGO', $roomId, $data);
    }

    private function registerPrizeCtn($roomId, $round, $cartons, $pattern, $USDValue)
    {
        if ($pattern !== 'SRL') $pattern = $pattern == 1 ? 'FLHE' : 'SONG';

        $recordsToInsert = $playersToUpdate = [];

        foreach ($cartons as $ctn) {

            if (!BingoPrizeCtns::existsPrizeCtn($ctn->id, $round)) {

                // Definir $roomUser
                $roomUser = $ctn->room_user;

                // Verificar y obtener el tipo de cambio
                $exchangeRate = GameExchangeRate::getGameExchangeRateValue('BINGO', $roomId, $ctn->currency) ?? 1;

                // Multiplicamos por el exchangeRate
                $convertedAmount = $USDValue * $exchangeRate;

                $prizeAmount = round($convertedAmount, 2);

                $timestamp = now();

                $record = [
                    'active' => 'N',
                    'room' => $roomId,
                    'round' => $round,
                    'creator_user_id' => $ctn->creator_user,
                    'seller' => $ctn->seller,
                    'room_user' => $roomUser,
                    'ctn' => $ctn->id,
                    'lot' => $ctn->lot,
                    'prize_type' => $pattern,
                    'usd_amount' => $USDValue,
                    'currency' => $ctn->currency,
                    'amount' => $prizeAmount,
                    'status' => 'WT',
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp
                ];

                // Añadimos el registro al array de inserciones
                $recordsToInsert[] = $record;

                // Determinar los jugadores a actualizar
                $playersToUpdate[] = $roomUser;
            }
        }

        DB::beginTransaction();

        try {

            if ($playersToUpdate && $recordsToInsert) {

                // Actualizar el estado del jugador 
                BingoRoomUser::where('room', $roomId)->where('winner', '0')
                    ->whereIn('id', $playersToUpdate)->update(['winner' => '1']);

                //Insertar los registros de una sola vez
                BingoPrizeCtns::insert($recordsToInsert);

                $roomType = BingoRoom::getValueById($roomId, 'room_type');

                if ($roomType === 'PB') {
                    // Manejar los pagos pendientes de los BingoRoomCreators
                    $this->handlePublicRoomPendingPayments($roomId);
                }
            }

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al registrar carton ganador ===> $prizeType [' . $pattern . '] : ' . $e->getMessage());
            return false;
        }
    }

    private function handlePublicRoomPendingPayments($roomId)
    {
        // Manejador de pagos pendientes de los BingoRoomCreators
        $bingoCreators = BingoCreator::where('room', $roomId)->count();

        if ($bingoCreators === 1) return;

        $recordsToInsert = [];

        $bingoCreatorSales = BingoCreatorSale::where('room', $roomId)->get();

        try {
        } catch (Exception $e) {
            Log::error('Error al insertar los registros de pagos pendientes para la sala {$roomId}:' . $e->getMessage());
        }
    }

    private function getCurrencyByPlayer($creator, $playerId)
    {
        switch ($creator) {
            case 'customer':
                $currency = CustomerUser::getValueById($playerId, 'currency');
                break;
            case 'commerce_id':
                $currency = ExternalPlayers::getValueByRoomUser('BINGO', $playerId, 'currency');
                break;
            default:
                break;
        }

        return $currency;
    }

    public function getWinnersData($prizeType, $roomId, $roomUser = null)
    {
        $room = BingoRoom::select('creator_user_id')
            ->where('id', $roomId)
            ->where('status', '>=', 3)
            ->first();

        if (!$room) {
            return ['Error' => 'Sala no disponible'];
        }

        $round = BingoRound::getValueByRoundInProgress($roomId, 'round');

        $query = BingoPrizeCtns::where('room', $roomId)->where('round', '<=', $round);

        // Si se especifica un tipo de premio (que no sea 'ALL')
        if ($prizeType !== 'ALL') {
            $typeValue = GamePrizes::getValueByDescription('BINGO', $prizeType);

            if (!$typeValue) {
                return ['Error' => "El prizeType '{$prizeType}' no existe"];
            }

            $query->where('prize_type', $typeValue);
        }

        $winners = $query->get(['room_user', 'round', 'ctn', 'prize_type', 'usd_amount', 'amount']);

        $winPlayer = false;

        if ($roomUser) {
            $winPlayer = BingoRoomUser::where('id', $roomUser)->where('winner', '1')->exists();
        }

        return [
            'prizeCtns' => $winners,
            'room' => $roomId,
            'winPlayer' => $winPlayer,
        ];
    }

    public function getArrayWinnersData($data)
    {
        $exchangeRate = GameExchangeRate::getGameExchangeRateValue('BINGO', $data['room'], $data['currency']) ?? 1;
        $bingoRounds = BingoRound::where('room', $data['room'])
            ->get(['round', 'bingo_pattern_id', 'prize_value', 'start_time', 'last_chip'])
            ->keyBy('round');

        $winnersByRound = [
            'numW' => count($data['prizeCtns']),
            'rounds' => [],
        ];

        // Cache para contar ganadores por ronda (evita contar repetidamente)
        $roundCounts = [];

        foreach ($data['prizeCtns'] as $prizeCtn) {

            $ctnId = $prizeCtn->ctn;
            $round = $prizeCtn->round;
            $startTime = $bingoRounds[$round]->start_time;
            $lastChip = $bingoRounds[$round]->last_chip;
            $prizeValue = $bingoRounds[$round]->prize_value;

            if ($prizeCtn->prize_type === 'SRL') {
                $prizeType = 'SERIALES';
            } else if ($prizeCtn->prize_type === 'FLHE') {
                $prizeType = 'CARTON LLENO';
            } else {
                $prizeType = BingoPatterns::getValueById($bingoRounds[$round]->bingo_pattern_id, 'name');
            }

            $serialCtn = BingoCtns::getValueById($ctnId, 'serial');
            $amount = round($prizeCtn->usd_amount * $exchangeRate, 2);
            $formattedAmount = number_format($amount, 2, '.', ',');
            $encryptedCtn = encrypt($ctnId);

            // Cachear conteo de ganadores por ronda
            if (!isset($roundCounts[$round])) {
                $roundCounts[$round] = $data['prizeCtns']->where('round', $round)->count();
            }

            // Inicializar la ronda si no existe
            if (!isset($winnersByRound['rounds'][$round])) {
                $winnersByRound['rounds'][$round] = [
                    'numWR' => $roundCounts[$round],
                    'startTime' => $startTime,
                    'lastChip' => $lastChip,
                    'prizeType' => []
                ];
            }

            // Inicializar tipo de premio si no existe
            if (!isset($winnersByRound['rounds'][$round]['prizeType'][$prizeType])) {
                $winnersByRound['rounds'][$round]['prizeType'][$prizeType] = [
                    'numWRPT' => 0,
                    'prizeVal' => $prizeValue,
                    'players' => [],
                ];
            }

            // Inicializar jugador si no existe
            if (!isset($winnersByRound['rounds'][$round]['prizeType'][$prizeType]['players'][$serialCtn])) {
                $winnersByRound['rounds'][$round]['prizeType'][$prizeType]['players'][$serialCtn] = [
                    'ctn' => $encryptedCtn,
                    'gain' => $formattedAmount,
                ];
            }

            // Incrementar contador por tipo de premio
            $winnersByRound['rounds'][$round]['prizeType'][$prizeType]['numWRPT']++;
        }

        return $winnersByRound;
    }

    private function findWinners($roomId, $roundID, array $chipsDraw)
    {
        $playType = BingoRound::getValueById($roundID, 'bingo_pattern_id');

        // Columnas del patrón
        $patternColumns = ($playType == 1)
            ? array_diff(BingoCtns::ALL_BOXES, ['N3'])
            : BingoPatterns::getColumnsBoxesById($playType);

        $columns = array_unique(array_merge(['id', 'room_user'], $patternColumns));
        $winners = collect();

        BingoCtns::where('room', $roomId)
            ->select($columns)
            ->chunk(100, function ($ctns) use ($patternColumns, $chipsDraw, &$winners) {
                foreach ($ctns as $ctn) {
                    $roomUser = $ctn->room_user;

                    // Ya ganó
                    if ($winners->has($roomUser)) {
                        continue;
                    }

                    // Extraer solo los valores del patrón
                    $values = array_map(fn($col) => $ctn->$col, $patternColumns);

                    // Verifica si todos los valores están en los chips sorteados
                    if (count(array_intersect($values, $chipsDraw)) === count($patternColumns)) {
                        $winners->put($roomUser, $ctn->id);
                    }
                }
            });

        return $winners;
    }

    public function handleWinners($roomId)
    {
        $room = BingoRoom::with('Config:room,serials_pct')
            ->select('id', 'creator_user_id', 'prize_pool', 'serial_prize', 'players')
            ->findOrFail($roomId);

        // Validar si ya hay ganadores 
        if (BingoRoomUser::getWinnersCountByRoom($roomId)) return false;

        $prizePool = $room->prize_pool;
        $serialsPct = $room->Config->serials_pct;
        $applySerialPrize = false;
        $rounds = BingoRound::where('room', $roomId)->get();
        $roundsCount = $rounds->count();

        foreach ($rounds as $roundItem) {

            $roundNumber = $roundItem->round;
            $roundPrizeValue = ($prizePool * $roundItem->prize_value) / 100;

            // Si es la última ronda y hay porcentaje para seriales, ajusta premio
            if ($roundNumber === $roundsCount && $serialsPct && $room->players > 1) {
                $serialPrizeValue = ($prizePool * $serialsPct) / 100;
                $roundPrizeValue -= $serialPrizeValue;
                $applySerialPrize = true;
            }

            $roundPrizeValue = round($roundPrizeValue, 2);

            $lastChip = BingoDraw::where('room', $roomId)
                ->where('round', $roundNumber)
                ->where('status', 3)
                ->orderByDesc('id')
                ->first();

            if (!$lastChip) {
                Log::warning("No hay ficha final para la ronda $roundNumber en la sala $roomId");
                continue;
            }

            $chipsDraw = BingoDraw::where('room', $roomId)
                ->where('round', $roundNumber)
                ->where('id', '<=', $lastChip->id)
                ->pluck('chip')
                ->unique()
                ->toArray();

            if (count($chipsDraw) < 24) {
                Log::warning("Faltan fichas para validar cartón lleno en la ronda $roundNumber");
                $this->chipWin($room);
                continue;
            }

            $winners = $this->findWinners($roomId, $roundItem->id, $chipsDraw);

            if ($winners->isEmpty()) {
                Log::info("Sin ganadores en la ronda $roundNumber de la sala $roomId");
                continue;
            }

            $ctnsJackpot = BingoCtns::where('room', $roomId)
                ->whereIn('id', $winners->values())
                ->get(['id', 'creator_user_id', 'seller', 'room_user', 'currency', 'lot']);

            DB::beginTransaction();

            try {

                $jackpotPrizePerCtn = round($roundPrizeValue / max(1, $ctnsJackpot->count()), 3);

                $register = $this->registerPrizeCtn(
                    $roomId,
                    $roundNumber,
                    $ctnsJackpot,
                    $roundItem->bingo_pattern_id,
                    $jackpotPrizePerCtn
                );

                if (!$register) {
                    throw new Exception('Fallo al registrar el carton premiado.');
                }

                if ($applySerialPrize) {
                    $serialPrize = round($serialPrizeValue, 3);
                    $serialResult = $this->handleSerialWinners(
                        $roomId,
                        $roundNumber,
                        $serialPrize,
                        $winners->values()
                    );

                    if (!$serialResult) {
                        throw new Exception('Fallo al registrar premio por seriales.');
                    }
                }

                if ($roundNumber === $roundsCount) {
                    $room->update(['close_room' => now()->timestamp]);
                }

                DB::commit();
            } catch (Exception $e) {
                DB::rollBack();
                Log::error("Error en handleWinners (Room $roomId - Round $roundNumber): " . $e->getMessage());
                return response()->json(['error' => 'Error al procesar ganadores.'], 500);
            }
        }

        // Validar transacciones si es customer
        $userType = RoleRepository::getValueById(User::getValueById($room->creator_user, 'user_type'), 'prefix');

        if ($userType === 'customer') {
            $customerId = Customer::getValueByUserId($room->creator_user, 'id');
            app(HandleTransactions::class)->validateTransactions($customerId);
        }

        return true;
    }

    public function getWinners(string $prizeType, int $roomId, int $currency, ?int $roomUser = null)
    {
        $data = $this->getWinnersData($prizeType, $roomId, $roomUser);

        // Si existe algun error
        if (isset($data['Error'])) return $data['Error'];

        // Agregar la moneda al resultado
        $data['currency'] = $currency;

        return $this->getArrayWinnersData($data);
    }

    public function handleSerialWinners($roomId, $round, $serialPrize, $roomUserIds)
    {
        try {
            // Obtener Seriales Ganadores
            $getWinners = BingoPrizeCtns::getWinnersCountByPrizeType($roomId, 'SERIAL');

            if ($getWinners) return false;

            // Obtener la última ficha ganadora
            $lastChip = BingoDraw::where('room', $roomId)->where('status', 3)
                ->where('round', $round)->value('id');

            // Fichas que han salido antes del jackpot
            $chipsDraw = array_flip(
                BingoDraw::where('room', $roomId)
                    ->where('id', '<', $lastChip)
                    ->where('round', $round)
                    ->pluck('chip')->toArray()
            );

            $bingoConfig = BingoRoomConfig::getByRoom($roomId, ['serials_pct', 'num_serials']);

            $serialsPct = $bingoConfig->serials_pct / 100;
            $countSerials = $bingoConfig->num_serials;

            $countCtns = 0;
            $uniquePlayers = [];
            $serialCtns = [];

            $batchSize = 100;
            $page = 1;

            do {
                // Tomar 100 cartones aleatorios que no pertenezcan a los ganadores previos
                $batch = BingoCtns::where('room', $roomId)
                    ->whereNotIn('room_user', $roomUserIds)
                    ->inRandomOrder()
                    ->forPage($page, $batchSize)
                    ->get();

                // Si no quedan más cartones, salimos
                if ($batch->isEmpty()) break;

                foreach ($batch as $ctn) {
                    if ($countCtns >= $countSerials) break;

                    // Obtener todos los números del cartón
                    $numbers = array_filter([
                        $ctn->b1,
                        $ctn->b2,
                        $ctn->b3,
                        $ctn->b4,
                        $ctn->b5,
                        $ctn->i1,
                        $ctn->i2,
                        $ctn->i3,
                        $ctn->i4,
                        $ctn->i5,
                        $ctn->n1,
                        $ctn->n2,
                        $ctn->n4,
                        $ctn->n5,
                        $ctn->g1,
                        $ctn->g2,
                        $ctn->g3,
                        $ctn->g4,
                        $ctn->g5,
                        $ctn->o1,
                        $ctn->o2,
                        $ctn->o3,
                        $ctn->o4,
                        $ctn->o5,
                    ]);

                    // Calcular cuántos números han salido
                    $hits = 0;
                    foreach ($numbers as $number) {
                        if (isset($chipsDraw[$number])) {
                            $hits++;
                        }
                    }

                    // Validar que el cartón 23 aciertos
                    if ($hits === 23) {
                        $roomUser = $ctn->room_user;

                        // Verificar si ese jugador ya tiene un cartón premiado
                        if (!isset($uniquePlayers[$roomUser])) {
                            $uniquePlayers[$roomUser] = true;
                            $serialCtns[] = $ctn->id;
                            $countCtns++;
                        }
                    }
                }

                $page++;
            } while ($countCtns < $countSerials);

            // Si no se encontró ningún ganador, retornar falso
            if ($countCtns === 0) {

                $timestamp = now();
                $gameId = Game::getValueByName('BINGO');

                // Obtener las monedas activas en la sala
                $currencies = CurrencyGameRoom::getCurrenciesByRoom($gameId, $roomId);

                // Preparar los datos para insertar
                $dataJackpots = $currencies->map(function ($currency) use ($roomId, $gameId, $serialsPct, $timestamp) {

                    $prizePoolAmount = BingoCreatorSale::getPrizePoolAmountByRoomAndCurrency($roomId, $currency);
                    $amount = round($prizePoolAmount * $serialsPct, 3);

                    return [
                        'game' => $gameId,
                        'room' => $roomId,
                        'currency' => $currency,
                        'amount' => $amount,
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ];
                })->toArray();

                // Insertar si hay datos
                if (!empty($dataJackpots)) {
                    GameRoomJackpot::insert($dataJackpots);
                }

                Log::info("No hubo ganadores de seriales en la Sala de Bingo {$roomId}. El porcentaje se registró al jackpot acumulado.");
                return true;
            }

            // Obtener la información final de los cartones ganadores
            $ctnsSerials = BingoCtns::whereIn('id', $serialCtns)
                ->where('room', $roomId)
                ->get(['id', 'seller', 'creator_user_id', 'room_user', 'currency', 'lot']);

            // Repartir el premio entre los ganadores
            $winnerJackpotPrize = $serialPrize / count($serialCtns);

            $this->registerPrizeCtn($roomId, $round, $ctnsSerials, 'SRL', $winnerJackpotPrize);

            return true;
        } catch (Exception $e) {
            Log::error('Error en handleSerialWinners(): ' . $e->getMessage());
            return false;
        }
    }

    // Obtener Informacion de un carton especifico (encriptado)
    public function getCtnWin($ctn)
    {
        // Desencriptar ID del carton
        $ctnId = decrypt($ctn);

        // Obtener todas las casillas del carton
        $boxes = array_diff(BingoCtns::ALL_BOXES, ['N3']);

        // Obtener el cartón
        $ctn = BingoCtns::select(array_merge(['room_user', 'player_user', 'serial'], $boxes))->find($ctnId);

        // Si no se encuentra el cartón, retornar false
        if (!$ctn) return false;

        $creatorUser = $ctn->creator_user;
        $creatorUserType = User::getUserTypePrefix($creatorUser);

        if ($creatorUserType !== 'customer') {
            if ($ctn->player_user) {
                $fullName = User::getValueById($ctn->player_user, 'username');
            } else {
                $playerLink = ExternalPlayers::getByRoomUser('BINGO', $ctn->room_user, ['name', 'lastname']);
                $fullName = "$playerLink->name $playerLink->lastname";
            }
        } else {
            $fullName = Customer::getValueByUserId($creatorUser, 'name');
        }

        $prizeCtn = BingoPrizeCtns::getById($ctnId, ['room', 'round', 'prize_type']);

        $roomId = $prizeCtn->room;
        $round = $prizeCtn->round;
        $prizeType = $prizeCtn->prize_type;

        // Verificar si es un carton ganador por serial premiado
        $serialAward = $prizeType === 'SRL' ? true : false;

        // Obtener los números del cartón en un solo array
        $carton = $ctn->only($boxes);

        if ($prizeType === 'SONG') {
            $bingoPattern = BingoRound::getValueByRoom($roomId, $round, 'bingo_pattern_id');
            $checkBoxes = BingoPatterns::getColumnsBoxesById($bingoPattern);

            // Verificar las checkBoxes con respeto al sorteo (draw) de la ronda 
            $lastId = BingoDraw::where('room', $roomId)->where('round', $round)
                ->where('status', 3)->orderByDesc('id')->value('id');

            $chipDraw = $lastId
                ? BingoDraw::where('room', $roomId)->where('round', $round)
                ->where('id', '<=', $lastId)->pluck('chip')->toArray()
                : [];

            dd($checkBoxes, $chipDraw, $carton);
        } else {
            $checkBoxes = $boxes;
        }

        // Añadir clave 'check' => true a las casillas dentro de $checkBoxes
        $carton = collect($carton)->mapWithKeys(function ($value, $key) use ($checkBoxes) {
            if (in_array($key, $checkBoxes)) {
                return [$key => ['value' => $value, 'check' => true]];
            }
            return [$key => ['value' => $value]];
        })->toArray();

        // Retornar los datos del cartón
        return [
            'carton' => $carton,
            'playerName' => $fullName,
            'serialCtn' => $ctn->serial,
            'srl' => $serialAward
        ];
    }

    public function allWinCtnPaid($room)
    {
        // Verificar la cantidad total de cartones premiados y la cantidad pagada
        $winnersCount = BingoRoomUser::where('room', $room)->where('winner', '!=', '0')->count();
        $winnersPaidCount = BingoRoomUser::where('room', $room)->where('winner', '2')->count();

        // Si la cantidad total de cartones premiados es igual a la cantidad pagada,
        // entonces todos los cartones premiados han sido pagados
        if ($winnersCount == $winnersPaidCount && $winnersCount > 0) {
            // Actualizar el estado de pago de la sala de bingo
            BingoRoom::where('id', $room)->update(['paid_win' => 1]);
        }
    }

    function registerCartonPayment($payment, $lotCtn, $paymentImage)
    {
        return BingoCtnsPayment::create([
            'payment' => $payment,
            'room' => $lotCtn->room,
            'creator_user_id' => $lotCtn->creator_user,
            'seller' => $lotCtn->seller,
            'external_player_id' => ExternalPlayers::getValueByRoomUser('BINGO', $lotCtn->room_user, 'id'),
            'lot' => $lotCtn->id,
            'currency' => $lotCtn->currency,
            'amount' => $lotCtn->sale_amount,
            'payment_proof' => $paymentImage,
        ]);
    }

    function registerPrizeCartonPayment($prizeCtn, $payment, $user, $filePayment)
    {
        try {

            return BingoPrizeCtnsPayment::create([
                'prize_ctn' => $prizeCtn->id,
                'payment' => $payment,
                'room' => $prizeCtn->room,
                'creator_user_id' => $prizeCtn->creator_user,
                'seller' => $prizeCtn->seller,
                'external_player_id' => ExternalPlayers::getValueByRoomUser('BINGO', $prizeCtn->room_user, 'id'),
                'user' => $user,
                'prize_type' => $prizeCtn->prize_type,
                'lot' => $prizeCtn->lot,
                'ctn' => $prizeCtn->ctn,
                'currency' => $prizeCtn->currency,
                'amount' => $prizeCtn->amount,
                'payment_proof' => $filePayment,
            ]);
        } catch (Exception $e) {
            Log::error("'Error al guardar el registro del pago del carton ganador: " . $e->getMessage());
            return false;
        }
    }

    public function verifyExistenceOfSales($user, $currency = '', $dateFilter = '', $room = '')
    {
        $column = $user['type'] !== 'seller' ? 'creator_user_id' : 'seller';
        $columnValue = $column !== 'seller' ? $user['userId'] : $user['id'];

        $query = BingoCtnsLot::where($column, $columnValue);

        // Filtro por moneda
        if ($currency) {
            try {
                $currency = decrypt($currency);
            } catch (Exception) {
                // Si falla el descifrado, asume que viene en texto plano
            }
            $query->where('currency', $currency);
        }

        // Filtro por sala
        if ($room) {
            $query->where('room', $room);
        }

        $existsSale = $query->exists();

        // Si existe venta, verifica el filtro de fecha
        if ($existsSale && $dateFilter) {
            $existsSaleDateFilter = $this->applyDateFilter($dateFilter, $query)->exists();
            // Si no existe venta en la fecha, retorna
            if (!$existsSaleDateFilter) {
                return ['return' => true, 'currency' => encrypt($currency)];
            }
        }

        // Si no existe venta o no hay filtros de moneda ni sala
        if (!$existsSale || (!$currency && !$room)) {

            $firstSale = BingoCtnsLot::select('currency')->where($column, $columnValue)->first();

            if ($firstSale) {
                return [
                    'return' => true,
                    'currency'   => encrypt($firstSale->currency)
                ];
            }

            return encrypt(1);
        }

        // Si existe venta y cumple con todos los filtros
        return $currency ?? null;
    }
}
