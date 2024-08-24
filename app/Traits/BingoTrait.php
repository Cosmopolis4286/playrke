<?php

namespace App\Traits;

use App\Events\Games\BingoOFF_ProccessDraw;
use App\Models\SellerRoomLimits;
use App\Models\BingoCtns;
use App\Models\BingoRoom;
use App\Models\BingoSort;
use App\Models\BingoSortCode;
use App\Models\BingoCtnsLot;
use App\Models\BingoCtnsPayment;
use App\Models\BingoPrizeCtns;
use App\Models\BingoPrizeCtnsPayment;
use App\Models\BingoRoomConfig;
use App\Models\BingoRoomUser;
use App\Models\Coin;
use App\Models\CoinGameRoom;
use App\Models\GameExchangeRate;
use App\Models\ExchangeRate;
use App\Models\PlayerLink;
use App\Models\Seller;
use App\Models\Transactions;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait BingoTrait
{
    use UserVal, EncryptRKE, GameTrait, PlayerLinkTrait, SeamlessWallet;

    public function newSort($room, $PJLinkWin = '')
    {
        date_default_timezone_set('America/Caracas');

        $sort = BingoSort::where('room', $room)->count() === 75;

        if (!$sort) {
            $this->encryptSort('bingo_sort_code', $room);

            $roomInfo = BingoRoom::getRoomById($room, ['date', 'time']);
            $dataTime = strtotime("{$roomInfo->date} {$roomInfo->time}");

            $symbols = BingoSortCode::where('room', $room)->pluck('sym', 'num');
            $existingChips = BingoSort::where('room', $room)->pluck('chip');

            DB::beginTransaction();

            try {
                $chips = [];
                $chp = 1;
                $inserts = [];
                $lastSortTime = BingoSort::where('room', $room)->orderByDesc('id')->value('time') ?? $dataTime;
                $currentTimestamp = now();

                while ($chp <= 75) {
                    do {
                        $rnd = mt_rand(1, 75);
                    } while (in_array($rnd, $chips) || $existingChips->contains($rnd));

                    $numEncoded = collect(str_split($rnd))
                        ->map(fn($digit) => $symbols[$digit == '0' ? 10 : $digit] ?? '')
                        ->implode('');

                    $timeIncrement = $chp === 1 ? 8 : 9;
                    $currentSortTime = $lastSortTime + $timeIncrement;

                    $inserts[] = [
                        "chip" => $rnd,
                        "code" => $numEncoded,
                        "room" => $room,
                        "time" => $currentSortTime,
                        "created_at" => $currentTimestamp,
                        "updated_at" => $currentTimestamp
                    ];

                    $chips[] = $rnd;
                    $lastSortTime = $currentSortTime;
                    $chp++;
                }

                BingoSort::insert($inserts); // Inserción masiva
                DB::commit();
                return $this->chipWin($room);
            } catch (\Throwable $th) {
                DB::rollBack();
                throw $th;
            }
        } else if ($sort && $PJLinkWin && $this->user()->user_type == 2) {

            $ctn = BingoCtns::where('room', $room)->where('player_link', $PJLinkWin)->inRandomOrder()->first();

            if (!$ctn) return false;

            $ctnFull = collect($ctn)->only([
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
            ])->values()->shuffle()->all();

            $sort = BingoSort::where('room', $room)->whereNotIn('chip', $ctnFull)->inRandomOrder()->pluck('chip')->toArray();

            $firstFour = array_slice($sort, 0, 4); // Obtener los primeros 4 elementos
            $sort = array_slice($sort, 4); // Eliminar los primeros 4 elementos del array original

            $newSort = array_merge($firstFour, $ctnFull, $sort); // Nuevo Sort

            $newSortValues = array_map(function ($chip) {
                return intval($chip);
            }, $newSort);

            $OrigSort = BingoSort::where('room', $room)->pluck('id');

            // Construir un arreglo asociativo de ID => Chip para actualizar
            $updateData = array_combine($OrigSort->toArray(), $newSortValues);

            DB::beginTransaction();

            try {
                // Construir la consulta SQL dinámica para actualizar los chips en función de sus IDs
                $sql = 'UPDATE bingo_sort SET chip = CASE id ';
                $bindings = [];
                foreach ($updateData as $id => $chip) {
                    $sql .= 'WHEN ? THEN ? ';
                    $bindings[] = $id;
                    $bindings[] = $chip;
                }
                $sql .= 'END WHERE room = ?';
                $bindings[] = $room;

                // Ejecutar la consulta SQL con bindings
                DB::update($sql, $bindings);

                DB::commit();
                return true;
            } catch (\Throwable $th) {
                DB::rollBack();
            }
        }

        $winChip = BingoSort::getWinningChip($room);
        if (!$winChip) return $this->chipWin($room);
        return false;
    }

    public function chipWin($room)
    {
        date_default_timezone_set('America/Caracas');

        DB::beginTransaction();

        try {
            // Actualizar el estado de las fichas que cumplen cierta condición
            BingoSort::where('win', 1)->where('room', $room)->update(['status' => NULL]);

            $allCartonsValues = array_map('array_values', BingoCtns::where('room', $room)
                ->get([
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
                ])->toArray());

            if (!$allCartonsValues) {
                $firstChip = BingoSort::getFirstSortChip($room, 'id');
                $winChip = $firstChip->id + 23;

                BingoSort::where('id', $winChip)->where('room', $room)->update(['win' => 1]);
                DB::commit();
                return BingoSort::where('id', $winChip)->where('room', $room)->first();
            }

            // Buscar la primera ficha común
            $chipsVan = [];
            // Obtener todas las fichas ordenadas de forma descendente
            $sortDesc = BingoSort::where('room', $room)->orderByDesc('id')->pluck('chip');

            $sortDesc->first(function ($value) use ($allCartonsValues, &$chipsVan) {
                static $arrCtns = [];
                foreach ($allCartonsValues as $key => $carton) {
                    if (in_array($value, $carton) && !in_array($key, $arrCtns)) {
                        $arrCtns[] = $key;
                        if (!in_array($value, $chipsVan)) {
                            $chipsVan[] = $value;
                        }
                    }
                }
            });

            $sortAsc = BingoSort::where('room', $room)->pluck('chip');
            $chipVan = $sortAsc->first(function ($value) use ($chipsVan) {
                return in_array($value, $chipsVan);
            });

            if ($chipVan) {
                BingoSort::where('chip', $chipVan)->where('room', $room)->update(['win' => 1]);

                $exchangeRate = $this->insertGameExchangeRate($room, 1); // Registrar Tasas de Cambio
                if (!$exchangeRate) DB::rollBack();

                DB::commit();
                return BingoSort::where('chip', $chipVan)->where('room', $room)->first();
            }
        } catch (\Throwable $th) {
            DB::rollBack();
        }
    }

    public function recorrersorteo($room)
    {
        date_default_timezone_set('America/Caracas');

        $encrypted = BingoRoom::getValueById($room, 'encrypted');

        $result = false;
        do {
            $chip = BingoSort::select('id', 'chip', 'status', 'win')->where('room', $room)
                ->where('time', '<=', time())->where('status', 1)->first();

            //if ($chip->win == 1) $result = $this->getInfoWinners($room);
            event(new BingoOFF_ProccessDraw($encrypted, $chip, $result));

            $chip->update(['status' => 2]);

            if ($chip->win == 1) BingoRoom::where('id', $room)->update(['status' => 4]);
        } while (BingoRoom::where('id', $room)->where('status', 3)->exists());
    }

    public function getInfoWinners($room, $column, $value)
    {
        date_default_timezone_set('America/Caracas');

        $room = BingoRoom::getRoomByEncrypted($room, ['id', 'date', 'time', 'status', 'encrypted']);

        $startTime = $this->convertToUnixTimestamp($room->date, $room->time);

        if ($room->status == 3 && $startTime <= time()) {
            BingoRoom::where('id', $room->id)->update(['status' => 4]);
        }

        $getWinnersJackpot = BingoPrizeCtns::getWinnersCountByPrizeType($room->id, 1);

        if (!$getWinnersJackpot && $room->status == 4) $this->handleJackpotWinners($room->id);

        $jackpotWinners = $this->getWinnersJackpot($room->id, $column, $value);
        $serialsWinners = (BingoPrizeCtns::getWinnersCountByPrizeType($room->id, 2)) ? $this->getWinnersSerials($room->id, $PJLink) : [];
        $prizeSerialVal = BingoRoomConfig::getValueByRoom($room->id, 'val_serials');

        $prizesValues = !empty($serialsWinners) ? [100 - $prizeSerialVal, $prizeSerialVal] : [100];

        return ['jackpotWinners' => $jackpotWinners, 'serialsWinners' => $serialsWinners, 'prizesValues' => $prizesValues];
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

    public function cartonStore($arrCtn)
    {
        date_default_timezone_set('America/Caracas');

        $roomID = $arrCtn['room'];

        $this->checkRoomClosure($roomID);

        $room = BingoRoom::select(
            'id',
            'game_mode',
            'players',
            'ctn_price',
            'all_sale',
            'commerce_commn',
            'seller_commn',
            'jackpot'
        )->where('status', 2)->where('id', $roomID)->first();

        if (!$room) return redirect()->back();

        try {
            $customer = $room['customer'];
            $commerce = $arrCtn['commerce'];
            $seller = $arrCtn['seller'];
            $coin = $arrCtn['coin'];
            $numOfCtns = $arrCtn['numOfCtns'];
            $PJLink = $arrCtn['PJLink'];
            $playerUser = $arrCtn['playerUser'];
            $totalAmountCtns = $numOfCtns * $room->ctn_price;

            if ($room->game_mode == 2) {
                // Obtener o crear la comisión del vendedor para la sala
                $commnSeller = SellerRoomLimits::getGameRoomValueBySeller(1, $seller, $roomID, 'commn');

                if (!$commnSeller) {
                    $commnSeller = Seller::getValueById($seller, 'commn');

                    SellerRoomLimits::create([
                        'game' => 1, // Bingo 
                        'room' => $roomID,
                        'seller' => $seller,
                        'commn' => $commnSeller
                    ]);
                }

                // Conversión de Moneda para obtener valor de venta
                $exchangeRate = ExchangeRate::getLastValueByCoin($coin);
                $totalSaleCtns = $numOfCtns * $exchangeRate;
                $comSaleSellerCtns = number_format($totalSaleCtns * ($commnSeller / 100), 2, '.', '');
                $saleSellerCtns = $totalSaleCtns - $comSaleSellerCtns;
            }

            // Crear lote de cartones de bingo
            $ctnsLot = BingoCtnsLot::create([
                'customer' => $customer,
                'commerce' => $commerce,
                'seller' => $seller,
                'room' => $roomID,
                'coin' => $coin,
                'exchange_rate' => $exchangeRate,
                'player_user' => $playerUser,
                'player_link' => $PJLink,
                'status' => 0,
                'ctns' => $numOfCtns,
                'sale_seller_ctns' => $saleSellerCtns ?? NULL,
            ]);

            if ($customer) { // Si el proceso es de un cliente externo  
                $customerResponse = false;
                $customerResponse = $this->getUserBalanceFromCustomer($customer, []);

                if (!$customerResponse['Error']) {
                    // Ocurrio un error inesperado
                    DB::rollBack();
                }

                if ($customerResponse['Data']['amount'] < $totalAmountCtns) {
                    // Saldo insuficiente
                    DB::rollBack();
                }
            }

            $lotID = $ctnsLot->id; // ID de Lote de Cartones

            // Obtener cartones existentes para verificación de duplicados
            $existingCtns = BingoCtns::where('room', $roomID)->get()->map(function ($val) {
                return [
                    $val->b1,
                    $val->b2,
                    $val->b3,
                    $val->b4,
                    $val->b5,
                    $val->i1,
                    $val->i2,
                    $val->i3,
                    $val->i4,
                    $val->i5,
                    $val->n1,
                    $val->n2,
                    $val->n3,
                    $val->n4,
                    $val->g1,
                    $val->g2,
                    $val->g3,
                    $val->g4,
                    $val->g5,
                    $val->o1,
                    $val->o2,
                    $val->o3,
                    $val->o4,
                    $val->o5,
                ];
            });

            // Generar cartones de bingo
            $x = 1;
            $initials = $arrCtn['initials'];
            $numLastCtn = BingoCtns::getLastCtnCreated($roomID);
            $numLastCtn = $numLastCtn ? $numLastCtn->id : 0;

            $recordsToInsert = [];

            while ($x <= $numOfCtns) {
                $nwCtn = array_merge(
                    $this->generateRandomNumbers(1, 15, 5),
                    $this->generateRandomNumbers(16, 30, 5),
                    $this->generateRandomNumbers(31, 45, 4),
                    $this->generateRandomNumbers(46, 60, 5),
                    $this->generateRandomNumbers(61, 75, 5)
                );

                if (!$existingCtns->contains($nwCtn)) {
                    $numLastCtn++;
                    $serialCtn = $initials . '-' . $numLastCtn;

                    // Preparar el array para insertar
                    $recordsToInsert[] = [
                        'room' => $roomID,
                        'customer' => $customer,
                        'commerce' => $commerce,
                        'seller' => $seller,
                        'player_user' => $playerUser,
                        'player_link' => $PJLink,
                        'coin' => $coin,
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
                        'created_at' => $this->getTimestamp()
                    ];
                    $x++;
                }
            }

            // Insertar todos los registros en una sola consulta
            BingoCtns::insert($recordsToInsert);

            // Determinar la columna y el ID del jugador según el modo de juego
            $playerColumn = ($room->game_mode == 1) ? 'player_user' : 'player_link';
            $playerID = ($room->game_mode == 1) ? $playerUser : $PJLink;

            // Preparar los datos para buscar o crear el registro
            $searchCriteria = ['room' => $roomID, $playerColumn => $playerID];
            $defaultValues = [
                'room' => $roomID,
                'commerce' => $commerce,
                'seller' => $seller,
                'player_user' => $playerUser,
                'player_link' => $PJLink
            ];

            // Registrar usuario en la sala si no está ya registrado
            $roomUser = BingoRoomUser::firstOrCreate($searchCriteria, $defaultValues);

            if ($roomUser->wasRecentlyCreated) {
                $room->increment('players');
            }

            // Actualización del bote acumulado y otras métricas de la sala
            $convertedJackpot = number_format($saleSellerCtns / $exchangeRate, 3, '.');
            $boatAcum = ($arrCtn['jackpotType'] == 1) ? $convertedJackpot : (($arrCtn['jackpotType'] == 2) ? 0 : null);
            $jackpot = $room->jackpot + $boatAcum;
            $comCommerce = ($jackpot * BingoRoomConfig::getValueByRoom($roomID, 'commerce_commn') / 100);
            $jackpot -= $comCommerce;

            // Preparar los datos para actualizar la sala
            $updateData = [
                'all_sale' => $room->all_sale + $totalAmountCtns,
                'jackpot' => $jackpot,
                'jackpot_prize' => $jackpot,
                'ctns' => $room->ctns + $numOfCtns
            ];

            if ($room->game_mode) {
                $updateData['commerce_commn'] = $room->commerce_commn + $comCommerce;
                $updateData['seller_commn'] = $room->seller_commn + $comSaleSellerCtns;
            }

            // Actualiza la sala con los datos preparados
            $room->update($updateData);

            // Registrar Nueva Moneda en CoinsGameRoom si no existe
            if (!CoinGameRoom::checkCoinGameRoom($coin, $roomID, 1)) {
                CoinGameRoom::create(['game' => 1, 'room' => $roomID, 'coin' => $coin]);
            }

            if ($customer) { // Si existe un cliente afiliado

                $userToken = decrypt(session('clientUser'));
                $coinClientUser = decrypt(session('coinClientUser'));

                $transaction = $this->registerTransaction([1, 1], $customer, $playerUser, 'BET', $totalAmountCtns);

                // Enviar informacion al cliente afiliado por una url externa
                $transactionResponse = $this->transferMoney(
                    $customer,
                    $userToken,
                    $this->getHashMD5($lotID),
                    $coinClientUser,
                    $totalAmountCtns,
                    $transaction[1],
                    'BET',
                    'BINGO'
                );

                if ($transactionResponse['Error']) DB::rollBack();
                // Cambiar status si la respuesta es exitosa
                Transactions::where('id', $transaction[0])->update(['status' => 1]);
            }

            if ($arrCtn['ctnWin'] == true) $this->newSort($room, true);

            return true;
        } catch (\Exception $e) {
            dd($e->getMessage());
            Log::error('Error al crear los cartones: ' . $e->getMessage());
            return false;
        }
    }

    public function handleCartonStore($data = [])
    {
        $roomID = $data['room'];
        $room = BingoRoom::with('Config')->where('encrypted', $roomID)->select('id', 'status')->first();

        if ($room->status > 2) {
            return redirect()->back()->with('message', 'Sala no disponible');
        }

        $roomID = $room->id;
        $customer = $data['customer'];
        $country = $data['country'];
        $coin = Coin::getByIdIdOfCountry($country);
        $player = false;
        $playerPhone = $data['playerPhone'];
        $playerUser = $data['playerUser'];

        if (!$customer) {
            $seller = $this->seller(['id', 'commerce']);

            $player = PlayerLink::where('game', 1)->where('room', $roomID)
                ->where('phone', $playerPhone)->first();

            $playerName = $data['playerName'];
            $playerLastname = $data['playerLastname'];
            $playerInitials = $this->initialCharacters([$playerName, $playerLastname]);
        }

        DB::beginTransaction();

        try {

            if (!$player) {
                $PJLink = $this->createPlayerLink(1);
                $playerDocument = null;

                $arrDataPlayer = [
                    'game' => 1,
                    'room' => $roomID,
                    'commerce' => $seller->commerce,
                    'seller' => $seller->id,
                    'country' => $country,
                    'coin' => $coin,
                    'link' => $PJLink,
                    'name' => $playerName,
                    'lastname' => $playerLastname,
                    'identification' => $playerDocument,
                    'phone' => $playerPhone
                ];

                $player = $this->createPlayerLinkInfo($arrDataPlayer);

                if (!$player) {
                    DB::rollBack();
                    Log::error('Hubo un error al crear la información del jugador');
                    return false;
                }
            } else {
                $player = $player->id ?? null;
            }

            $arrDataCtns = [
                'room' => $roomID,
                'customer' => $customer,
                'commerce' => $seller->commerce,
                'seller' => $seller->id,
                'playerUser' => $playerUser,
                'PJLink' => $player,
                'initials' => $playerInitials[0] . $playerInitials[1],
                'coin' => $coin,
                'numOfCtns' => $data['numOfCtns'],
                'jackpotType' => $room->Config->jackpot_type,
                'ctnWin' => false
            ];

            $carton = $this->cartonStore($arrDataCtns);

            if ($carton) {
                DB::commit();
                return $PJLink ?? true;
            } else {
                DB::rollBack();
                return false;
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear la información del jugador: ' . $e->getMessage());
            return false;
        }
    }

    public function convertPrizeValue($room, $coin, $type = 'string')
    {
        $room = BingoRoom::getRoomById($room, ['customer', 'status']);

        if (!$room->customer) {
            $exchangeRate = ($room->status == 2)
                ? ExchangeRate::getLastValueByCoin($coin)
                : GameExchangeRate::getGameExchangeRateValue(1, $room, $coin);

            if (!$exchangeRate) $this->insertGameExchangeRate($room, 1);

            $jackpotPrize = BingoRoom::getValueById($room, 'jackpot_prize');
        }

        $jackpot = $jackpotPrize * ($exchangeRate ?? 1);

        if ($type == 'string') {
            // Formatear como cadena y eliminar ceros y comas finales
            return rtrim(number_format($jackpot, 2, ',', '.'), '0,');
        } else {
            // Devolver como número de punto flotante
            return (float) $jackpot;
        }
    }

    private function registerPrizeCtn($room, $playerCtn, $prizeType, $USDValue)
    {
        date_default_timezone_set('America/Caracas');

        // Verificar y obtener el tipo de cambio
        $exchangeRate = GameExchangeRate::getGameExchangeRateValue(1, $room, $playerCtn[0]->coin);
        if (!$exchangeRate) {
            $this->insertGameExchangeRate($room, 1);
            $exchangeRate = GameExchangeRate::getGameExchangeRateValue(1, $room, $playerCtn[0]->coin);
        }

        $recordsToInsert = [];
        $playersToUpdate = [];
        $customer = $playerCtn[0]->customer;
        $gameMode = BingoRoom::getValueById($room, 'game_mode');

        foreach ($playerCtn as $ctn) {
            if (!BingoPrizeCtns::existsPrizeCtn($room, 1, $ctn->player_link)) {
                $prizeWn = number_format($USDValue * $exchangeRate, 3, '.', '');
                $timestamp = now();

                $recordsToInsert[] = [
                    'room' => $room,
                    'player_user' => $ctn->player_user,
                    'player_link' => $ctn->player_link,
                    'commerce' => $ctn->commerce,
                    'seller' => $ctn->seller,
                    'ctn' => $ctn->id,
                    'lot' => $ctn->lot,
                    'prize_type' => $prizeType,
                    'coin' => $ctn->coin,
                    'amount' => $prizeWn,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp
                ];

                // Determinar el campo de actualización basado en la presencia del cliente afiliado
                $playersToUpdate[] = $customer ? $ctn->user : $ctn->player_link;
            }
        }

        try {

            DB::beginTransaction();

            //Determinar la columna según el modo de juego
            $playerColumn = ($gameMode == 1) ? 'user' : 'player_link';

            // Actualizar el estado del jugador
            if ($playersToUpdate) {
                BingoRoomUser::where('room', $room)->whereIn($playerColumn, $playersToUpdate)
                    ->where('winner', 0)->update(['winner' => 1]);
            }

            // Insertar los registros de una sola vez
            if ($recordsToInsert) {
                BingoPrizeCtns::insert($recordsToInsert);
                if ($customer) {

                    $userToken = decrypt(session('clientUser'));
                    $coinClientUser = decrypt(session('coinClientUser'));

                    foreach ($playerCtn as $ctn) {
                        $transaction = $this->registerTransaction([1, 1], $customer, $ctn->user, 'WIN', $prizeWn);

                        // Enviar informacion al cliente afiliado por una url externa
                        $transactionResponse = $this->transferMoney(
                            $customer,
                            $userToken,
                            $this->getHashMD5($ctn->id),
                            $coinClientUser,
                            $prizeWn,
                            $transaction[1],
                            'WIN',
                            'BINGO'
                        );

                        if ($transactionResponse['Error']) DB::rollBack();
                        // Cambiar status si la respuesta es exitosa
                        Transactions::where('id', $transaction[0])->update(['status' => 1]);
                    }
                }
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            dd($e->getMessage());
            Log::error('Error al crear ganador o actualizar estado: ' . $e->getMessage());
            return false;
        }
    }

    public function handleJackpotWinners($roomID)
    {
        // Obtener la información de la sala
        $room = BingoRoom::with('Config')
            ->select('id', 'jackpot', 'jackpot_prize', 'serial_prize')
            ->where('id', $roomID)->firstOrFail();

        // Verificar si ya hay ganadores del premio mayor
        $getWinners = BingoPrizeCtns::getWinnersCountByPrizeType($roomID, 1);

        if ($getWinners) {
            DB::rollBack();
            return false;
        }
        // Obtener el último chip sorteado
        $chpVn = BingoSort::where('win', 1)->where('room', $roomID)->select('id', 'chip')->firstOrFail();

        // Obtener todos los chips sorteados hasta el último
        $chipSrt = BingoSort::where('room', $roomID)->where('id', '<=', $chpVn->id)->pluck('chip')->toArray();

        // Obtener todos los cartones de la sala
        $ctnsPlayer = BingoCtns::where('room', $roomID)->get();

        // Filtrar cartones ganadores
        $filteredCtns = $ctnsPlayer->filter(function ($ctn) use ($chpVn, $chipSrt) {
            static $uniquePlayers = [];
            $playerId = $ctn->player_link;

            // Obtener todos los valores del cartón del jugador
            $ctnFull = collect($ctn)->only([
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
            ])->values()->all();

            // Verificar si el chip está en el cartón y si todos los valores del cartón están en $chipSrt 
            if (in_array($chpVn->chip, $ctnFull) && empty(array_diff($ctnFull, $chipSrt))) {
                // Verificar si el jugador es único
                if (!in_array($playerId, $uniquePlayers)) {
                    $uniquePlayers[] = $playerId;
                    return true;
                }
            }
            return false;
        });

        // Obtener IDs de los cartones ganadores
        $arrCtnWin = $filteredCtns->pluck('id');

        // Obtener información de los cartones ganadores
        $playerCtn = BingoCtns::where('room', $roomID)->whereIn('id', $arrCtnWin)
            ->get(['id', 'commerce', 'seller', 'player_user', 'player_link', 'coin', 'lot']);

        // Obtener la cantidad de ganadores distintos
        $winnersCount = $filteredCtns->pluck('player_link')->unique()->count();

        $nonWinnersCount = BingoRoomUser::getNonWinnersCountByRoom($roomID);

        $generateSerials = $nonWinnersCount - count($arrCtnWin);

        try {

            $USDPrize = $room->jackpot_prize / $winnersCount;

            // Calcular premios si hay premio serial
            if ($room->Config->val_serials > 0  && $room->serial_prize == 0 && $generateSerials > 0) {
                $serialPrize = $room->jackpot * $room->Config->val_serials / 100;
                $jackpotPrize = $room->jackpot - $serialPrize;

                BingoRoom::where('id', $roomID)->update([
                    'jackpot_prize' => $jackpotPrize,
                    'serial_prize' => $serialPrize,
                    'close_room' => Carbon::now('America/Caracas')->timestamp
                ]);
                $USDPrize = $jackpotPrize / $winnersCount;
            }

            // Crear registros de ganadores y actualizar el estado de los jugadores
            $registerPrizeCtn = $this->registerPrizeCtn($roomID, $playerCtn, 1, $USDPrize);
            if (!$registerPrizeCtn) DB::rollBack();

            // Generar seriales ganadores si hay jugadores no ganadores
            if ($generateSerials) {
                $this->handleSerialWinners($roomID); // Ejecutar Obtener Seriales Ganadores
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            // Registrar el error
            Log::error('Error en handleJackpotWinners: ' . $e->getMessage());
            dd($e->getMessage());
            // Devolver un mensaje de error
            return response()->json(['error' => 'Hubo un error al procesar la solicitud.'], 500);
        }
    }

    public function getWinnersJackpot($roomID, $column, $value)
    {
        $room = BingoRoom::select('id', 'jackpot_prize')
            ->where('id', $roomID)->where('status', 4)->first();

        if ($room) {
            // Obtener todos los ganadores y verificar si el jugador específico está entre ellos
            $winners = BingoRoomUser::with('PlayerLink', 'WinCtns')
                ->whereHas('WinCtns', function ($query) {
                    $query->where('prize_type', 1);
                })->where('room', $roomID)->where('winner', '>', 0)->get();

            $numWinners = $winners->count(); // Contar todos los ganadores

            if ($numWinners == 0) $this->handleJackpotWinners($room);

            // Verificar si el jugador específico está entre los ganadores
            $winPlayer = $winners->firstWhere('player_link', $PJLink);

            // Obtener el país del jugador y la moneda correspondiente
            $playerCountry = PlayerLink::getValueById($PJLink, 'country');
            $coin = Coin::getValueByCountry($playerCountry, 'id');
            $boat = $room->jackpot_prize * GameExchangeRate::getGameExchangeRateValue(1, $roomID, $coin);

            // Calcular la ganancia por jugador y formatearla
            $boat = $this->convertPrizeValue($roomID, $coin, 'float');
            $formattedNumber = number_format($boat / $numWinners, 3, ',', '.');
            $gain = rtrim(rtrim($formattedNumber, '0'), ',');

            // Preparar la información del jugador
            $infoPlayer = ['size' => 0, 'name' => [], 'foto' => [], 'gain' => [], 'ctns' => []];

            // Añadir información del jugador específico es un ganador
            if ($winPlayer) {
                $infoPlayer['size'] = 1;
                $playerLastName = $this->initialCharacters($winPlayer->PlayerLink->lastname);
                $infoPlayer['name'][] = $winPlayer->PlayerLink->name . ' ' . $playerLastName;
                $infoPlayer['gain'][] = $gain;
                $infoPlayer['ctns'][] = $winPlayer->PlayerLink->WinCtns[0]->ctn;
            }

            // Añadir información de todos los demás ganadores
            foreach ($winners as $win) {
                // Evitar añadir el jugador específico de nuevo si ya está en la lista de ganadores
                if (!$winPlayer || $win->player_link != $winPlayer->player_link) {
                    $infoPlayer['size'] += 1;
                    $playerLastName = $this->initialCharacters($win->PlayerLink->lastname);
                    $infoPlayer['name'][] = $win->PlayerLink->name . ' ' . $playerLastName;
                    $infoPlayer['gain'][] = $gain;
                    $infoPlayer['ctns'][] = $win->PlayerLink->WinCtns[0]->ctn;
                }
            }

            return $infoPlayer;
        }
    }

    public function handleSerialWinners($room) // Obtener Seriales Ganadores
    {
        $getWinners = BingoPrizeCtns::getWinnersCountByPrizeType($room, 2);
        if ($getWinners) return false;

        $chpVn = BingoSort::where('room', $room)->where('status', 1)->value('id');

        $chipSrt = BingoSort::where('room', $room)->where('id', '<', $chpVn)->pluck('chip')->toArray();
        $ctnsPlayer = BingoCtns::where('room', $room)->get();

        $countSerials = BingoRoomConfig::getValueByRoom($room, 'num_serials');

        // Filtrar los registros en $ctnsPlayer que contienen $chpVn->chip y tienen 23 aciertos
        $filteredCtns = $ctnsPlayer->filter(function ($ctn) use ($chipSrt, $room, $countSerials) {
            static $countCtns = 0; // Contador estático para el número de cartones
            static $uniquePlayers = [];
            $playerId = $ctn->player_link;

            // Detener el filtrado cuando se alcanza el límite de seriales
            if ($countCtns >= $countSerials)  return false;

            $ctnFull = collect($ctn)->only([
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
            ])->values()->all();

            $aciertos = array_intersect($ctnFull, $chipSrt);

            $existsPrizeCtn = BingoPrizeCtns::existsPrizeCtn($room, 1, $playerId);

            // Incrementar el contador de cartones si hay 23 aciertos en el cartón actual
            if (count($aciertos) === 23 && !in_array($playerId, $uniquePlayers) && !$existsPrizeCtn) {
                $uniquePlayers[] = $playerId;
                $countCtns++;
                return true;
            }
            return false;
        });

        if (!count($filteredCtns)) return false; // Si no hay ganadores con 23 aciertos

        $arrSerialsPlayers = $filteredCtns->pluck('serial')->toArray();

        $ctnSerials = BingoCtns::whereIn('serial', $arrSerialsPlayers)
            ->where('room', $room)->get(['id', 'commerce', 'seller', 'player_user', 'player_link', 'coin', 'lot']);

        $USDPrize = BingoRoom::getSerialPrize($room) / count($arrSerialsPlayers);
        try {
            $registerPrizeCtn = $this->registerPrizeCtn($ctnSerials, 2, $USDPrize, $room);
            if (!$registerPrizeCtn) DB::rollBack();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear ganador o actualizar estado: ' . $e->getMessage());
            return response()->json(['error' => 'Hubo un error al procesar la solicitud.'], 500);
        }
    }

    public function getWinnersSerials($roomID, $column, $value) // Obtener Seriales Ganadores
    {
        $room = BingoRoom::select('id', 'serial_prize')
            ->where('id', $roomID)->where('status', 4)->first();

        if ($room) {
            // Obtener todos los ganadores y verificar si el jugador específico está entre ellos
            $winners = BingoRoomUser::with('PlayerLink')
                ->whereHas('PlayerLink.WinCtns', function ($query) {
                    $query->where('prize_type', 2); // (2) Seriales
                })->where('room', $roomID)->where('winner', '>', 0)->get();

            $numWinners = $winners->count(); // Contar todos los ganadores

            // Verificar si el jugador específico está entre los ganadores
            $winPlayer = $winners->firstWhere('player_link', $PJLink);

            // Obtener el país del jugador y la moneda correspondiente
            $playerCountry = PlayerLink::getValueById($PJLink, 'country');
            $coin = Coin::getValueByCountry($playerCountry, 'id');
            $boat = $room->serial_prize * GameExchangeRate::getGameExchangeRateValue(1, $roomID, $coin);
            // Calcular la ganancia por jugador y formatearla
            $formattedNumber = number_format($boat / $numWinners, 3, ',', '.');
            $gain = rtrim(rtrim($formattedNumber, '0'), ',');

            // Preparar la información del jugador
            $infoPlayer = ['size' => 0, 'name' => [], 'foto' => [], 'gain' => [], 'ctns' => []];

            // Añadir información del jugador específico es un ganador
            if ($winPlayer) {
                $infoPlayer['size'] = 1;
                $infoPlayer['name'][] = BingoCtns::getCtnSerialByPlayerInRoom($roomID, $winPlayer->PlayerLink->id);
                $infoPlayer['gain'][] = $gain;
                $infoPlayer['ctns'][] = $winPlayer->PlayerLink->WinCtns[0]->ctn;
            }

            // Añadir información de todos los demás ganadores
            foreach ($winners as $win) {
                // Evitar añadir el jugador específico de nuevo si ya está en la lista de ganadores
                if (!$winPlayer || $win->player_link != $winPlayer->player_link) {
                    $infoPlayer['size'] += 1;
                    $infoPlayer['name'][] = BingoCtns::getCtnSerialByPlayerInRoom($roomID, $win->player_link);
                    $infoPlayer['gain'][] = $gain;
                    $infoPlayer['ctns'][] = $win->PlayerLink->WinCtns[0]->ctn;
                }
            }
            return $infoPlayer;
        }
    }

    public function getCtnWin($ctn)
    {
        $ctn = BingoCtns::with('PlayerLink')->where('id', $ctn)->first();

        if ($ctn) {
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

            $playerName = $ctn->PlayerLink->name;
            $playerLastName = $this->initialCharacters($ctn->PlayerLink->lastname);

            return [
                'ctn' => $allCtn,
                'playerName' => $playerName . ' ' . $playerLastName,
                'serialCtn' => $ctn->serial
            ];
        }
        return null;
    }

    public function checkRoomClosure($room = '')
    {
        date_default_timezone_set('America/Caracas');

        $rooms = $room ? BingoRoom::where('id', $room)
            ->whereIn('status', [2, 3, 4])
            ->get(['id', 'unix_date'])

            : BingoRoom::whereIn('status', [2, 3, 4])
            ->get(['id', 'unix_date']);

        foreach ($rooms as $room) {

            if (time() < $room->unix_date) continue;

            $existsCtns = BingoCtns::getCountCtnByRoom($room->id);

            if ($existsCtns) {
                $winChip = BingoSort::getWinningChip($room->id) ?? $this->newSort($room->id);
                $status = ($winChip->time <= time()) ? 4 : 3;
            } else {
                $status = 1; //N/D Borrador
            }

            BingoRoom::where('id', $room->id)->update(['status' => $status]);
        }

        $this->hideRoom();
    }

    public function hideRoom()
    {
        date_default_timezone_set('America/Caracas');

        $rooms = BingoRoom::where('status', 4)->get(['id', 'date', 'time']);
        if (count($rooms)) {
            foreach ($rooms as $value) {
                $dataTime = strtotime($value->date . ' ' . $value->time);
                // Sumar 2 semanas (14 días * 24 horas * 60 minutos * 60 segundos)
                $dataTime2Week = $dataTime + (2 * 7 * 24 * 60 * 60);

                if ($dataTime2Week <= time()) {
                    BingoRoom::where('id', $value->id)->update(['status' => 6]);
                }
            }
        }
        return true;
    }

    public function getBingoInfoByDateTrait($commerce, $date = 'today') {}

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

    function insertCartonPayment($arr)
    {
        $timestamp = now();
        // Crear un array de datos para insertar en la base de datos
        $data = [];
        foreach ($arr as $payment) {
            $data[] = [
                'operation' => $payment[0], // PAGO [CTN] ENVIADO DE VENDEDOR A LA COMERCIALIZADORA
                'room' => $payment[1],
                'sender' => $payment[2],
                'commerce' => $payment[3],
                'player_link' => $payment[4],
                'lot' => $payment[5],
                'ctn' => $payment[6],
                'coin' => $payment[7],
                'amount' => $payment[8],
                'payment_type' => $payment[9], // PAGO CARTON JUGADOR 
                'payment_proof' => $payment[10],
                'created_at' => $timestamp,
                'updated_at' => $timestamp

            ];
        }
        // Inserción masiva en la base de datos
        return BingoCtnsPayment::insert($data);
    }

    function registerCartonPayment($operation, $room, $commerce, $seller, $playerLink, $paymentType, $lot, $coin, $amount, $paymentImage = '')
    {
        date_default_timezone_set('America/Caracas');

        return BingoCtnsPayment::create([
            'operation' => $operation,
            'room' => $room,
            'commerce' => $commerce,
            'seller' => $seller,
            'player_link' => $playerLink,
            'payment_type' => $paymentType,
            'lot' => $lot,
            'coin' => $coin,
            'amount' => $amount,
            'payment_proof' => $paymentImage,
        ]);
    }

    function registerPrizeCartonPayment($prizeCtn, $operation, $room, $commerce, $seller, $playerLink, $sender, $prizeType, $lot, $ctn, $coin, $amount, $paymentImage = '')
    {
        date_default_timezone_set('America/Caracas');

        return BingoPrizeCtnsPayment::create([
            'prize_ctn' => $prizeCtn,
            'operation' => $operation,
            'room' => $room,
            'commerce' => $commerce,
            'seller' => $seller,
            'player_link' => $playerLink,
            'sender' => $sender,
            'prize_type' => $prizeType,
            'lot' => $lot,
            'ctn' => $ctn,
            'coin' => $coin,
            'amount' => $amount,
            'payment_proof' => $paymentImage,
        ]);
    }
}
