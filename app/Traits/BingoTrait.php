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
use App\Models\CoinGameRoom;
use App\Models\Commerce;
use App\Models\CommerceExchangeRate;
use App\Models\CustomerExchangeRate;
use App\Models\CustomerUser;
use App\Models\GameExchangeRate;
use App\Models\Games;
use App\Models\PlayerLink;
use App\Models\Seller;
use App\Models\Transactions;
use App\Models\User;
use App\Models\Wallet;
use Carbon\Carbon;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait BingoTrait
{
    use UserVal, EncryptRKE, GameTrait, RouteRKE, PlayerLinkTrait, SeamlessWallet;

    public function newDraw($room, $roomUser = '')
    {
        $sort = BingoDraw::where('room', $room)->count() === 75;

        if (!$sort) {
            $this->encryptDraw('bingo_draw_code', $room);

            $roomInfo = BingoRoom::getRoomById($room, ['date', 'time']);
            $dataTime = strtotime("{$roomInfo->date} {$roomInfo->time}");

            $symbols = BingoDrawCode::where('room', $room)->pluck('sym', 'num');
            $existingChips = BingoDraw::where('room', $room)->pluck('chip');

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

            DB::beginTransaction();

            try {
                BingoDraw::insert($inserts); // Inserción masiva
                DB::commit();
                return $this->chipWin($room);
            } catch (\Throwable $th) {
                DB::rollBack();
                throw $th;
            }
        } else if ($sort && $roomUser && $this->user(['user_type']) == 2) {

            $ctn = BingoCtns::where('room', $room)->where('id', $roomUser)->inRandomOrder()->first();

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
        if (!$winChip || $roomUser) return $this->chipWin($room);

        return false;
    }

    public function chipWin($room)
    {
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

            BingoDraw::where('chip', $chipVan)->where('room', $room)->update(['win' => 1]);

            $this->insertGameExchangeRate($room, 'BINGO'); // Registrar Tasas de Cambio

            DB::commit();

            return BingoDraw::select('id', 'chip', 'time')->where('chip', $chipVan)->where('room', $room)->first();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al registar ficha ganadora Sala Bingo (ID)' . $room . ': ' . $e->getMessage());
        }
    }

    public function recorrersorteo($room)
    {
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

    public function getInfoWinners($room, $roomUser)
    {
        $room = BingoRoom::getRoomByEncrypted($room, ['id', 'date', 'time', 'status', 'encrypted']);

        // Obtener tiempo de salida de la ficha bandera
        $chpVnTime = BingoDraw::where('win', 1)->where('room', $room->id)->value('time');

        if ($room->status == 3 && ($chpVnTime && $chpVnTime <= time())) {
            $room->update(['status' => 4]);
        }

        //Obtener Conteo de Ganadores
        $getWinnersJackpot = BingoPrizeCtns::getWinnersCountByPrizeType($room->id, 1);

        if (!$getWinnersJackpot) $this->handleJackpotWinners($room->id);

        $jackpotWinners = $this->getWinnersJackpot($room->id, $roomUser);

        $serialsWinners = (BingoPrizeCtns::getWinnersCountByPrizeType($room->id, 2)) ?
            $this->getWinnersSerials($room->id, $roomUser) : [];

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
    private function calculateTotalSale($saleAmount, $room, $coin, $seller, $commerce)
    {
        $exchangeRate = ($coin != 1) ? CommerceExchangeRate::getCommerceCoinValue($commerce, $coin) : 1;
        $totalSaleCtns = $saleAmount * $exchangeRate;

        // Comisiones del vendedor
        $saleCommn = $this->calculateSellerCommission($seller, $totalSaleCtns, $room->id);
        $sellerSaleCommn = number_format(($totalSaleCtns - $saleCommn) / $exchangeRate, 3, '.');;

        return ['exchangeRate' => $exchangeRate, 'sellerSaleCommn' => $sellerSaleCommn];
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
    private function createCtnsLot($arrCtn, $exchangeRate, $saleAmount)
    {
        $currentTime = now();

        return BingoCtnsLot::insertGetId([
            'customer' => $arrCtn['customer'] ?? null,
            'commerce' => $arrCtn['commerce'] ?? null,
            'seller' => $arrCtn['seller'] ?? null,
            'room_user' => $arrCtn['roomUser'],
            'player_user' => $arrCtn['playerUser'] ?? null,
            'player_link' => $arrCtn['playerLink'] ?? null,
            'room' => $arrCtn['room'],
            'coin' => $arrCtn['coin'],
            'exchange_rate' => $exchangeRate,
            'status' => 0,
            'ctns' => $arrCtn['numOfCtns'],
            'sale_amount' => $saleAmount,
            'created_at' => $currentTime,
            'updated_at' => $currentTime,
        ]);
    }

    // Función optimizada para generar y almacenar cartones de bingo usando hash
    private function generateAndStoreCtns($arrCtn, $lotID)
    {
        try {
            // Iniciales del cartón
            $initials = $arrCtn['initials'];

            // Obtener el último número de cartón creado
            $numLastCtn = BingoCtns::getLastCtnCreated($arrCtn['room'])?->id ?? 0;

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
                    $serialCtn = "$initials-$numLastCtn";

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
        } catch (\Exception $e) {
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
            'customer' => $arrCtn['customer'] ?? null,
            'commerce' => $arrCtn['commerce'] ?? null,
            'seller' => $arrCtn['seller'] ?? null,
            'room_user' => $arrCtn['roomUser'],
            'player_user' => $arrCtn['playerUser'] ?? null,
            'player_link' => $arrCtn['playerLink'] ?? null,
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
            $playerUser = $arrCtn['playerUser'] ?? null;

            $playerColumn = $playerUser ? 'player_user' : 'player_link';
            $playerID = $playerUser ?? $arrCtn['playerLink'];

            $defaultValues = [
                'room' => $arrCtn['room'],
                'commerce' => $arrCtn['commerce'] ?? null,
                'seller' => $arrCtn['seller'] ?? null,
                'player_user' => $playerUser,
                'player_link' => $arrCtn['playerLink'] ?? null,
                'ctns' => $arrCtn['numOfCtns']
            ];

            $roomUser = BingoRoomUser::firstOrCreate(
                ['room' => $room->id, $playerColumn => $playerID],
                $defaultValues
            );

            if ($roomUser->wasRecentlyCreated) {
                $room->increment('players');
            } else {
                // Anexar cantidad de cartones al usuario 
                $roomUser->increment('ctns', $arrCtn['numOfCtns']);
            }

            return $roomUser->id;
        } catch (\Exception $e) {
            Log::error('Error al registar o actualizar los datos del usuario: ' . $e->getMessage());
            return false;
        }
    }

    // Función para actualizar acumulados y comisiones de la sala
    private function updateRoomMetrics($room, $arrCtn, $saleAmount, $sellerSaleCommn)
    {
        try {
            // Actualización del bote acumulado y otras métricas de la sala

            $boatAcum = ($arrCtn['jackpotType'] == 1) ? $saleAmount : (($arrCtn['jackpotType'] == 2) ? 0 : null);

            $jackpot = $room->jackpot + $boatAcum;

            // Obtener comision del creador/casa (comision por sala)
            $creatorCommn = ($jackpot * BingoRoomConfig::getValueByRoom($room->id, 'room_commn') / 100);
            if ($creatorCommn) $jackpot -= $creatorCommn;

            // Preparar los datos para actualizar la sala
            $updateData = [
                'all_sale' => $room->all_sale + $saleAmount,
                'jackpot' => $jackpot,
                'jackpot_prize' => $jackpot,
                'ctns' => $room->ctns + $arrCtn['numOfCtns'],
                'room_commn' => $room->room_commn + $creatorCommn
            ];

            if (isset($arrCtn['seller'])) {
                $updateData['seller_commn'] = $room->seller_commn + $sellerSaleCommn;
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

    public function cartonStore($arrCtn)
    {
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
            $coin = $arrCtn['coin'];
            $numOfCtns = $arrCtn['numOfCtns'];
            $gameMode = $room->game_mode;
            $saleAmount = $numOfCtns * $room->ctn_price;
            $sellerSaleCommn = null;

            if ($customer) $exchangeRate = CustomerExchangeRate::getValueById($coin) ?? 1;

            // Registrar o actualizar el usuario en la sala
            $roomUser = $this->registerOrUpdateRoomUser($room, $arrCtn);

            if (!$roomUser) {
                DB::rollBack();
                return ['Error' => 'Registrar o actualizar el usuario en la sala'];
            }

            // Anexar atributo al array
            $arrCtn['roomUser'] = $roomUser;

            if ($gameMode != 1 && ($commerce || $seller)) {
                // Calcular monto total de venta y comisiones si es un vendedor
                $salesData = $this->calculateTotalSale($saleAmount, $room, $coin, $seller, $commerce);

                $exchangeRate = $salesData['exchangeRate'];
                $sellerSaleCommn = $salesData['sellerSaleCommn'];
            }

            // Definir monto total de venta
            $totalSaleAmount = $exchangeRate * $saleAmount;

            // Crear lote de cartones
            $lotID = $this->createCtnsLot($arrCtn, $exchangeRate, $totalSaleAmount);

            // Generar y almacenar los cartones de bingo
            $generateAndStoreCtns = $this->generateAndStoreCtns($arrCtn, $lotID);

            if (!$generateAndStoreCtns) {
                DB::rollBack();
                return ['Error' => 'Error al generar cartones'];
            }

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
            $this->updateRoomMetrics($room, $arrCtn, $saleAmount, $sellerSaleCommn);

            // Definir variables dobre el juego
            $game = ['name' => 'BINGO', 'game_mode' => $gameMode];

            //Registrar nueva moneda en juego de ser necesario
            if (!CoinGameRoom::existsCoinGameRoom($coin, $roomID, 'BINGO', $gameMode)) {
                CoinGameRoom::createNewCoinInRoom($game, $roomID, $coin);
            }

            // Si la sala es de un cliente
            if ($customer) {
                // Registrar transacción (se consulta nuvamente el saldo del juagdor)
                $roomID = BingoRoom::getValueById($roomID, 'code_uuid') ?? $this->getCodeUUID('BingoRoom');

                $transactionBet = $this->handleTransactionByBET(
                    $game,
                    $customer,
                    $roomID,
                    $playerUser,
                    $coin,
                    $totalSaleAmount
                );

                if (isset($transactionBet['Error'])) {
                    DB::rollBack();
                    return ['Error' => 'Error al procesar la transferencia: ' . $transactionBet['Error']];
                }
            }

            // Generar un nuevo sorteo si aplica
            if (isset($arrCtn['ctnWin'])) {
                $this->newDraw($roomID, $roomUser);
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear los cartones: ' . $e->getMessage());
            return ['Error' => 'Error al crear los cartones'];
        }
    }

    public function handleCartonStore($data)
    {
        $room = BingoRoom::with(['Config:room,jackpot_type'])
            ->select('id', 'room_type', 'status')
            ->where('encrypted', $data['room'])->first();

        if ($room->status > 2) {
            return ['Error' => 'Sala no disponible'];
        }

        $roomID = $room->id;

        $isLogin = $data['isLogin'] ?? false;
        $data['room'] = $roomID;
        $coin = $data['coin'];
        $commerce = $data['commerce'] ?? null;
        $seller = $data['seller'] ?? null;

        if ($commerce || $seller) {

            if (!$isLogin) { // Usuario Fuera de Sesion
                // Si la venta es procesada por un comercio o vendedor
                $playerLink = $data['playerLink'] ?? null;
                $playerPhone = $data['playerPhone'] ?? null;

                $player = PlayerLink::select('id', 'name', 'lastname', 'link')
                    ->where('game', 1)->where('room', $roomID)
                    ->where(function ($query) use ($playerPhone, $playerLink) {
                        $query->where('phone', $playerPhone)
                            ->orWhere('id', $playerLink);
                    })->first();

                $playerName = $data['playerName'] ?? $player->name;
                $playerLastname = $data['playerLastname'] ?? $player->lastname;
                $arrInitials = $this->initialCharacters([$playerName, $playerLastname]);
                $serialText = $arrInitials[0] . $arrInitials[1];
            }
        }

        DB::beginTransaction();

        try {

            if (($commerce || $seller) && !$isLogin) {

                if (!$player) {

                    $playerLink = $this->createPlayerLink(1);

                    $arrDataPlayer = [
                        'game' => 1,
                        'room' => $roomID,
                        'commerce' => $commerce,
                        'seller' => $seller,
                        'country' => $data['country'],
                        'coin' => $coin,
                        'link' => $playerLink,
                        'name' => $playerName,
                        'lastname' => $playerLastname,
                        'identification' => null,
                        'phone' => $playerPhone
                    ];

                    $player = $this->createPlayerLinkInfo($arrDataPlayer);

                    if (!$player) {
                        DB::rollBack();
                        Log::error('Hubo un error al crear la información del jugador');
                        return false;
                    }
                } else {
                    $playerLink = $player->link ?? null;
                    $player = $player->id ?? null;
                }
            }

            $data['jackpotType'] = $room->Config->jackpot_type;
            $data['initials'] = $serialText ?? 'RKE';

            if (($commerce || $seller)) {
                $data['playerLink'] = $player;
                // Eliminar Valores innecesarios
                unset($data['country'], $data['playerPhone'], $data['playerName'], $data['playerLastname']);
            }

            $responseCtns = $this->cartonStore($data);

            if (!isset($responseCtns['Error'])) {
                DB::commit();
                return $seller ? $this->getGameLinkByPlayer($commerce, $playerLink, $room->room_type) : true;
            } else {
                DB::rollBack();
                return $responseCtns;
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear la información del jugador: ' . $e->getMessage());
            return false;
        }
    }

    public function convertPrizeValue($roomID, $coin, $type = 'string')
    {
        $room = BingoRoom::getRoomById($roomID, ['commerce', 'customer', 'jackpot_prize']);

        $creatorType = $room->commerce ? 'commerce' : 'customer';
        $creatorID = $room->commerce ? $room->commerce : $room->customer;

        $creator = ['type' => $creatorType, 'id' => $creatorID];

        $data = ['coin' => $coin, 'value' => $room->jackpot_prize, 'creator' => $creator];

        return $this->convertValueByGame('BINGO', $roomID, $data, $type);
    }

    private function registerPrizeCtn($roomID, $cartons, $prizeType, $USDValue)
    {
        $recordsToInsert = [];
        $playersToUpdate = [];

        foreach ($cartons as $ctn) {

            //Determinar la columna según el modo de juego
            $playerColumn = $ctn->player_user ? 'player_user' : 'player_link';

            $playerColumnVal = $ctn->player_user ?? $ctn->player_link;

            if (!BingoPrizeCtns::existsPrizeCtn($roomID, $prizeType, $playerColumn, $playerColumnVal)) {

                // Verificar y obtener el tipo de cambio
                $exchangeRate = GameExchangeRate::getGameExchangeRateValue('BINGO', $roomID, $ctn->coin) ?? 1;

                $prizeWn = number_format($USDValue * $exchangeRate, 3, '.', '');
                $timestamp = now();

                $record = [
                    'room' => $roomID,
                    'room_user' => $ctn->room_user,
                    'customer' => $ctn->customer,
                    'commerce' => $ctn->commerce,
                    'seller' => $ctn->seller,
                    'player_link' => $ctn->player_link,
                    'ctn' => $ctn->id,
                    'lot' => $ctn->lot,
                    'prize_type' => $prizeType,
                    'coin' => $ctn->coin,
                    'amount' => $prizeWn,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp
                ];

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

                    foreach ($cartons as $ctn) {

                        $roomUUID = BingoRoom::getValueById($roomID, 'code_uuid');
                        // Registrar la transaccion en PlayRKE
                        $transaction = $this->registerTransaction(
                            ['name' => 'BINGO', 'game_mode' => 1],
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

    private function getCoinByPlayer($creator, $playerID)
    {
        switch ($creator) {
            case 'customer':
                $coin = CustomerUser::getValueById($playerID, 'coin');
                break;
            case 'commerce':
                $coin = Wallet::getValue($playerID, 'coin') ?? PlayerLink::getValueById($playerID, 'coin');
                break;
            default:
                break;
        }

        return $coin;
    }

    public function getWinnersData($roomID, $prizeType, $playerID = '')
    {
        $room = BingoRoom::select('serial_prize')->where('id', $roomID)->first();

        if (!$room) return ['Error' => 'Sala no disponible'];

        // Obtener todos los ganadores por tipo de premio 
        $winners = BingoRoomUser::with(['PrizeCtns:room_user,prize_type,ctn'])
            ->whereHas('PrizeCtns', function ($query) use ($prizeType) {
                $query->where('prize_type', $prizeType);
            })->where('room', $roomID)->where('winner', '>', 0)->get();

        if ($playerID) {
            // Verificar si el jugador específico está entre los ganadores
            $winPlayer = $winners->firstWhere('id', $playerID);

            // Definir creador de la sala
            $creator = $winPlayer->customer ? 'customer' : 'commerce';

            // Obtener la moneda del jugador
            $coin = $this->getCoinByPlayer($creator, $playerID);
        }

        return [
            'serialPrize' => $room->serial_prize,
            'winners' => $winners,
            'winPlayer' => $winPlayer ?? false,
            'coin' => $coin ?? 1
        ];
    }

    public function getArrayWinnersData($winPlayer, $winners, $jackpot, $prizeType = 'jackpot')
    {
        // Calcular la ganancia de acuerdo a la cantidad de ganadores
        $decimals = count($winners) > 1 ? 3 : 2;
        $gain = rtrim(rtrim(number_format($jackpot / max(1, count($winners)), $decimals, ',', '.'), '0'), ',');

        // Definir array para guardar información
        $infoPlayer = ['size' => 0, 'name' => [], 'gain' => [], 'ctn' => []];

        // Función para agregar la información de un jugador
        $addPlayerInfo = function ($player) use (&$infoPlayer, $gain, $prizeType) {

            $playerName = BingoCtns::getCtnSerialByRoomUser($player->id);
            // Obtener nombre o identificador del jugador
            if (!$player->customer) {
                if ($prizeType === 'jackpot') {
                    if ($player->player_user) {
                        $playerName = User::getValueById($player->player_user, 'username');
                    } else {
                        $playerLink = PlayerLink::getById($player->player_link, ['name', 'lastname']);
                        $playerLastname = $this->initialCharacters($playerLink->lastname);

                        $playerName = "$playerLink->name $playerLastname";
                    }
                }
            }

            $infoPlayer['name'][] = $playerName;
            $infoPlayer['gain'][] = $gain;
            $infoPlayer['ctn'][] = encrypt($player->PrizeCtns[0]->ctn);
            $infoPlayer['size']++;
        };

        // Añadir jugador específico si existe
        if ($winPlayer) {
            $addPlayerInfo($winPlayer);
        }

        // Añadir todos los demás ganadores, excluyendo al jugador específico si ya fue añadido
        foreach ($winners as $winner) {
            if (!$winPlayer || $winPlayer->id != $winner->id) {
                $addPlayerInfo($winner);
            }
        }

        return $infoPlayer;
    }

    public function handleJackpotWinners($roomID)
    {
        // Obtener la información de la sala
        $room = BingoRoom::with((['Config:room,val_serials']))
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
        $ctnsJackpot = BingoCtns::where('room', $roomID)->whereIn('id', $arrCtnWin)
            ->get(['id', 'customer', 'commerce', 'seller', 'room_user', 'player_user', 'player_link', 'coin', 'lot']);

        // Obtener la cantidad de ganadores
        $winnersCount = count(array_merge($uniquePlayers['playerUser'], $uniquePlayers['playerLink']));

        $nonWinnersCount = BingoRoomUser::getNonWinnersCountByRoom($roomID);

        $generateSerials = $nonWinnersCount - count($arrCtnWin);

        try {

            $jackpotPrize = $room->jackpot_prize;
            $updateData = ['close_room' => Carbon::now()->timestamp];

            // Calcular premios si hay premio serial
            if ($room->Config->val_serials > 0 && $room->serial_prize == 0 && $generateSerials > 0) {
                $serialPrize = $room->jackpot * $room->Config->val_serials / 100;
                $jackpotPrize -= $serialPrize;

                $updateData['jackpot_prize'] = $jackpotPrize;
                $updateData['serial_prize'] = $serialPrize;
            }

            // Actualizar datos de la sala
            BingoRoom::where('id', $roomID)->update($updateData);

            // Calcular el valor de cada premio individual
            $winnerJackpotPrize = $jackpotPrize / $winnersCount;

            // Crear registros de ganadores y actualizar el estado de los jugadores
            $registerPrizeCtn = $this->registerPrizeCtn($roomID, $ctnsJackpot, 1, $winnerJackpotPrize);

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

    public function getWinnersJackpot($roomID, $roomUser)
    {
        $data = $this->getWinnersData($roomID, 1, $roomUser);

        // Si existe algun error
        if (isset($data['Error'])) return $data['Error'];

        // Si no hay ganadores en la sala
        if (!count($data['winners'])) $this->handleJackpotWinners($roomID);

        // Calcular el bote de la sala con respecto a la monde del jugador
        $jackpot = $this->convertPrizeValue($roomID, $data['coin'], 'float');

        return $this->getArrayWinnersData($data['winPlayer'], $data['winners'], $jackpot);
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

        $ctnsSerials = BingoCtns::whereIn('serial', $arrSerialsPlayers)
            ->where('room', $roomID)->get(['id', 'commerce', 'seller', 'room_user', 'player_user', 'player_link', 'coin', 'lot']);

        $winnerJackpotPrize = BingoRoom::getSerialPrize($roomID) / count($arrSerialsPlayers);

        try {
            $registerPrizeCtn = $this->registerPrizeCtn($roomID, $ctnsSerials, 2, $winnerJackpotPrize);
            if (!$registerPrizeCtn) DB::rollBack();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear ganador o actualizar estado: ' . $e->getMessage());
            return response()->json(['error' => 'Hubo un error al procesar la solicitud.'], 500);
        }
    }

    public function getWinnersSerials($roomID, $roomUser) // Obtener Seriales Ganadores
    {
        $data = $this->getWinnersData($roomID, 2, $roomUser);

        // Si existe algun error
        if (isset($data['Error'])) return $data['Error'];

        // Obtener tasa de cambio de la sala
        $gameExchangeRate = GameExchangeRate::getGameExchangeRateValue('BINGO', $roomID, $data['coin']);

        // Calcular el bote de seriales de la sala con respecto a la moneda del jugador
        $serialAmount = $this->formatAmount($data['serialPrize'] * $gameExchangeRate, 'float');

        return $this->getArrayWinnersData($data['winPlayer'], $data['winners'], $serialAmount, 'serial');
    }

    // Obtener Informacion de un carton especifico (encriptado)
    public function getCtnWin($ctn)
    {
        // Obtener el cartón
        $ctn = BingoCtns::find(decrypt($ctn));

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
        $fullName = BingoCtns::getCtnSerialByRoomUser($ctn->room_user);

        if (!$ctn->customer) {
            if ($ctn->player_user) {
                $fullName = User::getValueById($ctn->player_user, 'username');
            } else {
                $playerLink = PlayerLink::getById($ctn->player_link, ['name', 'lastname']);
                $playerLastname = $this->initialCharacters($playerLink->lastname);

                $fullName = "$playerLink->name $playerLastname";
            }
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

                    //dd($winChip->time <= time());

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

    public function verifyExistenceOfSales($coin = '', $user, $room = '')
    {
        if ($coin) {
            try {
                // Intentar descifrar el valor
                $coin = decrypt($coin);
                return ['return' => false, 'coin' => $coin];
            } catch (DecryptException $e) {
                $subQueryCoin = BingoCtnsLot::where($user['type'], $user['id']);

                if ($room) $coin = $subQueryCoin->where('room', $room);

                $coin = $subQueryCoin->value('coin');
            }
        } else {
            $subQueryCoin = BingoCtnsLot::where($user['type'], $user['id']);

            if ($room) $coin = $subQueryCoin->where('room', $room);

            $coin = $subQueryCoin->value('coin');
        }

        if (!$coin && !$room) {
            switch ($user['type']) {
                case 'commerce':
                    $coin = Commerce::getValueById($user['id'], 'coin');
                    break;
                case 'seller':
                    $coin = Seller::getValueById($user['id'], 'coin');
                    break;
                default:
                    # code...
                    break;
            }
        }
        return ['return' => true, 'coin' => $coin];
    }
}
