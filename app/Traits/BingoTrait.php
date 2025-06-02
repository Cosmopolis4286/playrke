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
use App\Models\BingoPrizeCtns;
use App\Models\BingoPrizeCtnsPayment;
use App\Models\BingoRoomConfig;
use App\Models\BingoRounds;
use App\Models\BingoRoundsDrafts;
use App\Models\BingoRoomUser;
use App\Models\CoinGameRoom;
use App\Models\CommerceExchangeRate;
use App\Models\Country;
use App\Models\Customer;
use App\Models\CustomerExchangeRate;
use App\Models\CustomerUser;
use App\Models\GameExchangeRate;
use App\Models\GamePrizes;
use App\Models\Games;
use App\Models\PlayerLink;
use App\Models\Seller;
use App\Models\User;
use App\Models\UserType;
use App\Models\Wallet;
use App\Services\Customer\HandleTransactions;
use App\Services\Game\Bingo\HandleBingoRooms;
use Carbon\Carbon;
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

        // Insertar configuracion de las rondas de la sala
        $this->handleRoundsConfig($room);

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

            $allDraws = [];

            $lastDrawTime = $dataTime;

            for ($round = 1; $round <= $rounds; $round++) {

                $availableChips = range(1, 75);
                shuffle($availableChips);

                $currentTimestamp = now();

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
                DB::commit();

                return $this->chipWin($room);
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

    public function chipWin($room)
    {
        DB::beginTransaction();

        try {
            // Si no hay jugadores, reestablecer por defecto
            if (!BingoRoomUser::where('room', $room)->exists()) {
                BingoRoom::where('id', $room)->update(['status' => 1]);
                BingoDraws::where('room', $room)->update(['time' => 0]);

                DB::commit();
                return ['Error' => 'No hay jugadores en la sala'];
            }

            $rounds = (int) BingoRoomConfig::getValueByRoom($room, 'rounds');

            for ($round = 1; $round <= $rounds; $round++) {

                // Crear un índice invertido para las fichas en los cartones
                $chipIndex = [];

                // Obtener los valores de los cartones
                BingoCtns::where('room', $room)
                    ->select([
                        'id',
                        'b1',
                        'b2',
                        'b3',
                        'b4',
                        'b5',
                        'i1',
                        'i2',
                        'i3',
                        'i4',
                        'i5',
                        'n1',
                        'n2',
                        'n3',
                        'n4',
                        'g1',
                        'g2',
                        'g3',
                        'g4',
                        'g5',
                        'o1',
                        'o2',
                        'o3',
                        'o4',
                        'o5'
                    ])
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

                // Obtener las fichas jugadas en este round
                $drawDesc = BingoDraws::select('chip')
                    ->where('room', $room)->where('round', $round)
                    ->orderByDesc('id')->pluck('chip')->toArray();

                $winningChip = null;
                $chipsVanAssoc = [];
                $arrCtns = [];

                foreach ($drawDesc as $chip) {
                    if (isset($chipIndex[$chip])) {
                        foreach ($chipIndex[$chip] as $cartonId) {
                            if (isset($arrCtns[$cartonId])) {
                                continue;
                            }
                            $arrCtns[$cartonId] = true;
                            $chipsVanAssoc[$chip] = true;
                        }
                    }
                }

                // Encontrar la última ficha ganadora
                for ($i = count($drawDesc) - 1; $i >= 0; $i--) {
                    $chip = $drawDesc[$i];
                    if (isset($chipsVanAssoc[$chip])) {
                        $winningChip = $chip;
                        break;
                    }
                }

                if ($winningChip) {
                    BingoDraws::where('chip', $winningChip)
                        ->where('room', $room)->where('round', $round)
                        ->update(['status' => 3]);

                    if ($round == $rounds) {
                        // Puedes opcionalmente retornar o guardar el resultado
                        $chipWin = BingoDraws::select('id', 'chip', 'time')
                            ->where('chip', $winningChip)->where('round', $round)
                            ->where('room', $room)->first();
                    }
                }
            }

            $this->insertGameExchangeRate($room, 'BINGO');

            DB::commit();

            return $chipWin;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error("Error al registrar ficha ganadora en la Sala Bingo (ID: {$room}): {$e->getMessage()}");
            return null;
        }
    }

    public function getInfoWinners($room, $roomUser, $coin = '')
    {
        date_default_timezone_set(config('app.timezone'));

        $roomID = BingoRoom::getValueByEncrypted($room, 'id');

        //Obtener Conteo de Ganadores
        $countWinnersJackpot = BingoPrizeCtns::getWinnersCountByPrizeType($roomID, 1);

        if (!$countWinnersJackpot) $this->handleWinners($roomID);

        $fullHouseWinners = $this->getWinnersFullHouse($roomID, $roomUser, $coin);

        if (BingoRoomConfig::getValueByRoom($roomID, 'play_types')) {
            $bingoPlaysWinners = [];
        }

        $serialsWinners = (BingoPrizeCtns::getWinnersCountByPrizeType($roomID, 2)) ?
            $this->getWinnersSerials($roomID, $roomUser, $coin) : [];

        $serialPrizePct = BingoRoomConfig::getValueByRoom($roomID, 'serials_pct');

        $prizesValues = !empty($serialsWinners) ? [100 - $serialPrizePct, $serialPrizePct] : [100];

        return [
            'fullHouseWinners' => $fullHouseWinners,
            'serialsWinners' => $serialsWinners,
            'bingoPlaysWinners' => $bingoPlaysWinners ?? [],
            'prizesValues' => $prizesValues
        ];
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
            'all_sale',
            'room_commn',
            'seller_commn',
            'prize_pool',
            'full_house_prize',
            'ctns'
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
    private function createCtnsLot($arrCtn, $exchangeRate, $saleAmount)
    {
        $seller = $arrCtn['seller'] ?? null;

        $finalSaleAmount = $saleAmount;

        if ($seller) {
            $sellerCommn = Seller::getValueById($seller, 'commn') / 100;
            $finalSaleAmount = round($saleAmount * (1 - $sellerCommn), 2);
        }

        $creatorUser = $arrCtn['creator']['userId'];
        $roomID = $arrCtn['room'];
        $coin = $arrCtn['coin'];

        $roomCommn = BingoRoomConfig::getValueByRoom($roomID, 'room_commn');

        $currentTime = now();

        try {

            $BingoCtnsLotID = BingoCtnsLot::insertGetId([
                'creator_user' => $creatorUser,
                'seller' => $seller,
                'player_user' => $arrCtn['playerUser'] ?? null,
                'room_user' => $arrCtn['roomUser'],
                'room' => $roomID,
                'ctn_price' => $arrCtn['ctnPrice'],
                'exchange_rate' => $exchangeRate,
                'ctns' => $arrCtn['numOfCtns'],
                'coin' => $coin,
                'sale_amount' => $saleAmount,
                'final_sale_amount' => $finalSaleAmount,
                'status' => 0,
                'created_at' => $currentTime,
                'updated_at' => $currentTime,
            ]);

            // Actualizar monto de venta total por monedas
            BingoCreatorSales::updateOrCreateSale($roomID, $creatorUser, $roomCommn, $coin, $saleAmount, $finalSaleAmount);

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
            'n3' => $nwCtn[12],
            'n4' => $nwCtn[13],
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
                'room'         => $arrCtn['room'],
                'creator_user' => $arrCtn['creator']['userId'],
                'player_user'  => $arrCtn['playerUser'] ?? null,
                'ctns'         => $arrCtn['numOfCtns'],
                'automarker'   => 1,
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
            if (!empty($arrCtn['exchangeRate'])) {
                $exchangeRate = $arrCtn['exchangeRate'];
                $saleAmount = round($saleAmount / $exchangeRate, 3);
                $sellerSaleCommn = round($sellerSaleCommn / $exchangeRate, 3);
            }

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

            // Evaluar tasa de cambio
            if ($creator['type'] === 'customer') {
                $exchangeRate = CustomerExchangeRate::getCustomerCoinValue($arrCtn['customer'], $coin) ?? 1;
            } else {
                $exchangeRate = CommerceExchangeRate::getCommerceCoinValue($creator['id'], $coin) ?? 1;
            }

            // Crear lote de cartones
            $lotID = $this->createCtnsLot($arrCtn, $exchangeRate, $saleAmount);

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

            // Actualizar acumulado y comisiones de la sala
            $updateRoomMetrics = $this->updateRoomMetrics($room, $arrCtn, $saleAmount, $sellerSaleCommn ?? 0);

            // Verificar si se actualizó los valores de la sala
            if (!$updateRoomMetrics) {
                return ['Error' => 'Error al actualizar los valores de la sala'];
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

    public function convertPrizeValue($roomID, $coin, $value, $float = false)
    {
        $room = BingoRoom::getById($roomID, ['creator_user', 'prize_pool']);

        $creatorUser = $room->creator_user;
        $userCreator = User::getCreatorUser($creatorUser, false, true);

        $creator = ['type' => $userCreator['type'], 'id' => $userCreator['id'], 'userId' => $creatorUser];

        $data = ['coin' => $coin, 'value' => $value, 'creator' => $creator];

        return $this->convertValueByGame('BINGO', $roomID, $data);
    }

    private function registerPrizeCtn($roomID, $round, $cartons, $prizeType, $USDValue)
    {
        $roomType = BingoRoom::getValueById($roomID, 'room_type');

        $prizeType = GamePrizes::getValueByDescription('BINGO', $prizeType);

        $recordsToInsert = $playersToUpdate = [];

        foreach ($cartons as $ctn) {

            if (!BingoPrizeCtns::existsPrizeCtn($ctn->id)) {

                // Definir $roomUser
                $roomUser = $ctn->room_user;

                // Verificar y obtener el tipo de cambio
                $exchangeRate = GameExchangeRate::getGameExchangeRateValue('BINGO', $roomID, $ctn->coin) ?? 1;

                // Multiplicamos por el exchangeRate
                $convertedAmount = $USDValue * $exchangeRate;

                // Usamos floor() para truncar y luego formateamos a 2 decimales
                $prizeAmount = floor($convertedAmount * 100) / 100;

                $timestamp = now();

                $record = [
                    'active' => 'N',
                    'room' => $roomID,
                    'round' => $round,
                    'creator_user' => $ctn->creator_user,
                    'seller' => $ctn->seller,
                    'player_user' => $ctn->player_user,
                    'room_user' => $roomUser,
                    'ctn' => $ctn->id,
                    'lot' => $ctn->lot,
                    'prize_type' => $prizeType,
                    'usd_amount' => $USDValue,
                    'coin' => $ctn->coin,
                    'amount' => $prizeAmount,
                    'status' => null,
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

            if (!$playersToUpdate && !$recordsToInsert) return;

            if ($playersToUpdate) {
                // Actualizar el estado del jugador 
                BingoRoomUser::where('room', $roomID)->whereIn('id', $playersToUpdate)
                    ->where('winner', 0)->update(['winner' => 1]);
            }

            if ($recordsToInsert) {
                //Insertar los registros de una sola vez
                BingoPrizeCtns::insert($recordsToInsert);
            }

            if ($roomType === 'PB') {
                // Manejar los pagos pendientes de los BingoRoomCreators
                $this->handlePublicRoomPendingPayments($roomID);
            }

            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error al registrar carton ganador ===> $prizeType [' . $prizeType . '] : ' . $e->getMessage());
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

        //dd($bingoCreatorSales);

        foreach ($bingoCreatorSales as $item) {

            // solo sumar los pertenecientes de esa moneda y insertar en el array 
            // despues hacer los mismos
            $finalSaleAmount = $bingoCreatorSales->sum('final_sale_amount');

            $recordsToInsert[] = [
                'coin' => $item->coin,
                'amount' => $finalSaleAmount
            ];
        }

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

    public function getWinnersData($roomID, $prizeType, $roomUser = '')
    {
        $room = BingoRoom::select('creator_user')->where('id', $roomID)
            ->where('status', '>=', 3)->first();

        if (!$room) return ['Error' => 'Sala no disponible'];

        // Definir $prizeType
        $prizeType = $prizeType === 'JACKPOT' ? 1 : 2;

        // Obtener todos los ganadores por tipo de premio 
        $winners = BingoRoomUser::with(['PrizeCtns:creator_user,room_user,prize_type,ctn,amount'])
            ->whereHas('PrizeCtns', function ($query) use ($prizeType) {
                $query->where('prize_type', $prizeType);
            })->where('room', $roomID)->where('winner', '>', 0)->get();

        if ($roomUser) {
            // Obtener el creador de la sala
            $creator['type'] = User::getValueById($room->creator_user, 'user_type');

            $userType = UserType::getValueById($creator['type'], 'prefix');

            // Obtener la moneda del jugador
            $coin = $this->getCoinByPlayer($userType, $roomUser);

            // Verificar si el usuario del enlace es un ganador
            $winPlayer = BingoRoomUser::where('room', $roomID)->where('winner', 1)->exists();
        }

        // Obtener Premio a repartir en seriales 
        $serialPrize = BingoRoom::where('id', $roomID)->value('serial_prize');

        return [
            'serialPrize' => $serialPrize,
            'winners' => $winners,
            'winPlayer' => $winPlayer ?? false,
            'coin' => $coin ?? 1
        ];
    }

    public function getArrayWinnersData($exchangeRate, $winPlayer, $winners)
    {
        $infoPlayer = ['size' => 0, 'name' => [], 'gain' => [], 'ctn' => []];

        foreach ($winners as $player) {

            $ctnId = optional($player->PrizeCtns[0])->ctn;

            if (!$ctnId) {
                continue; // Evitar errores si no hay ctn
            }

            $prizeCtn = BingoPrizeCtns::getById($ctnId, ['usd_amount', 'amount']);

            // Calcular el monto dependiendo del tipo de jugador
            if (!$winPlayer) {
                $amount = floor($prizeCtn->usd_amount * $exchangeRate * 100) / 100;
            } else {
                $amount = $prizeCtn->amount;
            }

            $infoPlayer['name'][] = BingoCtns::getValueById($ctnId, 'serial');
            $infoPlayer['gain'][] = number_format($amount, 2, '.', ',');
            $infoPlayer['ctn'][] = encrypt($ctnId);
            $infoPlayer['size']++;
        }

        return $infoPlayer;
    }

    public function handleTypesOfPlays($roomID) {}

    public function handleWinners($roomID)
    {
        // Obtener la información de la sala
        $room = BingoRoom::with('Config:room,serials_pct')
            ->select('id', 'creator_user', 'prize_pool', 'full_house_prize', 'serial_prize')
            ->where('id', $roomID)->firstOrFail();

        // Verificar si ya hay ganadores del premio mayor
        $prizeCtns = BingoPrizeCtns::getWinnersCountByPrizeType($roomID, 1);
        $winners = BingoRoomUser::getWinnersCountByRoom($roomID);

        if ($prizeCtns && $winners) return false; // Ya existen cartones premiados (full_house_prize)

        $round = BingoRounds::getRoundInProgress($roomID);

        // Obtener el último chip sorteado
        $lastChip = BingoDraws::select('id', 'chip')->where('status', 3)
            ->where('round', $round)->where('room', $roomID)->first();

        // Obtener todos los chips sorteados hasta el último
        $chipsDraw = BingoDraws::select('chip')->where('room', $roomID)
            ->where('round', $round)->where('id', '<=', $lastChip->id)
            ->distinct()->pluck('chip')->toArray();

        if (count($chipsDraw) < 24) {
            Log::warning('Aún no hay suficientes fichas sorteadas para validar cartón lleno');
            $this->chipWin($room);
        }

        // Última ficha sorteada
        $lastChipNum = $lastChip->chip;

        // Columnas de los cartones
        $cartonColumns = [
            'b1',
            'b2',
            'b3',
            'b4',
            'b5',
            'i1',
            'i2',
            'i3',
            'i4',
            'i5',
            'n1',
            'n2',
            'n3',
            'n4',
            'g1',
            'g2',
            'g3',
            'g4',
            'g5',
            'o1',
            'o2',
            'o3',
            'o4',
            'o5'
        ];

        // Inicializar variables de ganadores
        $winnersByUser = collect();

        // Consulta perezosa
        BingoCtns::where('room', $roomID)
            ->whereRaw(
                '(' . implode(' = ? OR ', $cartonColumns) . ' = ?)',
                array_fill(0, count($cartonColumns), $lastChipNum)
            )
            ->select(array_merge(['id', 'room_user'], $cartonColumns))
            ->lazyById(100)
            ->each(function ($ctn) use ($cartonColumns, $chipsDraw, &$winnersByUser) {
                if (!$winnersByUser->has($ctn->room_user)) {
                    $values = [];
                    foreach ($cartonColumns as $col) {
                        $values[] = $ctn->$col;
                    }

                    if (count(array_intersect($values, $chipsDraw)) === count($values)) {
                        $winnersByUser->put($ctn->room_user, $ctn->id);
                    }
                }
            });

        // IDs de cartones ganadores
        $prizeCtnIds = $winnersByUser->values()->all();
        $roomUserIds = $winnersByUser->keys()->all();

        // Obtener información de cartones ganadores
        $ctnsJackpot = BingoCtns::where('room', $roomID)
            ->whereIn('id', $prizeCtnIds)
            ->get(['id', 'creator_user', 'seller', 'room_user', 'coin', 'lot']);

        // Contar ganadores y no ganadores
        $winnersCount = count($prizeCtnIds);
        $nonWinnersCount = BingoRoomUser::getNonWinnersCountByRoom($roomID);

        // Seriales a generar
        $generateSerials = $nonWinnersCount - $winnersCount;

        DB::beginTransaction();

        try {
            // Definir valores a actualizar de la sala 
            $updateData = ['close_room' => Carbon::now()->timestamp];

            $fullHousePrize = $room->full_house_prize;

            // Generar seriales ganadores si hay jugadores no ganadores
            if ($generateSerials && $room->Config->serials_pct && !$room->seller_commn && !BingoPrizeCtns::existsPrizeCtns($roomID, 'SERIAL')) {

                $serialPrize = $room->prize_pool * $room->Config->serials_pct / 100;

                $serialWinners = $this->handleSerialWinners($roomID, $serialPrize, $roomUserIds);

                if ($serialWinners) {
                    // Calcular premios si hay premio serial
                    $fullHousePrize -= $serialPrize;

                    $updateData['full_house_prize'] = $fullHousePrize;
                    $updateData['serial_prize'] = $serialPrize;
                } else {
                    DB::rollBack();
                }
            }

            // Calcular el valor de cada premio individual
            $winnerJackpotPrize = $fullHousePrize / $winnersCount;
            // Crear registros de ganadores y actualizar el estado de los jugadores
            $registerPrizeCtn = $this->registerPrizeCtn($roomID, $round, $ctnsJackpot, 'Cartón Lleno', $winnerJackpotPrize);

            if (!$registerPrizeCtn) DB::rollBack();

            // Actualizar datos de la sala
            BingoRoom::where('id', $roomID)->update($updateData);

            DB::commit();
        } catch (Exception $e) {
            DB::rollBack();
            Log::error('Error en handleWinners: ' . $e->getMessage());
            // Devolver un mensaje de error
            return response()->json(['error' => 'Hubo un error al procesar la solicitud.'], 500);
        }

        // Validar Transacciones
        if (UserType::getValueById(User::getValueById($room->creator_user, 'user_type'), 'prefix') === 'customer') {
            $customer = Customer::getValueByUserId($room->creator_user, 'id');
            app(HandleTransactions::class)->validateTransactions($customer);
        }
    }

    public function getWinnersFullHouse($roomID, $roomUser, $coin)
    {
        $data = $this->getWinnersData($roomID, 'JACKPOT', $roomUser);

        // Si existe algun error
        if (isset($data['Error'])) return $data['Error'];

        // Si no hay ganadores en la sala
        if (!count($data['winners'])) $this->handleWinners($roomID);

        $exchangeRate = 1;
        $coin = $coin ? $coin : $data['coin'];

        if (!$data['winPlayer']) {
            $exchangeRate = GameExchangeRate::getGameExchangeRateValue('BINGO', $roomID, $coin) ?? 1;
        }

        return $this->getArrayWinnersData($exchangeRate, $data['winPlayer'], $data['winners']);
    }

    public function handleSerialWinners($roomID, $serialPrize, $roomUserIds) // Obtener Seriales Ganadores
    {
        try {

            $getWinners = BingoPrizeCtns::getWinnersCountByPrizeType($roomID, 2);

            if ($getWinners) return false;

            $round = BingoRounds::getRoundInProgress($roomID);

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

            $countSerials = BingoRoomConfig::getValueByRoom($roomID, 'num_serials');
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
                        $ctn->n3,
                        $ctn->n4,
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

                    // Validar que el cartón tenga entre 15 y 23 aciertos
                    if ($hits > 14 && $hits < 24) {
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
            if ($countCtns === 0) return false;

            // Obtener la información final de los cartones ganadores
            $ctnsSerials = BingoCtns::whereIn('id', $serialCtns)
                ->where('room', $roomID)
                ->get(['id', 'seller', 'creator_user', 'room_user', 'coin', 'lot']);

            // Repartir el premio entre los ganadores
            $winnerJackpotPrize = $serialPrize / count($serialCtns);

            $this->registerPrizeCtn($roomID, $round, $ctnsSerials, 'Serial', $winnerJackpotPrize);

            return true;
        } catch (Exception $e) {
            Log::error('Error en handleSerialWinners(): ' . $e->getMessage());
            return false;
        }
    }

    public function getWinnersSerials($roomID, $roomUser, $coin) // Obtener Seriales Ganadores
    {
        $data = $this->getWinnersData($roomID, 'SERIAL', $roomUser);

        // Si existe algun error
        if (isset($data['Error'])) return $data['Error'];

        $exchangeRate = 1;
        $coin = $coin ? $coin : $data['coin'];

        if (!$data['winPlayer']) {
            $exchangeRate = GameExchangeRate::getGameExchangeRateValue('BINGO', $roomID, $coin) ?? 1;
        }

        return $this->getArrayWinnersData($exchangeRate, $data['winPlayer'], $data['winners']);
    }

    // Obtener Informacion de un carton especifico (encriptado)
    public function getCtnWin($ctn)
    {
        // Desencriptar ID del carton
        $ctnId = decrypt($ctn);

        // Obtener el cartón
        $ctn = BingoCtns::find($ctnId);

        // Si no se encuentra el cartón, retornar false
        if (!$ctn) return false;

        // Obtener los números del cartón en un solo array
        $allCtn = [
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
            $ctn->n3,
            $ctn->n4,
            $ctn->g1,
            $ctn->g2,
            $ctn->g3,
            $ctn->g4,
            $ctn->g5,
            $ctn->o1,
            $ctn->o2,
            $ctn->o3,
            $ctn->o4,
            $ctn->o5
        ];

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

        // Retornar los datos del cartón
        return [
            'ctn' => $allCtn,
            'playerName' => $fullName,
            'serialCtn' => $ctn->serial
        ];
    }

    public function allWinCtnPaid($room)
    {
        // Verificar la cantidad total de cartones premiados y la cantidad pagada
        $winnersCount = BingoRoomUser::where('room', $room)->where('winner', '>', 0)->count();
        $winnersPaidCount = BingoRoomUser::where('room', $room)->where('winner', 2)->count();

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

    function registerPrizeCartonPayment($prizeCtn, $payment, $sender, $filePayment)
    {
        try {

            return BingoPrizeCtnsPayment::create([
                'prize_ctn' => $prizeCtn->id,
                'payment' => $payment,
                'room' => $prizeCtn->room,
                'creator_user' => $prizeCtn->creator_user,
                'seller' => $prizeCtn->seller,
                'player_link' => PlayerLink::getValueByRoomUser('BINGO', $prizeCtn->room_user, 'id'),
                'sender' => $sender,
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

    public function handleRoundsConfig($roomID)
    {
        try {

            $existsRounds = BingoRounds::existsRounds($roomID);

            if ($existsRounds) return;

            $roundsDraft = BingoRoundsDrafts::getByRoom($roomID, [
                'rounds',
                'prize_types',
                'play_types',
                'prize_values'
            ]);

            if (!$roundsDraft) {
                throw new Exception("No draft config found for room {$roomID}");
            }

            $roundsCount = $roundsDraft->rounds;
            $prizeTypes = $roundsDraft->prize_types;
            $playTypes = $roundsDraft->play_types;
            $prizeValues = $roundsDraft->prize_values;

            if (
                count($prizeTypes) < $roundsCount ||
                count($playTypes) < $roundsCount ||
                count($prizeValues) < $roundsCount
            ) {
                throw new Exception("Incomplete draft config for room {$roomID}");
            }

            $timestamp = now();
            $data = [];
            $status = 'ACTIVE';


            for ($i = 0; $i < $roundsCount; $i++) {

                $numRound = $i + 1;

                if ($numRound == 1) {
                    $status = 'IN PROGRESS';
                }

                $data[] = [
                    'room' => $roomID,
                    'round' => $numRound,
                    'prize_type' => $prizeTypes[$i],
                    'play_type' => $playTypes[$i],
                    'prize_value' => $prizeValues[$i],
                    'status' => $status,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ];
            }

            if (!empty($data)) {
                BingoRounds::insert($data);
                BingoRoundsDrafts::where('room', $roomID)->update(['status' => 'COMPLETED']);
            }
            return true;
        } catch (Exception $e) {
            Log::error('Error en handleRoundsConfig: ' . $e->getMessage());
            return false;
        }
    }
}
