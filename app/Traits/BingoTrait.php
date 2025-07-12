<?php

namespace App\Traits;

use App\Models\BingoCreator;
use App\Models\BingoCreatorSales;
use App\Models\SellerRoomLimits;
use App\Models\BingoCtns;
use App\Models\BingoRoom;
use App\Models\BingoDraws;
use App\Models\BingoDrawsCode;
use App\Models\BingoCtnsLot;
use App\Models\BingoCtnsPayment;
use App\Models\BingoPatterns;
use App\Models\BingoPrizeCtns;
use App\Models\BingoPrizeCtnsPayment;
use App\Models\BingoRoomConfig;
use App\Models\BingoRounds;
use App\Models\BingoRoundsDrafts;
use App\Models\BingoRoomUser;
use App\Models\CoinGameRoom;
use App\Models\Country;
use App\Models\Customer;
use App\Models\CustomerUser;
use App\Models\GameExchangeRate;
use App\Models\GamePrizes;
use App\Models\GameRoomJackpot;
use App\Models\Games;
use App\Models\PlayerLink;
use App\Models\Seller;
use App\Models\User;
use App\Models\UserType;
use App\Models\Wallet;
use App\Services\Customer\HandleTransactions;
use App\Services\Game\Bingo\HandleBingoRooms;
use Exception;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait BingoTrait
{
    use UserVal, GameTrait, RouteRKE, PlayerLinkTrait, SeamlessWallet;

    private function encryptDraw($room)
    {
        // Revisa qué números ya tienen símbolo en esta sala
        $existingNums = BingoDrawsCode::where('room', $room)->pluck('num')->all();

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
                    $exists = BingoDrawsCode::where('room', $room)->where('sym', $code)->exists();
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
                BingoDrawsCode::insert($newSymbols->toArray());
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
        $hasDraws = BingoDraws::where('room', $room)->where('time', '>', 0)->exists();

        // Si no hay draws generados, los creamos para cada ronda
        if (!$hasDraws) {

            $this->encryptDraw($room);

            $symbols = BingoDrawsCode::where('room', $room)->pluck('sym', 'num');
            $bingoRoundDraft = BingoRoundsDrafts::where('bingo_room_id', $room)->first();

            $allDraws = [];
            $allRounds = [];

            $lastDrawTime = $dataTime;

            for ($round = 1; $round <= $rounds; $round++) {

                $currentTimestamp = now();

                // Preparar info de las rondas
                $playType = $bingoRoundDraft->play_types[$round - 1];
                $prizeType = $bingoRoundDraft->prize_types[$round - 1];
                $prizeValue = $bingoRoundDraft->prize_values[$round - 1];

                $allRounds[] = [
                    'room' => $room,
                    'round' => $round,
                    'start_time' => $lastDrawTime,
                    'play_type' => $playType,
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
                BingoDraws::insert($allDraws);
                BingoRounds::insert($allRounds);
                $bingoRoundDraft->update(['status' => 'COMPLETED']);
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
                ? BingoDraws::where('room', $room)->max('round')
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
            $drawChips = BingoDraws::where('room', $room)
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
            $drawIds = BingoDraws::where('room', $room)
                ->where('round', $roundToUse)
                ->pluck('id')
                ->toArray();

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

        if (!BingoDraws::getWinningChip($room) || $roomUser) {
            return $this->chipWin($room);
        }

        return false;
    }

    public function chipWin($roomId)
    {
        DB::beginTransaction();

        try {

            // Verificar si hay jugadores en la sala
            if (!BingoRoomUser::where('room', $roomId)->exists()) {
                BingoRoom::where('id', $roomId)->update(['status' => 1]);
                BingoDraws::where('room', $roomId)->update(['time' => 0]);
                DB::commit();

                return ['Error' => 'No hay jugadores en la sala'];
            }

            $roundsCount = (int) BingoRoomConfig::getValueByRoom($roomId, 'rounds');
            $roundPlayTypes = BingoRounds::where('room', $roomId)->pluck('play_type', 'round');

            $firstChipDraw = null;

            for ($round = 1; $round <= $roundsCount; $round++) {

                $playType = $roundPlayTypes[$round] ?? null;

                if (!$playType) continue;

                // Obtener columnas del patrón de juego
                $columns = ($playType == 1)
                    ? array_diff(BingoCtns::ALL_BOXES, ['N3'])
                    : BingoPatterns::getColumnsBoxesById($playType);

                // Asegurar 'id' como primera columna y evitar duplicados
                $columns = array_unique(array_merge(['id'], $columns));

                // Índice de fichas jugadas -> cartones
                $chipIndex = [];

                BingoCtns::where('room', $roomId)
                    ->select($columns)
                    ->chunk(100, function ($cartons) use (&$chipIndex) {
                        foreach ($cartons as $carton) {
                            $chips = collect($carton)->except('id');
                            foreach ($chips as $chip) {
                                if (!is_null($chip)) {
                                    $chipIndex[$chip][] = $carton->id;
                                }
                            }
                        }
                    });

                // Obtener fichas jugadas en orden descendente
                $drawnChips = BingoDraws::where('room', $roomId)
                    ->where('round', $round)
                    ->orderByDesc('id')
                    ->pluck('chip')
                    ->toArray();

                $matchedCartons = [];
                $winningChips = [];

                foreach ($drawnChips as $chip) {
                    if (isset($chipIndex[$chip])) {
                        foreach ($chipIndex[$chip] as $ctnId) {
                            if (!isset($matchedCartons[$ctnId])) {
                                $matchedCartons[$ctnId] = true;
                                $winningChips[$chip] = true;
                            }
                        }
                    }
                }

                // Encontrar la última ficha ganadora
                $winningChip = null;
                foreach (array_reverse($drawnChips) as $chip) {
                    if (isset($winningChips[$chip])) {
                        $winningChip = $chip;
                        break;
                    }
                }

                if (!$winningChip) continue;

                $chipDraw = BingoDraws::where('room', $roomId)
                    ->where('round', $round)
                    ->where('chip', $winningChip)
                    ->select('id', 'time', 'chip', 'status')
                    ->first();

                if ($chipDraw) {
                    $chipDraw->update(['status' => 3]);

                    BingoRounds::where('room', $roomId)
                        ->where('round', $round)
                        ->update([
                            'start_time' => $chipDraw->time,
                            'last_chip' => $chipDraw->chip,
                        ]);

                    if ($round === 1) {
                        $firstChipDraw = $chipDraw;
                    }
                }
            }

            $this->insertGameExchangeRate($roomId, 'BINGO');

            DB::commit();
            return $firstChipDraw;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error al procesar chipWin para sala {$roomId}: {$e->getMessage()}");
            return null;
        }
    }

    public function getInfoWinners($data, $roomUser)
    {
        $roomID = BingoRoom::getValueByEncrypted($data['room'], 'id');

        $this->handleWinners($roomID);

        $winners = $this->getWinners('ALL', $roomID, $data['coin'], $roomUser);

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
    private function getRoomDetails($roomID)
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
        )->where('status', 2)->where('id', $roomID)->first();
    }

    // Función para calcular la comisión del vendedor
    private function calculateSellerCommission($seller, $saleAmount, $roomID)
    {
        if (!$seller) return 0;

        $commnSeller = SellerRoomLimits::getGameRoomValueBySeller('BINGO', $seller, $roomID, 'commn') ?? Seller::getValueById($seller, 'commn');
        // Registar la comision del vendedor para la sala de jeugo
        SellerRoomLimits::firstOrCreate(['game' => 1, 'room' => $roomID, 'seller' => $seller], ['commn' => $commnSeller]);

        $commnSeller = ($saleAmount * $commnSeller) / 100;

        return $commnSeller ? round($commnSeller, 2) : 0;
    }

    // Función para crear el lote de cartones
    private function createCtnsLot($arrCtn, $saleAmount)
    {
        $seller = $arrCtn['seller'] ?? null;

        if ($seller) {
            $sellerCommn = Seller::getValueById($seller, 'commn') / 100;
            $sellerCommn = round($saleAmount * $sellerCommn, 2);
        }

        $creatorUser = $arrCtn['creator']['userId'];
        $roomID = $arrCtn['room'];
        $coin = $arrCtn['coin'];

        $currentTime = now();

        try {

            $BingoCtnsLotID = BingoCtnsLot::insertGetId([
                'creator_user' => $creatorUser,
                'seller' => $seller,
                'player_user' => $arrCtn['playerUser'] ?? null,
                'room_user' => $arrCtn['roomUser'],
                'room' => $roomID,
                'ctns' => $arrCtn['numOfCtns'],
                'coin' => $coin,
                'ctn_price' => $arrCtn['ctnPrice'],
                'exchange_rate' => $arrCtn['exchangeRate'],
                'sale_amount' => $saleAmount,
                'seller_commn' => $sellerCommn ?? null,
                'status' => 0,
                'created_at' => $currentTime,
                'updated_at' => $currentTime,
            ]);

            // Actualizar monto de venta total por monedas
            BingoCreatorSales::updateOrCreateSale($roomID, $creatorUser, $coin, $saleAmount, $sellerCommn);

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
                $creatorID = $arrCtn['creator']['id'];
                $country = CustomerUser::getValueByUserId($creatorID, $arrCtn['playerUser'], 'country');
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
            'creator_user' => $arrCtn['creator']['userId'],
            'seller' => $arrCtn['seller'] ?? null,
            'player_user' => $arrCtn['playerUser'] ?? null,
            'room_user' => $arrCtn['roomUser'],
            'coin' => $arrCtn['coin'],
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
                'creator_user' => $arrCtn['creator']['userId'],
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

        $roomID = $arrCtn['room'];

        $room = $this->getRoomDetails($roomID);

        if (!$room) return ['Error' => 'Sala no disponible'];

        try {
            // Inicialización de variables
            $creator = $arrCtn['creator'];
            $seller = $arrCtn['seller'] ?? null;
            $playerUser = $arrCtn['playerUser'] ?? null;
            $coin = $arrCtn['coin'];
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

            if ($gameMode != 1 && ($creator['type'] === 'commerce' || $seller)) {
                // Calcular monto total de venta y comisiones si es un vendedor
                $sellerSaleCommn = $this->calculateSellerCommission($seller, $saleAmount, $roomID);
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
                $columnBalance = $coin == 1 ? 'usdt' : 'balance';

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
            if (!CoinGameRoom::existsCoinGameRoom($coin, $roomID, 'BINGO', $gameMode)) {
                CoinGameRoom::createNewCoinInRoom($game, $roomID, $coin);
            }

            // Si la sala es de un cliente
            if ($creator['type'] === 'customer') {
                // Registrar transacción (se consulta nuvamente el saldo del juagdor)
                $roomID = BingoRoom::getValueById($roomID, 'code_uuid') ?? $this->getCodeUUID('BingoRoom');

                $transactionBet = $this->handleTransactionByBET(
                    $game,
                    $creator['id'],
                    $roomID,
                    $playerUser,
                    $coin,
                    $saleAmount
                );

                if (isset($transactionBet['Error'])) {
                    return ['Error' => 'Ha ocurrido un error'];
                }
            }

            // Generar un nuevo sorteo si aplica
            if (isset($arrCtn['ctnWin'])) {
                $this->handleDraw($roomID, $roomUser);
            }

            return $roomUser;
        } catch (Exception $e) {
            Log::error('Error al crear los cartones cartonStore(): ' . $e->getMessage());
            return ['Error' => 'Error al crear los cartones'];
        }
    }

    public function convertPrizeValue($roomID, $coin, $value)
    {
        $room = BingoRoom::getById($roomID, ['creator_user', 'prize_pool']);

        $creatorUser = $room->creator_user;
        $userCreator = User::getCreatorUser($creatorUser, false, true);

        $creator = ['type' => $userCreator['type'], 'id' => $userCreator['id'], 'userId' => $creatorUser];

        $data = ['coin' => $coin, 'value' => $value, 'creator' => $creator];

        return $this->convertValueByGame('BINGO', $roomID, $data);
    }

    private function registerPrizeCtn($roomID, $round, $cartons, $pattern, $USDValue)
    {
        if ($pattern !== 'SRL') $pattern = $pattern == 1 ? 'FLHE' : 'SONG';

        $recordsToInsert = $playersToUpdate = [];

        foreach ($cartons as $ctn) {

            if (!BingoPrizeCtns::existsPrizeCtn($ctn->id, $round)) {

                // Definir $roomUser
                $roomUser = $ctn->room_user;

                // Verificar y obtener el tipo de cambio
                $exchangeRate = GameExchangeRate::getGameExchangeRateValue('BINGO', $roomID, $ctn->coin) ?? 1;

                // Multiplicamos por el exchangeRate
                $convertedAmount = $USDValue * $exchangeRate;

                $prizeAmount = round($convertedAmount, 2);

                $timestamp = now();

                $record = [
                    'active' => 'N',
                    'room' => $roomID,
                    'round' => $round,
                    'creator_user' => $ctn->creator_user,
                    'seller' => $ctn->seller,
                    'room_user' => $roomUser,
                    'ctn' => $ctn->id,
                    'lot' => $ctn->lot,
                    'prize_type' => $pattern,
                    'usd_amount' => $USDValue,
                    'coin' => $ctn->coin,
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
                BingoRoomUser::where('room', $roomID)->where('winner', '0')
                    ->whereIn('id', $playersToUpdate)->update(['winner' => '1']);

                //Insertar los registros de una sola vez
                BingoPrizeCtns::insert($recordsToInsert);

                $roomType = BingoRoom::getValueById($roomID, 'room_type');

                if ($roomType === 'PB') {
                    // Manejar los pagos pendientes de los BingoRoomCreators
                    $this->handlePublicRoomPendingPayments($roomID);
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

    private function handlePublicRoomPendingPayments($roomID)
    {
        // Manejador de pagos pendientes de los BingoRoomCreators
        $bingoCreators = BingoCreator::where('room', $roomID)->count();

        if ($bingoCreators === 1) return;

        $recordsToInsert = [];

        $bingoCreatorSales = BingoCreatorSales::where('room', $roomID)->get();

        try {
        } catch (Exception $e) {
            Log::error('Error al insertar los registros de pagos pendientes para la sala {$roomID}:' . $e->getMessage());
        }
    }

    private function getCoinByPlayer($creator, $playerID)
    {
        switch ($creator) {
            case 'customer':
                $coin = CustomerUser::getValueById($playerID, 'coin');
                break;
            case 'commerce':
                $coin = PlayerLink::getValueByRoomUser('BINGO', $playerID, 'coin');
                break;
            default:
                break;
        }

        return $coin;
    }

    public function getWinnersData($prizeType, $roomID, $roomUser = null)
    {
        $room = BingoRoom::select('creator_user')
            ->where('id', $roomID)
            ->where('status', '>=', 3)
            ->first();

        if (!$room) {
            return ['Error' => 'Sala no disponible'];
        }

        $round = BingoRounds::getValueByRoundInProgress($roomID, 'round');

        $query = BingoPrizeCtns::where('room', $roomID)->where('round', '<=', $round);

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
            'room' => $roomID,
            'winPlayer' => $winPlayer,
        ];
    }

    public function getArrayWinnersData($data)
    {
        $exchangeRate = GameExchangeRate::getGameExchangeRateValue('BINGO', $data['room'], $data['coin']) ?? 1;
        $bingoRounds = BingoRounds::where('room', $data['room'])
            ->get(['round', 'play_type', 'prize_value', 'start_time', 'last_chip'])
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
                $prizeType = BingoPatterns::getValueById($bingoRounds[$round]->play_type, 'name');
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

    private function findWinners($roomID, $roundID, array $chipsDraw)
    {
        $playType = BingoRounds::getValueById($roundID, 'play_type');

        // Columnas del patrón
        $patternColumns = ($playType == 1)
            ? array_diff(BingoCtns::ALL_BOXES, ['N3'])
            : BingoPatterns::getColumnsBoxesById($playType);

        $columns = array_unique(array_merge(['id', 'room_user'], $patternColumns));
        $winners = collect();

        BingoCtns::where('room', $roomID)
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

    public function handleWinners($roomID)
    {
        $room = BingoRoom::with('Config:room,serials_pct')
            ->select('id', 'creator_user', 'prize_pool', 'serial_prize', 'players')
            ->findOrFail($roomID);

        // Validar si ya hay ganadores 
        if (BingoRoomUser::getWinnersCountByRoom($roomID)) return false;

        $prizePool = $room->prize_pool;
        $serialsPct = $room->Config->serials_pct;
        $applySerialPrize = false;
        $rounds = BingoRounds::where('room', $roomID)->get();
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

            $lastChip = BingoDraws::where('room', $roomID)
                ->where('round', $roundNumber)
                ->where('status', 3)
                ->orderByDesc('id')
                ->first();

            if (!$lastChip) {
                Log::warning("No hay ficha final para la ronda $roundNumber en la sala $roomID");
                continue;
            }

            $chipsDraw = BingoDraws::where('room', $roomID)
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

            $winners = $this->findWinners($roomID, $roundItem->id, $chipsDraw);

            if ($winners->isEmpty()) {
                Log::info("Sin ganadores en la ronda $roundNumber de la sala $roomID");
                continue;
            }

            $ctnsJackpot = BingoCtns::where('room', $roomID)
                ->whereIn('id', $winners->values())
                ->get(['id', 'creator_user', 'seller', 'room_user', 'coin', 'lot']);

            DB::beginTransaction();

            try {

                $jackpotPrizePerCtn = round($roundPrizeValue / max(1, $ctnsJackpot->count()), 3);

                $register = $this->registerPrizeCtn(
                    $roomID,
                    $roundNumber,
                    $ctnsJackpot,
                    $roundItem->play_type,
                    $jackpotPrizePerCtn
                );

                if (!$register) {
                    throw new Exception('Fallo al registrar el carton premiado.');
                }

                if ($applySerialPrize) {
                    $serialPrize = round($serialPrizeValue, 3);
                    $serialResult = $this->handleSerialWinners(
                        $roomID,
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
                Log::error("Error en handleWinners (Room $roomID - Round $roundNumber): " . $e->getMessage());
                return response()->json(['error' => 'Error al procesar ganadores.'], 500);
            }
        }

        // Validar transacciones si es customer
        $userType = UserType::getValueById(User::getValueById($room->creator_user, 'user_type'), 'prefix');

        if ($userType === 'customer') {
            $customerId = Customer::getValueByUserId($room->creator_user, 'id');
            app(HandleTransactions::class)->validateTransactions($customerId);
        }

        return true;
    }

    public function getWinners(string $prizeType, int $roomID, int $coin, ?int $roomUser = null)
    {
        $data = $this->getWinnersData($prizeType, $roomID, $roomUser);

        // Si existe algun error
        if (isset($data['Error'])) return $data['Error'];

        // Agregar la moneda al resultado
        $data['coin'] = $coin;

        return $this->getArrayWinnersData($data);
    }

    public function handleSerialWinners($roomID, $round, $serialPrize, $roomUserIds)
    {
        try {
            // Obtener Seriales Ganadores
            $getWinners = BingoPrizeCtns::getWinnersCountByPrizeType($roomID, 'SERIAL');

            if ($getWinners) return false;

            // Obtener la última ficha ganadora
            $lastChip = BingoDraws::where('room', $roomID)->where('status', 3)
                ->where('round', $round)->value('id');

            // Fichas que han salido antes del jackpot
            $chipsDraw = array_flip(
                BingoDraws::where('room', $roomID)
                    ->where('id', '<', $lastChip)
                    ->where('round', $round)
                    ->pluck('chip')->toArray()
            );

            $bingoConfig = BingoRoomConfig::getByRoom($roomID, ['serials_pct', 'num_serials']);

            $serialsPct = $bingoConfig->serials_pct / 100;
            $countSerials = $bingoConfig->num_serials;

            $countCtns = 0;
            $uniquePlayers = [];
            $serialCtns = [];

            $batchSize = 100;
            $page = 1;

            do {
                // Tomar 100 cartones aleatorios que no pertenezcan a los ganadores previos
                $batch = BingoCtns::where('room', $roomID)
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
                $gameID = Games::getValueByName('BINGO');

                // Obtener las monedas activas en la sala
                $coins = CoinGameRoom::getCoinsByRoom($gameID, $roomID);

                // Preparar los datos para insertar
                $dataJackpots = $coins->map(function ($coin) use ($roomID, $gameID, $serialsPct, $timestamp) {

                    $prizePoolAmount = BingoCreatorSales::getPrizePoolAmountByRoomAndCoin($roomID, $coin);
                    $amount = round($prizePoolAmount * $serialsPct, 3);

                    return [
                        'game' => $gameID,
                        'room' => $roomID,
                        'coin' => $coin,
                        'amount' => $amount,
                        'created_at' => $timestamp,
                        'updated_at' => $timestamp,
                    ];
                })->toArray();

                // Insertar si hay datos
                if (!empty($dataJackpots)) {
                    GameRoomJackpot::insert($dataJackpots);
                }

                Log::info("No hubo ganadores de seriales en la Sala de Bingo {$roomID}. El porcentaje se registró al jackpot acumulado.");
                return true;
            }

            // Obtener la información final de los cartones ganadores
            $ctnsSerials = BingoCtns::whereIn('id', $serialCtns)
                ->where('room', $roomID)
                ->get(['id', 'seller', 'creator_user', 'room_user', 'coin', 'lot']);

            // Repartir el premio entre los ganadores
            $winnerJackpotPrize = $serialPrize / count($serialCtns);

            $this->registerPrizeCtn($roomID, $round, $ctnsSerials, 'SRL', $winnerJackpotPrize);

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

        // Verificar si es un carton ganador por serial premiado
        $serialAward = BingoPrizeCtns::where('ctn', $ctnId)->where('prize_type', 'SRL')->exists();

        $creatorUser = $ctn->creator_user;
        $creatorUserType = User::getUserTypePrefix($creatorUser);

        if ($creatorUserType !== 'customer') {
            if ($ctn->player_user) {
                $fullName = User::getValueById($ctn->player_user, 'username');
            } else {
                $playerLink = PlayerLink::getByRoomUser('BINGO', $ctn->room_user, ['name', 'lastname']);
                $fullName = "$playerLink->name $playerLink->lastname";
            }
        } else {
            $fullName = Customer::getValueByUserId($creatorUser, 'name');
        }

        $prizeCtn = BingoPrizeCtns::getById($ctnId, ['room', 'round', 'prize_type']);

        if ($prizeCtn->prize_type === 'SONG') {
            $bingoPattern = BingoRounds::getValueByRoom($prizeCtn->room, $prizeCtn->round, 'play_type');
            $checkBoxes = BingoPatterns::getColumnsBoxesById($bingoPattern);
        } else {
            $checkBoxes = $boxes;
        }

        // Obtener los números del cartón en un solo array
        $carton = $ctn->only($boxes);

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
            'creator_user' => $lotCtn->creator_user,
            'seller' => $lotCtn->seller,
            'player_link' => PlayerLink::getValueByRoomUser('BINGO', $lotCtn->room_user, 'id'),
            'lot' => $lotCtn->id,
            'coin' => $lotCtn->coin,
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
                'creator_user' => $prizeCtn->creator_user,
                'seller' => $prizeCtn->seller,
                'player_link' => PlayerLink::getValueByRoomUser('BINGO', $prizeCtn->room_user, 'id'),
                'user' => $user,
                'prize_type' => $prizeCtn->prize_type,
                'lot' => $prizeCtn->lot,
                'ctn' => $prizeCtn->ctn,
                'coin' => $prizeCtn->coin,
                'amount' => $prizeCtn->amount,
                'payment_proof' => $filePayment,
            ]);
        } catch (Exception $e) {
            Log::error("'Error al guardar el registro del pago del carton ganador: " . $e->getMessage());
            return false;
        }
    }

    public function verifyExistenceOfSales($user, $coin = '', $dateFilter = '', $room = '')
    {
        $column = $user['type'] !== 'seller' ? 'creator_user' : 'seller';
        $columnValue = $column !== 'seller' ? $user['userId'] : $user['id'];

        $query = BingoCtnsLot::where($column, $columnValue);

        // Filtro por moneda
        if ($coin) {
            try {
                $coin = decrypt($coin);
            } catch (Exception) {
                // Si falla el descifrado, asume que viene en texto plano
            }
            $query->where('coin', $coin);
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
                return ['return' => true, 'coin' => encrypt($coin)];
            }
        }

        // Si no existe venta o no hay filtros de moneda ni sala
        if (!$existsSale || (!$coin && !$room)) {

            $firstSale = BingoCtnsLot::select('coin')->where($column, $columnValue)->first();

            if ($firstSale) {
                return [
                    'return' => true,
                    'coin'   => encrypt($firstSale->coin)
                ];
            }

            return encrypt(1);
        }

        // Si existe venta y cumple con todos los filtros
        return $coin ?? null;
    }
}
