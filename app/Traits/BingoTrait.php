<?php

namespace App\Traits;

use App\Events\Games\BingoOFF_ProccessDraw;
use App\Models\SellerRoomLimits;
use App\Models\BingoCtns;
use App\Models\BingoRoom;
use App\Models\BingoDraw;
use App\Models\BingoDrawCode;
use App\Models\BingoCtnsLot;
use App\Models\BingoCtnsPayment;
use App\Models\BingoPrizeCtns;
use App\Models\BingoPrizeCtnsPayment;
use App\Models\BingoRoomConfig;
use App\Models\BingoRoomUser;
use App\Models\Coin;
use App\Models\CustomerExchangeRate;
use App\Models\CustomerUser;
use App\Models\GameExchangeRate;
use App\Models\ExchangeRate;
use App\Models\PlayerLink;
use App\Models\Seller;
use App\Models\Transactions;
use App\Models\User;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait BingoTrait
{
    use UserVal, EncryptRKE, GameTrait, RouteRKE, PlayerLinkTrait, SeamlessWallet;

    public function newDraw($room, $PJLinkWin = '')
    {
        date_default_timezone_set('America/Caracas');

        $sort = BingoDraw::where('room', $room)->count() === 75;

        if (!$sort) {
            $this->encryptDraw('bingo_draw_code', $room);

            $roomInfo = BingoRoom::getRoomById($room, ['date', 'time']);
            $dataTime = strtotime("{$roomInfo->date} {$roomInfo->time}");

            $symbols = BingoDrawCode::where('room', $room)->pluck('sym', 'num');
            $existingChips = BingoDraw::where('room', $room)->pluck('chip');

            DB::beginTransaction();

            try {
                $chips = [];
                $chp = 1;
                $inserts = [];
                $lastDrawTime = BingoDraw::where('room', $room)->orderByDesc('id')->value('time') ?? $dataTime;
                $currentTimestamp = now();

                while ($chp <= 75) {
                    do {
                        $rnd = mt_rand(1, 75);
                    } while (in_array($rnd, $chips) || $existingChips->contains($rnd));

                    $numEncoded = collect(str_split($rnd))
                        ->map(fn($digit) => $symbols[$digit == '0' ? 10 : $digit] ?? '')
                        ->implode('');

                    $timeIncrement = $chp === 1 ? 8 : 9;
                    $currentDrawTime = $lastDrawTime + $timeIncrement;

                    $inserts[] = [
                        "chip" => $rnd,
                        "code" => $numEncoded,
                        "room" => $room,
                        "time" => $currentDrawTime,
                        "created_at" => $currentTimestamp,
                        "updated_at" => $currentTimestamp
                    ];

                    $chips[] = $rnd;
                    $lastDrawTime = $currentDrawTime;
                    $chp++;
                }

                BingoDraw::insert($inserts); // Inserción masiva
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

            $sort = BingoDraw::where('room', $room)->whereNotIn('chip', $ctnFull)->inRandomOrder()->pluck('chip')->toArray();

            $firstFour = array_slice($sort, 0, 4); // Obtener los primeros 4 elementos
            $sort = array_slice($sort, 4); // Eliminar los primeros 4 elementos del array original

            $newDraw = array_merge($firstFour, $ctnFull, $sort); // Nuevo Draw

            $newDrawValues = array_map(function ($chip) {
                return intval($chip);
            }, $newDraw);

            $OrigDraw = BingoDraw::where('room', $room)->pluck('id');

            // Construir un arreglo asociativo de ID => Chip para actualizar
            $updateData = array_combine($OrigDraw->toArray(), $newDrawValues);

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

        $winChip = BingoDraw::getWinningChip($room);
        if (!$winChip || $PJLinkWin) return $this->chipWin($room);

        return false;
    }

    public function chipWin($room)
    {
        date_default_timezone_set('America/Caracas');

        DB::beginTransaction();

        try {
            // Actualizar el estado de las fichas que cumplen la condición
            BingoDraw::where('win', 1)->where('room', $room)->update(['status' => NULL]);

            // Obtener los valores de los cartones de la sala
            $allCartonsValues = BingoCtns::where('room', $room)
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
                ])->toArray();

            // Si no hay cartones, actualizamos la primera ficha ganadora
            if (empty($allCartonsValues)) {
                $firstChip = BingoDraw::getFirstDrawChip($room, 'id');
                $winChip = $firstChip->id + 23;

                BingoDraw::where('id', $winChip)->where('room', $room)->update(['win' => 1]);

                DB::commit();
                return BingoDraw::select('id', 'chip')->find($winChip);
            }

            $chipsVan = [];

            // Obtener todas las fichas ordenadas de forma descendente
            $sortDesc = BingoDraw::where('room', $room)->orderByDesc('id')->pluck('chip');

            // Buscar la primera ficha común
            $sortDesc->first(function ($value) use ($allCartonsValues, &$chipsVan) {
                static $arrCtns = [];
                foreach ($allCartonsValues as $key => $carton) {
                    if (in_array($value, $carton) && !isset($arrCtns[$key])) {
                        $arrCtns[$key] = true;
                        if (!in_array($value, $chipsVan)) {
                            $chipsVan[] = $value;
                        }
                    }
                }
            });

            // Obtener la ficha ganadora
            $chipVan = BingoDraw::where('room', $room)->whereIn('chip', $chipsVan)->value('chip');

            if ($chipVan) {

                BingoDraw::where('chip', $chipVan)->where('room', $room)->update(['win' => 1]);

                $this->insertGameExchangeRate($room, 1); // Registrar Tasas de Cambio

                DB::commit();

                return BingoDraw::select('id', 'chip')->where('chip', $chipVan)->where('room', $room)->first();
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al registar ficha ganadora Sala Bingo (ID)' . $room . ': ' . $e->getMessage());
        }
    }

    public function recorrersorteo($room)
    {
        date_default_timezone_set('America/Caracas');

        $encrypted = BingoRoom::getValueById($room, 'encrypted');

        $result = false;
        do {
            $chip = BingoDraw::select('id', 'chip', 'status', 'win')->where('room', $room)
                ->where('time', '<=', time())->where('status', 1)->first();

            //if ($chip->win == 1) $result = $this->getInfoWinners($room);
            event(new BingoOFF_ProccessDraw($encrypted, $chip, $result));

            $chip->update(['status' => 2]);

            if ($chip->win == 1) BingoRoom::where('id', $room)->update(['status' => 4]);
        } while (BingoRoom::where('id', $room)->where('status', 3)->exists());
    }

    public function getInfoWinners($room)
    {
        date_default_timezone_set('America/Caracas');

        $room = BingoRoom::getRoomByEncrypted($room, ['id', 'date', 'time', 'status', 'encrypted']);

        // Obtener tiempo de salida de la ficha bandera
        $chpVnTime = BingoDraw::where('win', 1)->where('room', $room->id)->value('time');

        if ($room->status == 3 && ($chpVnTime && $chpVnTime <= time())) {
            $room->update(['status' => 4]);
        }

        //Obtener Conteo de Ganadores
        $getWinnersJackpot = BingoPrizeCtns::getWinnersCountByPrizeType($room->id, 1);

        if (!$getWinnersJackpot) $this->handleJackpotWinners($room->id);

        $jackpotWinners = $this->getWinnersJackpot($room->id);

        $serialsWinners = (BingoPrizeCtns::getWinnersCountByPrizeType($room->id, 2)) ?
            $this->getWinnersSerials($room->id) : [];

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

    // Función para obtener detalles de la sala
    private function getRoomDetails($roomID)
    {
        return BingoRoom::select(
            'id',
            'game_mode',
            'players',
            'ctn_price',
            'all_sale',
            'room_commn',
            'seller_commn',
            'jackpot'
        )->where('status', 2)->where('id', $roomID)->first();
    }

    // Función para calcular el monto total de venta
    private function calculateTotalSale($numOfCtns, $room, $coin, $seller, $commerce)
    {
        $exchangeRate = ($coin != 1) ? ExchangeRate::getLastValueByCoin($coin) : 1;
        $totalSaleCtns = $numOfCtns * $exchangeRate;

        // Comisiones del vendedor
        $comSaleSellerCtns = $this->calculateSellerCommission($seller, $totalSaleCtns, $room->id);
        $saleAmount = $totalSaleCtns - $comSaleSellerCtns;

        return [
            'exchangeRate' => $exchangeRate,
            'saleAmount' => $saleAmount,
            'comSaleSellerCtns' => $comSaleSellerCtns
        ];
    }

    // Función para calcular la comisión del vendedor
    private function calculateSellerCommission($seller, $totalSaleCtns, $roomID)
    {
        if ($seller) {
            $commnSeller = SellerRoomLimits::getGameRoomValueBySeller(1, $seller, $roomID, 'commn') ?? Seller::getValueById($seller, 'commn');
            SellerRoomLimits::firstOrCreate(['game' => 1, 'room' => $roomID, 'seller' => $seller], ['commn' => $commnSeller]);
            return $commnSeller ? number_format($totalSaleCtns * ($commnSeller / 100), 2, '.', '') : 0;
        }
        return 0;
    }

    // Función para crear el lote de cartones
    private function createCtnsLot($arrCtn, $roomID, $coin, $saleAmount, $roomUser)
    {
        return BingoCtnsLot::create([
            'customer' => $arrCtn['customer'] ?? null,
            'commerce' => $arrCtn['commerce'] ?? null,
            'seller' => $arrCtn['seller'] ?? null,
            'room_user' => $roomUser,
            'room' => $roomID,
            'coin' => $coin,
            'exchange_rate' => 1,
            'status' => 0,
            'ctns' => $arrCtn['numOfCtns'],
            'sale_amount' => $saleAmount,
        ])->id;
    }

    // Función optimizada para generar y almacenar cartones de bingo usando hash
    private function generateAndStoreCtns($arrCtn, $numOfCtns, $roomID, $lotID)
    {
        try {
            // Iniciales del cartón
            $initials = $arrCtn['initials'];

            // Obtener el último número de cartón creado
            $numLastCtn = BingoCtns::getLastCtnCreated($roomID)?->id ?? 0;

            // Array para insertar registros en una sola operación
            $recordsToInsert = [];

            // Usar un set para evitar duplicados dentro del mismo lote
            $generatedHashes = [];

            for ($x = 1; $x <= $numOfCtns; $x++) {
                // Generar nuevo cartón de bingo
                $nwCtn = $this->generateCompleteBingoCard();

                // Generar hash del cartón
                $cartonHash = hash('sha256', implode('-', $nwCtn));

                // Verificar si el hash ya existe en la base de datos o dentro del mismo lote generado
                if (!BingoCtns::existsCartonHashRoom($roomID, $cartonHash) && !isset($generatedHashes[$cartonHash])) {
                    $numLastCtn++;
                    $serialCtn = "$initials-$numLastCtn";

                    // Preparar el cartón para la inserción
                    $recordsToInsert[] = $this->prepareCtnRecord($arrCtn, $roomID, $lotID, $nwCtn, $serialCtn, $cartonHash);

                    // Añadir el hash a los hashes generados en este ciclo
                    $generatedHashes[$cartonHash] = true;
                }
            }

            // Insertar todos los registros en la base de datos en una sola operación
            if (!empty($recordsToInsert)) {
                BingoCtns::insert($recordsToInsert);
            }
            return true;
        } catch (\Exception $e) {
            dd($e->getMessage());
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
    private function prepareCtnRecord($arrCtn, $roomID, $lotID, $nwCtn, $serialCtn, $cartonHash)
    {
        return [
            'room' => $roomID,
            'customer' => $arrCtn['customer'] ?? null,
            'commerce' => $arrCtn['commerce'] ?? null,
            'seller' => $arrCtn['seller'] ?? null,
            'room_user' => $arrCtn['roomUser'],
            'player_user' => $arrCtn['playerUser'] ?? null,
            'player_link' => $arrCtn['PJLink'] ?? null,
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
    private function registerOrUpdateRoomUser($room, $arrCtn)
    {
        try {
            $playerUser = $arrCtn['playerUser'];
            $playerColumn = $playerUser ? 'player_user' : 'player_link';
            $playerID = $playerUser ?? $arrCtn['PJLink'];

            $defaultValues = [
                'room' => $arrCtn['room'],
                'commerce' => $arrCtn['commerce'] ?? null,
                'seller' => $arrCtn['seller'] ?? null,
                'player_user' => $playerUser ?? null,
                'player_link' => $arrCtn['PJLink'] ?? null,
                'ctns' => $arrCtn['numOfCtns']
            ];

            $roomUser = BingoRoomUser::firstOrCreate(
                ['room' => $room->id, $playerColumn => $playerID],
                $defaultValues
            );
            dd($defaultValues);
            if ($roomUser->wasRecentlyCreated) {
                $room->increment('players');
            }
            // Anexar cantidad de cartones al usuario 
            $roomUser->increment('ctns', $arrCtn['numOfCtns']);
            return $roomUser->id;
        } catch (\Exception $e) {
            Log::error('Error al registar o actualizar los datos del usuario: ' . $e->getMessage());
            return false;
        }
    }

    // Función para actualizar acumulados y comisiones de la sala
    private function updateRoomMetrics($room, $arrCtn, $totalSaleAmount, $exchangeRate, $saleAmount, $comSaleSellerCtns)
    {
        try {
            // Actualización del bote acumulado y otras métricas de la sala
            $convertedJackpot = number_format($saleAmount / $exchangeRate, 3, '.');
            $boatAcum = ($arrCtn['jackpotType'] == 1) ? $convertedJackpot : (($arrCtn['jackpotType'] == 2) ? 0 : null);
            $jackpot = $room->jackpot + $boatAcum;
            $comCommerce = ($jackpot * BingoRoomConfig::getValueByRoom($room->id, 'room_commn') / 100);
            $jackpot -= $comCommerce;

            // Preparar los datos para actualizar la sala
            $updateData = [
                'all_sale' => $room->all_sale + $totalSaleAmount,
                'jackpot' => $jackpot,
                'jackpot_prize' => $jackpot,
                'ctns' => $room->ctns + $arrCtn['numOfCtns'],
                'room_commn' => $room->room_commn + $comCommerce
            ];

            if (isset($arrCtn['seller'])) {
                $updateData['seller_commn'] = $room->seller_commn + $comSaleSellerCtns;
            }

            // Actualiza la sala con los datos preparados
            $room->update($updateData);
            return true;
        } catch (\Exception $e) {
            dd($e->getMessage());
            Log::error('Error al actualizar los datos de la sala: ' . $e->getMessage());
            return false;
        }
    }

    // Función para registrar la transacción de cliente afiliado
    private function registerCustomerTransaction($customer, $roomID, $playerUser, $coin, $totalSaleAmount)
    {
        try {
            $transaction = $this->registerTransaction(
                [1, 1],
                $customer,
                $playerUser,
                BingoRoom::getValueById($roomID, 'code_uuid') ?? 'TEST',
                'BET',
                $coin,
                $totalSaleAmount
            );

            if (!$transaction) {
                DB::rollBack();
                return ['Error' => 'Error al registrar la transacción'];
            }

            $transactionResponse = $this->transferMoney(
                $transaction->customer,
                $transaction->game,
                $transaction->user_id,
                $transaction->bet_id,
                $transaction->coin,
                $transaction->amount,
                $transaction->code,
                $transaction->transaction_type
            );

            // Verificar si la respuesta contiene un error
            if (isset($transactionResponse->Error) && $transactionResponse->Error) {
                Log::error("Error al procesar la transacción hacia el cliente: '{$transaction->id}': {$transactionResponse->Error}");
                return ['Error' => 'Error al registrar la transacción hacia el cliente'];
            }

            if ($transactionResponse) {
                // Actualizar la transacción si es exitosa
                Transactions::where('id', $transaction->id)->update([
                    'status' => 1,
                    'processing_data' => Carbon::now('America/Caracas'),
                    'updated_at' => now(),
                ]);
            }
        } catch (\Exception $e) {
            dd($e->getMessage());
            Log::error('Error al generar transaccion: ' . $e->getMessage());
        }
    }

    public function cartonStore($arrCtn)
    {
        date_default_timezone_set('America/Caracas');

        $roomID = $arrCtn['room'];
        $this->checkRoomClosure($roomID);

        $room = $this->getRoomDetails($roomID);
        if (!$room) return ['Error' => 'Sala no disponible'];

        DB::beginTransaction();

        try {
            // Inicialización de variables
            $customer = $arrCtn['customer'] ?? null;
            $commerce = $arrCtn['commerce'] ?? null;
            $seller = $arrCtn['seller'] ?? null;
            $playerUser = $arrCtn['playerUser'] ?? null;
            $PJLink = $arrCtn['PJLink'] ?? null;
            $gameMode = $room->game_mode;
            $coin = $arrCtn['coin'];
            $numOfCtns = $arrCtn['numOfCtns'];

            $exchangeRate = $customer ? CustomerExchangeRate::getCustomerCoinValue($customer, $coin) : 1;
            $comSaleSellerCtns = 0;
            $totalSaleAmount = $numOfCtns * $room->ctn_price;
            $saleAmount = $totalSaleAmount;

            if ($room->game_mode != 1 && ($commerce || $seller)) {
                // Calcular monto total de venta y comisiones
                $salesData = $this->calculateTotalSale($numOfCtns, $room, $coin, $seller, $commerce);

                $exchangeRate = $salesData['exchangeRate'];
                $saleAmount = $salesData['saleAmount'];
                $comSaleSellerCtns = $salesData['comSaleSellerCtns'];
            }

            // Registrar o actualizar el usuario en la sala
            $roomUser = $this->registerOrUpdateRoomUser($room, $arrCtn);

            // Crear lote de cartones
            $lotID = $this->createCtnsLot($arrCtn, $roomID, $coin, $totalSaleAmount, $roomUser);

            // Anexar atributo al array
            $arrCtn['roomUser'] = $roomUser;

            // Generar y almacenar los cartones de bingo
            $this->generateAndStoreCtns($arrCtn, $numOfCtns, $roomID, $lotID);

            // Si no es modo offline y el creador es un comercio, actualizar la billetera del jugador
            if ($gameMode != 2 && $commerce) {
                // Determinar la columna de saldo en función de la moneda
                $columnBalance = $coin == 1 ? 'usdt' : 'balance';

                // Descontar monto del saldo del usuario
                $walletUpdated = Wallet::where('user', $playerUser)->decrement($columnBalance, $totalSaleAmount);

                // Verificar si se actualizó algún registro
                if ($walletUpdated === 0) {
                    DB::rollBack();
                    return ['Error' => 'Error al descontar saldo del usuario'];
                }
            }

            // Actualizar acumulado y comisiones de la sala
            $this->updateRoomMetrics($room, $arrCtn, $totalSaleAmount, $exchangeRate, $saleAmount, $comSaleSellerCtns);

            // Registrar transacción si hay un cliente afiliado
            if ($customer) {
                $this->registerCustomerTransaction($customer, $roomID, $playerUser, $coin, $totalSaleAmount);
            }

            // Generar un nuevo sorteo si aplica
            if (isset($arrCtn['ctnWin'])) {
                $this->newDraw($room, $PJLink);
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            dd($e->getMessage());
            DB::rollBack();
            Log::error('Error al crear los cartones: ' . $e->getMessage());
            return ['Error' => 'Error al crear los cartones'];
        }
    }

    public function handleCartonStore($data)
    {
        $room = BingoRoom::with('Config')->select('id', 'coin', 'all_for_one', 'status')
            ->where('encrypted', $data['room'])->first();

        if ($room->status > 2) {
            return ['Error' => 'Sala no disponible'];
        }

        $roomID = $room->id;
        $isLogin = $data['isLogin'] ?? false;
        $country = $data['country'] ?? null;
        $customer = $data['customer'] ?? null;
        $commerce = $data['commerce'] ?? null;
        $seller = $data['seller'] ?? null;

        if ($commerce || $seller) {

            if ($isLogin) { // Usuario en Session
                $playerUser = $data['playerUser'];
                $coin = $data['coin'];
            } else { // Offline
                // Si la venta es procesada por un comercio o vendedor
                $PJLink = $data['PJLink'] ?? null;
                $playerPhone = $data['playerPhone'] ?? null;

                if ($seller) $seller = $this->seller(['id', 'commerce']);

                $player = PlayerLink::select('id', 'name', 'lastname', 'link')
                    ->where('game', 1)->where('room', $roomID)
                    ->where(function ($query) use ($playerPhone, $PJLink) {
                        $query->where('phone', $playerPhone)
                            ->orWhere('link', $PJLink);
                    })->first();

                $playerName = $data['playerName'] ?? $player->name;
                $playerLastname = $data['playerLastname'] ?? $player->lastname;
                $arrInitials = $this->initialCharacters([$playerName, $playerLastname]);
                $serialText = $arrInitials[0] . $arrInitials[1];
            }
        } else {
            $playerUser = $data['playerUser'];
            $coin = $customer ? CustomerUser::getValueById($playerUser, 'coin') : null;
        }

        DB::beginTransaction();

        try {

            if (($commerce || $seller) && !$isLogin) {

                $coin = $seller ? Coin::getByIdIdOfCountry($country) : $player->coin;
                $sellerID = $seller->id ?? null;

                if (!$player) {
                    $PJLink = $this->createPlayerLink(1);
                    $playerDocument = null;

                    $arrDataPlayer = [
                        'game' => 1,
                        'room' => $roomID,
                        'commerce' => $commerce,
                        'seller' => $sellerID,
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
                    if (!$PJLink) $PJLink = $player->link;
                    $player = $player->id ?? null;
                }
            }

            $dataCtns = [
                'room' => $roomID,
                'numOfCtns' => $data['numOfCtns'],
                'jackpotType' => $room->Config->jackpot_type,
                'coin' => $coin,
                'initials' => $serialText ?? 'RKE'
            ];

            if ($customer) {
                $dataCtns['customer'] = $customer;
                $dataCtns['playerUser'] = $playerUser ?? null;
            }

            if ($commerce || $seller) {
                if ($commerce) {
                    $dataCtns['commerce'] = $commerce ?? null;
                    if ($isLogin) $dataCtns['playerUser'] = $playerUser;
                }

                if ($seller) {
                    $dataCtns['seller'] = $sellerID ?? null;
                }

                if (!$isLogin) $dataCtns['PJLink'] = $player;
            }

            $responseCtns = $this->cartonStore($dataCtns);

            if (!isset($responseCtns['Error'])) {
                DB::commit();
                return $seller ? $this->getPlayerGameLink($commerce, $PJLink, $room->all_for_one) : true;
            } else {
                DB::rollBack();
                return $responseCtns;
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear la información del jugador: ' . $e->getMessage());
            dd($e->getMessage());
            return false;
        }
    }

    public function convertPrizeValue($roomID, $coin, $type = 'string')
    {
        $room = BingoRoom::getRoomById($roomID, ['customer', 'status']);

        $jackpotPrize = BingoRoom::getValueById($roomID, 'jackpot_prize');

        if (!$room->customer) {
            $exchangeRate = ($room->status == 2)
                ? ExchangeRate::getLastValueByCoin($coin)
                : GameExchangeRate::getGameExchangeRateValue(1, $roomID, $coin);

            if (!$exchangeRate) $this->insertGameExchangeRate($roomID, 1);
        }

        $jackpot = $jackpotPrize * ($exchangeRate ?? 1);

        if ($type == 'string') {
            // Formatear como cadena y eliminar ceros y comas finales
            return $jackpot == 0 ? '0.00' : rtrim(number_format($jackpot, 2, ',', '.'), '0,');
        } else {
            // Devolver como número de punto flotante
            return (float) $jackpot;
        }
    }

    private function registerPrizeCtn($roomID, $playerCtn, $prizeType, $USDValue)
    {
        date_default_timezone_set('America/Caracas');

        $recordsToInsert = [];
        $playersToUpdate = [];

        foreach ($playerCtn as $ctn) {

            //Determinar la columna según el modo de juego
            $playerColumn = $ctn->player_user ? 'player_user' : 'player_link';

            $playerColumnVal = $ctn->player_user ?? $ctn->player_link;

            if (!BingoPrizeCtns::existsPrizeCtn($roomID, 1, $playerColumn, $playerColumnVal)) {

                // Verificar y obtener el tipo de cambio
                $exchangeRate = GameExchangeRate::getGameExchangeRateValue(1, $roomID, $playerCtn[0]->coin) ?? 1;

                if (!$exchangeRate) {
                    $this->insertGameExchangeRate($roomID, 1);
                    $exchangeRate = GameExchangeRate::getGameExchangeRateValue(1, $roomID, $playerCtn[0]->coin);
                }

                $prizeWn = number_format($USDValue * $exchangeRate, 3, '.', '');
                $timestamp = now();

                $record = [
                    'room' => $roomID,
                    'ctn' => $ctn->id,
                    'lot' => $ctn->lot,
                    'prize_type' => $prizeType,
                    'coin' => $ctn->coin,
                    'amount' => $prizeWn,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp
                ];

                // Si el room tiene un customer 
                if ($ctn->customer) {
                    $record['customer'] = $ctn->customer;
                } else {
                    $record['commerce'] = $ctn->commerce;
                    $record['seller'] = $ctn->seller;
                    $record['player_link'] = $ctn->player_link;
                }
                $record['player_user'] = $ctn->player_user;

                // Añadimos el registro al array de inserciones
                $recordsToInsert[] = $record;

                // Determinar el campo de actualización basado en el tipo de usuario
                $playersToUpdate[] = BingoRoomUser::getValueByColumn($roomID, $playerColumn, $playerColumnVal, 'id');
            }
        }

        DB::beginTransaction();

        try {

            // Actualizar el estado del jugador
            if ($playersToUpdate) {
                BingoRoomUser::where('room', $roomID)->whereIn('id', $playersToUpdate)
                    ->where('winner', 0)->update(['winner' => 1]);
            }

            // Insertar los registros de una sola vez
            if ($recordsToInsert) {

                BingoPrizeCtns::insert($recordsToInsert);

                $customer = BingoRoom::getValueById($roomID, 'customer');

                if ($customer) {

                    foreach ($playerCtn as $ctn) {

                        $roomUUID = BingoRoom::getValueById($roomID, 'code_uuid');
                        // Registrar la transaccion en PlayRKE
                        $transaction = $this->registerTransaction(
                            [1, 1],
                            $customer,
                            $ctn->player_user,
                            $roomUUID,
                            'WIN',
                            $ctn->coin,
                            $prizeWn
                        );

                        if (!$transaction) {
                            DB::rollBack();
                            return ['Error' => 'Error al registrar la transacción'];
                        }
                    }
                }
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear ganador o actualizar estado: ' . $e->getMessage());
            dd($e->getMessage());
            return false;
        }
    }

    private function getCoinByPlayer($creator, $column, $value)
    {
        dd($column, $value);
        switch ($column) {
            case 'player_user':
                //$coin = Bing
                break;
            case 'player_link':
                $country = PlayerLink::getValueById($value, 'country');
                $coin = Coin::getValueByCountry($country, 'id');
                break;
            default:
                # code...
                break;
        }

        return $coin;
    }

    public function getWinnersData($roomID, $prizeType)
    {
        $room = BingoRoom::select('customer', 'serial_prize')
            ->where('id', $roomID)->where('status', 4)->first();

        if (!$room) return ['Error' => 'Sala no disponible'];

        // Definir valor $column
        $column = $room->customer ? 'player_user' : 'player_link';

        $relation = match ($column) {
            'player_user' => $room->customer ? 'CustomerUser' : 'User',
            default => 'PlayerLink',
        };

        // Obtener todos los ganadores por seriales 
        $winners = BingoRoomUser::with($relation, 'WinCtns')
            ->whereHas('WinCtns', function ($query) use ($prizeType) {
                $query->where('prize_type', $prizeType);
            })->where('room', $roomID)->where('winner', '>', 0)->get();

        $numWinners = $winners->count(); // Contar todos los ganadores

        // Verificar si el jugador específico está entre los ganadores
        $winPlayer = $winners->firstWhere($column, $columnVal);

        // Obtener el país del jugador y la moneda correspondiente
        $coin = $this->getCoinByPlayer($column, $columnVal);

        return [
            'room' => $room,
            'winners' => $winners,
            'numWinners' => $numWinners,
            'winPlayer' => $winPlayer,
            'coin' => $coin
        ];
    }

    public function handleJackpotWinners($roomID)
    {
        // Obtener la información de la sala
        $room = BingoRoom::with('Config')
            ->select('id', 'customer', 'jackpot', 'jackpot_prize', 'serial_prize')
            ->where('id', $roomID)->firstOrFail();

        // Verificar si ya hay ganadores del premio mayor
        $prizeCtns = BingoPrizeCtns::getWinnersCountByPrizeType($roomID, 1);
        $winners = BingoRoomUser::getWinnersCountByRoom($roomID);

        if ($prizeCtns && $winners) return false; // Ya existen cartones premiados (jackpot)

        // Obtener el último chip sorteado
        $chpVn = BingoDraw::select('id', 'chip')->where('win', 1)->where('room', $roomID)->first();

        // Obtener todos los chips sorteados hasta el último
        $chipsDraw = BingoDraw::where('room', $roomID)->where('id', '<=', $chpVn->id)->pluck('chip')->toArray();

        // Obtener todos los cartones únicos de la sala
        $ctnsPlayer = BingoCtns::where('room', $roomID)->distinct('serial')->get();

        // Inicializar array de jugadores únicos
        $uniquePlayers = ['playerUser' => [], 'playerLink' => []];

        // Filtrar cartones ganadores
        $filteredCtns = $ctnsPlayer->filter(function ($ctn) use ($chpVn, $chipsDraw, &$uniquePlayers) {
            // Obtener todos los valores del cartón
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
            ])->values()->toArray();

            // Verificar si el chip está en el cartón y si todos los valores están en $chipsDraw
            if (in_array($chpVn->chip, $ctnFull) && empty(array_diff($ctnFull, $chipsDraw))) {
                // Identificar al jugador
                $playerId = $ctn->player_user ?: $ctn->player_link;
                $playerType = $ctn->player_user ? 'playerUser' : 'playerLink';

                // Verificar si el jugador es único
                if (!in_array($playerId, $uniquePlayers[$playerType])) {
                    $uniquePlayers[$playerType][] = $playerId; // Añadir a la lista de jugadores únicos
                    return true; // Cartón ganador
                }
            }

            return false; // Cartón no ganador
        });

        // Obtener IDs de los cartones ganadores
        $arrCtnWin = $filteredCtns->pluck('id');

        // Obtener información de los cartones ganadores
        $playerCtn = BingoCtns::where('room', $roomID)->whereIn('id', $arrCtnWin)
            ->get(['id', 'customer', 'commerce', 'seller', 'player_user', 'player_link', 'coin', 'lot']);

        // Obtener la cantidad de ganadores
        $winnersCount = count(array_merge($uniquePlayers['playerUser'], $uniquePlayers['playerLink']));

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
            // Cambiar Status Premios Pagados
            BingoRoom::where('id', $roomID)->update(['paid_win' => 1]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            // Registrar el error
            Log::error('Error en handleJackpotWinners: ' . $e->getMessage());
            dd($e->getMessage());
            // Devolver un mensaje de error
            return response()->json(['error' => 'Hubo un error al procesar la solicitud.'], 500);
        }

        // Validar Transacciones
        if ($room->customer) $this->validateTransaction($room->customer);
    }

    public function getWinnersJackpot($roomID)
    {
        $data = $this->getWinnersData($roomID, 1);

        if (isset($data['Error'])) return $data['Error'];

        // Declarar varaiables
        $winners = $data['winners'];
        $numWinners = $data['numWinners'];
        $winPlayer = $data['winPlayer'];
        $coin = $data['coin'];

        if ($numWinners == 0) $this->handleJackpotWinners($roomID);

        // Calcular la ganancia por jugador y formatearla
        $boat = $this->convertPrizeValue($roomID, $coin, 'float');
        $formattedNumber = number_format($boat / $numWinners, 3, ',', '.');
        $gain = rtrim(rtrim($formattedNumber, '0'), ',');

        // Preparar la información del jugador
        $infoPlayer = ['size' => 0, 'name' => [], 'foto' => [], 'gain' => [], 'ctns' => []];

        // Añadir información del jugador específico es un ganador
        if ($winPlayer) {
            $infoPlayer['size'] = 1;
            $playerLastName = $this->initialCharacters($winPlayer->$relation->lastname);
            $infoPlayer['name'][] = "{$winPlayer->$relation->name} {$playerLastName}";
            $infoPlayer['gain'][] = $gain;
            $infoPlayer['ctns'][] = $winPlayer->$relation->WinCtns[0]->ctn;
        }

        // Añadir información de todos los demás ganadores
        foreach ($winners as $win) {
            // Evitar añadir el jugador específico de nuevo si ya está en la lista de ganadores
            if (!$winPlayer || $win->player_link != $winPlayer->player_link) {
                $infoPlayer['size']++;
                $playerLastName = $this->initialCharacters($win->$relation->lastname);
                $infoPlayer['name'][] = "{$win->$relation->name} {$playerLastName}";
                $infoPlayer['gain'][] = $gain;
                $infoPlayer['ctns'][] = $win->$relation->WinCtns[0]->ctn;
            }
        }

        return $infoPlayer;
    }

    public function handleSerialWinners($roomID) // Obtener Seriales Ganadores
    {
        $getWinners = BingoPrizeCtns::getWinnersCountByPrizeType($roomID, 2);

        if ($getWinners) return false;

        // Obtener ultima ficha en salir (ganadora) 
        $chpVn = BingoDraw::where('room', $roomID)->where('win', 1)->value('id');

        $chipsDraw = BingoDraw::where('room', $roomID)->where('id', '<', $chpVn)->pluck('chip')->toArray();

        // Obtener los IDs del los cartones de jugadores ganadores del Jackpot
        $ctnsID = BingoPrizeCtns::getWinnersIDsByPrizeType($roomID, 1, 'ctn');

        // Filtrar los  cartones excluyendo los cartones de los ganadores del jackpot
        $ctnsPlayer = BingoCtns::where('room', $roomID)->whereNotIn('id', $ctnsID)->distinct('serial')->get();

        // Obtener Cantidad de Seriales permitidos para la sala
        $countSerials = BingoRoomConfig::getValueByRoom($roomID, 'num_serials');

        // Filtrar los registros en $ctnsPlayer que contienen $chpVn->chip y tienen 23 aciertos
        $filteredCtns = $ctnsPlayer->filter(function ($ctn) use ($chipsDraw, $countSerials, &$countCtns, &$uniquePlayers) {

            // Definir las variables si no existen aún
            if (!isset($countCtns)) {
                $countCtns = 0; // Contador para el número de cartones
            }

            if (!isset($uniquePlayers)) {
                $uniquePlayers = []; // Almacena los jugadores únicos
            }

            // Detener el filtrado cuando se alcanza el límite de seriales
            if ($countCtns >= $countSerials) {
                return false;
            }

            // Obtener los números del cartón
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

            // Obtener los aciertos comparando los números del cartón con $chipsDraw
            $aciertos = array_intersect($ctnFull, $chipsDraw);

            // Incrementar el contador de cartones si hay 23 aciertos y el jugador es único
            if (count($aciertos) === 23) {
                // Identificar al jugador
                $playerId = $ctn->player_user ?: $ctn->player_link;
                $playerType = $ctn->player_user ? 'playerUser' : 'playerLink';

                // Verificar si el jugador es único
                if (!in_array($playerId, $uniquePlayers[$playerType])) {
                    $uniquePlayers[$playerType][] = $playerId; // Añadir a la lista de jugadores únicos
                    $countCtns++; // Incrementar el contador de cartones
                    return true; // Cartón ganador
                }
            }

            return false; // Si no cumple con las condiciones, no se incluye
        });

        if (!count($filteredCtns)) return false; // Si no hay ganadores con 23 aciertos

        $arrSerialsPlayers = $filteredCtns->pluck('serial')->toArray();

        $ctnSerials = BingoCtns::whereIn('serial', $arrSerialsPlayers)
            ->where('room', $roomID)->get(['id', 'commerce', 'seller', 'player_user', 'player_link', 'coin', 'lot']);

        $USDPrize = BingoRoom::getSerialPrize($roomID) / count($arrSerialsPlayers);

        try {
            $registerPrizeCtn = $this->registerPrizeCtn($ctnSerials, 2, $USDPrize, $roomID);
            if (!$registerPrizeCtn) DB::rollBack();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear ganador o actualizar estado: ' . $e->getMessage());
            return response()->json(['error' => 'Hubo un error al procesar la solicitud.'], 500);
        }
    }

    public function getWinnersSerials($roomID) // Obtener Seriales Ganadores
    {
        $data = $this->getWinnersData($roomID, 2);

        if (isset($data['Error'])) return $data['Error'];

        // Declarar varaiables
        $serialPrize = $data['room']->serial_prize;
        $winners = $data['winners'];
        $numWinners = $data['numWinners'];
        $winPlayer = $data['winPlayer'];
        $coin = $data['coin'];

        // Calcular valor del monto a repartir en Seriales
        $boat = $serialPrize * GameExchangeRate::getGameExchangeRateValue(1, $roomID, $coin);

        // Calcular la ganancia por jugador y formatearla
        $formattedNumber = number_format($boat / $numWinners, 3, ',', '.');
        $gain = rtrim(rtrim($formattedNumber, '0'), ',');

        // Preparar la información del jugador
        $infoPlayer = ['size' => 0, 'name' => [], 'foto' => [], 'gain' => [], 'ctns' => []];

        // Añadir información del jugador específico es un ganador
        if ($winPlayer) {
            $infoPlayer['size'] = 1;
            $infoPlayer['name'][] = BingoCtns::getCtnSerialByPlayerInRoom($roomID, $winPlayer->$relation->id);
            $infoPlayer['gain'][] = $gain;
            $infoPlayer['ctns'][] = $winPlayer->$relation->WinCtns[0]->ctn;
        }

        // Añadir información de todos los demás ganadores
        foreach ($winners as $win) {
            // Evitar añadir el jugador específico de nuevo si ya está en la lista de ganadores
            if (!$winPlayer || $win->player_link != $winPlayer->player_link) {
                $infoPlayer['size']++;
                $infoPlayer['name'][] = BingoCtns::getCtnSerialByPlayerInRoom($roomID, $win->player_link);
                $infoPlayer['gain'][] = $gain;
                $infoPlayer['ctns'][] = $win->$relation->WinCtns[0]->ctn;
            }
        }

        return $infoPlayer;
    }

    // Obtener Informacion de un carton especifico
    public function getCtnWin($ctn)
    {
        // Obtener el cartón junto con la relación PlayerLink
        $ctn = BingoCtns::with('PlayerLink')->find($ctn);

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

        // Obtener el nombre del jugador si existe
        $fullName = '';
        if ($ctn->PlayerLink) {
            $fullName = $ctn->PlayerLink->name . ' ' . $this->initialCharacters($ctn->PlayerLink->lastname);
        } elseif ($ctn->commerce) {
            $fullName = User::getValueById($ctn->player_user, 'username');
        }

        // Retornar los datos del cartón
        return [
            'ctn' => $allCtn,
            'playerName' => $fullName,
            'serialCtn' => $ctn->serial
        ];
    }

    public function checkRoomClosure($room = '')
    {
        date_default_timezone_set('America/Caracas');

        $query = BingoRoom::whereIn('status', [2, 3]);

        if ($room) $query = $query->where('id', $room);

        $rooms = $query->get(['id', 'unix_date']);

        if (count($rooms)) {

            foreach ($rooms as $room) {

                $roomID = $room->id;

                if (time() < $room->unix_date) continue;

                $existsCtns = BingoCtns::getCountCtnByRoom($roomID);
                $existsPlayers = BingoRoomUser::getPlayerCountByRoom($roomID);

                if ($existsCtns && $existsPlayers) {
                    $winChip = BingoDraw::getWinningChip($roomID) ?? $this->newDraw($roomID);
                    $status = ($winChip->time <= time()) ? 4 : 3;
                } else {
                    $status = 1; //N/D Borrador
                }

                BingoRoom::where('id', $roomID)->update(['status' => $status]);
            }
        }

        $this->hideRoom();
    }

    public function hideRoom() // Validar Salas Finalizadas
    {
        date_default_timezone_set('America/Caracas');

        $rooms = BingoRoom::where('status', 4)->get(['id', 'customer', 'date', 'time']);

        if (!count($rooms)) return true;

        foreach ($rooms as $room) {

            $getWinnersJackpot = BingoPrizeCtns::getWinnersCountByPrizeType($room->id, 1);

            if ($getWinnersJackpot == 0) {
                $this->handleJackpotWinners($room->id);
            }

            $dataTime = strtotime("{$room->date} {$room->time}");

            // Sumar 2 semanas (14 días * 24 horas * 60 minutos * 60 segundos)
            $dataTime2Week = $dataTime + (2 * 7 * 24 * 60 * 60);

            if ($dataTime2Week <= time()) { // Status (6) Sala Oculta
                BingoRoom::where('id', $room->id)->update(['status' => 6]);
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
