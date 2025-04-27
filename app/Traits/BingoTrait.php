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
use App\Models\Country;
use App\Models\Customer;
use App\Models\CustomerExchangeRate;
use App\Models\CustomerUser;
use App\Models\GameConfig;
use App\Models\GameExchangeRate;
use App\Models\PlayerLink;
use App\Models\Seller;
use App\Models\User;
use App\Models\UserType;
use App\Models\Wallet;
use App\Services\Customer\HandleTransactions;
use App\Services\Game\Bingo\HandleBingoRooms;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait BingoTrait
{
    use UserVal, GameTrait, RouteRKE, PlayerLinkTrait, SeamlessWallet;

    private function encryptDraw($room)
    {
        $existingSymbols = BingoDrawCode::where('room', $room)->pluck('sym')->toArray();

        $symbolsCount = count($existingSymbols);

        if ($symbolsCount) return false;

        DB::beginTransaction();

        try {

            $newSymbols = collect();
            $timestamp = now();

            for ($i = $symbolsCount + 1; $i <= 10; $i++) {
                $num = $i === 10 ? 0 : $i;

                do {
                    $code = Str::random(3);
                } while (in_array($code, $existingSymbols));

                $newSymbols->push([
                    'num' => $num,
                    'sym' => $code,
                    'room' => $room,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp,
                ]);

                $existingSymbols[] = $code;
            }

            if ($newSymbols->isNotEmpty()) {
                BingoDrawCode::insert($newSymbols->toArray());
            }

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error al crear código de encriptación de fichas para la sala de bingo {$room}: {$e->getMessage()}");
            return false;
        }
    }

    public function handleDraw($room, $roomUser = '')
    {
        date_default_timezone_set(config('app.timezone'));

        $draw = BingoDraw::where('room', $room)->exists();

        if (!$draw) {
            // Obtener la información de la sala y la hora base
            $roomInfo = BingoRoomConfig::getByRoom($room, ['date', 'time']);
            $dataTime = strtotime("{$roomInfo->date} {$roomInfo->time}");

            // Generar codigo por cada numero 0-10
            $this->encryptDraw($room);

            // Obtener chips existentes y símbolos
            $existingChipsSet = BingoDraw::where('room', $room)->pluck('chip')->flip();
            $symbols = BingoDrawCode::where('room', $room)->pluck('sym', 'num');

            // Variables de control
            $lastDrawTime = BingoDraw::where('room', $room)->max('time') ?? $dataTime;
            $currentTimestamp = now();
            $timeChip = BingoRoomConfig::getValueByRoom($room, 'time_chip') ?? 6;
            $timeIncrement = 8;
            $inserts = [];
            $availableChips = range(1, 75);

            // Mezclar el array para obtener números aleatorios sin repeticiones
            shuffle($availableChips);

            foreach ($availableChips as $rnd) {
                if (isset($existingChipsSet[$rnd])) {
                    continue; // Si el número ya existe en la BD, se ignora
                }

                // Codificar el número
                $numEncoded = implode('', array_map(
                    fn($digit) => $symbols[$digit == '0' ? 10 : $digit] ?? '',
                    str_split($rnd)
                ));

                // Calcular la hora del sorteo para este chip
                $currentDrawTime = $lastDrawTime + $timeIncrement;

                $inserts[] = [
                    "chip" => $rnd,
                    "code" => $numEncoded,
                    "room" => $room,
                    "time" => $currentDrawTime,
                    "created_at" => $currentTimestamp,
                    "updated_at" => $currentTimestamp,
                ];

                $lastDrawTime = $currentDrawTime;
                $timeIncrement = $timeChip; // Cambiar el incremento después del primer chip
            }

            if (!empty($inserts)) {
                DB::beginTransaction();
                try {
                    BingoDraw::insert($inserts);
                    DB::commit();
                    return $this->chipWin($room);
                } catch (\Throwable $th) {
                    DB::rollBack();
                    throw $th;
                }
            }
        } else if ($draw && $roomUser && $this->user(['user_type']) == 2) {

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

            $draw = BingoDraw::where('room', $room)->whereNotIn('chip', $ctnFull)->inRandomOrder()->pluck('chip')->toArray();

            $firstFour = array_slice($draw, 0, 4); // Obtener los primeros 4 elementos
            $draw = array_slice($draw, 4); // Eliminar los primeros 4 elementos del array original

            $newDraw = array_merge($firstFour, $ctnFull, $draw); // Nuevo Draw

            $newDrawValues = array_map(function ($chip) {
                return intval($chip);
            }, $newDraw);

            $OrigDraw = BingoDraw::where('room', $room)->pluck('id');

            // Construir un arreglo asociativo de ID => Chip para actualizar
            $updateData = array_combine($OrigDraw->toArray(), $newDrawValues);

            DB::beginTransaction();

            try {
                // Construir la consulta SQL dinámica para actualizar los chips en función de sus IDs
                $sql = 'UPDATE bingo_draw SET chip = CASE id ';
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

        if (!BingoDraw::getWinningChip($room) || $roomUser) {
            return $this->chipWin($room);
        }

        return false;
    }

    public function chipWin($room)
    {
        DB::beginTransaction();

        try {
            // Si no hay jugadores, asignar manualmente la ficha ganadora
            if (!BingoRoomUser::where('room', $room)->exists()) {
                $winChip = BingoDraw::getFirstDrawChip($room, 'id')->id + 23;
                BingoDraw::where('id', $winChip)->where('room', $room)->update(['status' => 3]);

                DB::commit();
                return BingoDraw::select('id', 'chip')->find($winChip);
            }

            // Crear un índice invertido para las fichas en los cartones
            $chipIndex = [];

            // Obtener los valores de los cartones en chunks
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
                        // Excluir 'id' y trabajar directamente con los demás valores
                        $chips = collect($carton)->except('id');
                        foreach ($chips as $chip) {
                            if (!is_null($chip)) {
                                $chipIndex[$chip][] = $carton->id;
                            }
                        }
                    }
                });

            // Buscar ficha ganadora en los cartones
            $winningChip = null;
            $chipsVanAssoc = [];
            $arrCtns = [];

            // Obtener las fichas ordenadas de forma descendente
            $drawDesc = BingoDraw::where('room', $room)->orderByDesc('id')->pluck('chip')->toArray();

            // Buscar la última ficha de cada cartón
            foreach ($drawDesc as $chip) {
                if (isset($chipIndex[$chip])) {
                    foreach ($chipIndex[$chip] as $key) {
                        // Si el cartón ya fue procesado, salta
                        if (isset($arrCtns[$key])) {
                            continue;
                        }
                        // Marca el cartón como procesado
                        $arrCtns[$key] = true;
                        // Almacena la ficha ganadora
                        $chipsVanAssoc[$chip] = true;
                    }
                }
            }

            // Recorremos $drawDesc en orden inverso
            for ($i = count($drawDesc) - 1; $i >= 0; $i--) {
                $chip = $drawDesc[$i];
                if (isset($chipsVanAssoc[$chip])) {
                    $winningChip = $chip;
                    break;
                }
            }

            if ($winningChip) {
                BingoDraw::where('chip', $winningChip)->where('room', $room)->update(['status' => 3]);
            }

            $this->insertGameExchangeRate($room, 'BINGO'); // Registrar Tasas de Cambio

            DB::commit();

            return BingoDraw::select('id', 'chip', 'time')->where('chip', $winningChip)->where('room', $room)->first();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error al registrar ficha ganadora en la Sala Bingo (ID: {$room}): {$e->getMessage()}");
        }
    }

    public function recorrersorteo($room)
    {
        $encrypted = BingoRoom::getValueById($room, 'encrypted');
        date_default_timezone_set(config('app.timezone'));

        $result = false;
        do {
            $chip = BingoDraw::select('id', 'chip', 'status')->where('room', $room)
                ->where('time', '<=', time())->where('status', 1)->first();

            //if ($chip->win == 1) $result = $this->getInfoWinners($room);
            event(new BingoOFF_ProccessDraw($encrypted, $chip, $result));

            $chip->update(['status' => 2]);

            if ($chip->win == 1) BingoRoom::where('id', $room)->update(['status' => 4]);
        } while (BingoRoom::where('id', $room)->where('status', 3)->exists());
    }

    public function getInfoWinners($room, $roomUser, $coin = '')
    {
        date_default_timezone_set(config('app.timezone'));

        $room = BingoRoom::getByEncrypted($room, ['id', 'status']);
        $roomID = $room->id;

        // Obtener tiempo de salida de la ficha jackpot
        $lastChipTime = BingoDraw::where('status', 3)->where('room', $roomID)->value('time');

        if ($room->status == 3 && ($lastChipTime && $lastChipTime <= time())) {
            $room->update(['status' => 4]);
        }

        //Obtener Conteo de Ganadores
        $countWinnersJackpot = BingoPrizeCtns::getWinnersCountByPrizeType($roomID, 1);

        if (!$countWinnersJackpot) $this->handleJackpotWinners($roomID);

        $jackpotWinners = $this->getWinnersJackpot($roomID, $roomUser, $coin);

        $serialsWinners = (BingoPrizeCtns::getWinnersCountByPrizeType($roomID, 2)) ?
            $this->getWinnersSerials($roomID, $roomUser, $coin) : [];

        $serialPrizePct = BingoRoomConfig::getValueByRoom($roomID, 'pct_serials');

        $prizesValues = !empty($serialsWinners) ? [100 - $serialPrizePct, $serialPrizePct] : [100];

        return [
            'jackpotWinners' => $jackpotWinners,
            'serialsWinners' => $serialsWinners,
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
            'jackpot',
            'jackpot_prize',
            'ctns'
        )->where('status', 2)->where('id', $roomID)->first();
    }

    // Función para calcular la comisión del vendedor
    private function calculateSellerCommission($seller, $saleAmount, $roomID)
    {
        if (!$seller) return 0;

        $commnSeller = SellerRoomLimits::getGameRoomValueBySeller(1, $seller, $roomID, 'commn') ?? Seller::getValueById($seller, 'commn');
        // Registar la comision del vendedor para la sala de jeugo
        SellerRoomLimits::firstOrCreate(['game' => 1, 'room' => $roomID, 'seller' => $seller], ['commn' => $commnSeller]);

        $commnSeller = ($saleAmount * $commnSeller) / 100;

        return $commnSeller ? number_format($commnSeller, 2, '.') : 0;
    }

    // Función para crear el lote de cartones
    private function createCtnsLot($arrCtn, $exchangeRate, $saleAmount)
    {
        $seller = $arrCtn['seller'] ?? null;

        $finalSaleAmount = $saleAmount;

        if ($seller) {
            $sellerCommn = Seller::getValueById($seller, 'commn') / 100;
            $finalSaleAmount = number_format($saleAmount * (1 - $sellerCommn), 2, '.');
        }

        $currentTime = now();

        try {
            return BingoCtnsLot::insertGetId([
                'creator_user' => $arrCtn['creator']['userId'],
                'seller' => $seller,
                'room_user' => $arrCtn['roomUser'],
                'player_user' => $arrCtn['playerUser'] ?? null,
                'player_link' => $arrCtn['playerLink'] ?? null,
                'room' => $arrCtn['room'],
                'ctn_price' => $arrCtn['ctnPrice'],
                'exchange_rate' => $exchangeRate,
                'ctns' => $arrCtn['numOfCtns'],
                'coin' => $arrCtn['coin'],
                'sale_amount' => $saleAmount,
                'final_sale_amount' => $finalSaleAmount,
                'status' => 0,
                'created_at' => $currentTime,
                'updated_at' => $currentTime,
            ]);
        } catch (\Exception $e) {
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
                $countryID = PlayerLink::getValueById($arrCtn['playerLink'], 'country');
                $country = Country::getValue($countryID, 'iso_code_2');
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
            'creator_user' => $arrCtn['creator']['userId'],
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
                'creator_user' => $arrCtn['creator']['userId'],
                'seller' => $arrCtn['seller'] ?? null,
                'player_user' => $playerUser,
                'player_link' => $arrCtn['playerLink'] ?? null,
                'ctns' => $arrCtn['numOfCtns'],
                'automarker' => 1
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

    // Función para actualizar el bote acumulado y otras métricas de la sala
    private function updateRoomMetrics($room, $arrCtn, $saleAmount, $sellerSaleCommn)
    {
        try {
            // Recalcular venta y comisiones en base a USDT
            $exchangeRate = $arrCtn['exchangeRate'];

            if ($exchangeRate != 0) {
                $saleAmount /= $exchangeRate;
                $sellerSaleCommn /= $exchangeRate;
            }

            // Variable para definir monto a anexar al bote
            $amountAddJackpot = number_format($saleAmount, 3, '.');

            if ($arrCtn['jackpotType'] == 2) {
                // Si el bote es tipo "Premio"
                $amountAddJackpot = 0;
            }

            // Obtener comision del creador/casa (comision por sala)\
            $creatorCommn = ($amountAddJackpot * BingoRoomConfig::getValueByRoom($room->id, 'room_commn')) / 100;
            $creatorCommn =  number_format($creatorCommn, 3, '.');

            // Restar al bote la comision del creador de la sala
            if ($creatorCommn) $amountAddJackpot -= $creatorCommn;

            if ($sellerSaleCommn) $amountAddJackpot -= $sellerSaleCommn;

            $gameConfig = GameConfig::getGameParam('BINGO', 'COMMN');

            if ($gameConfig && $gameConfig->status == 1) {
                $gameCommn = number_format(($amountAddJackpot * $gameConfig->param_val) / 100, 3, '.');
                $amountAddJackpot -= $gameCommn;
            }

            // Preparar los datos para actualizar la sala
            $updateData = [
                'all_sale' => $room->all_sale + $saleAmount,
                'jackpot' => $room->jackpot + $amountAddJackpot,
                'jackpot_prize' => $room->jackpot_prize + $amountAddJackpot,
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
            Log::error('Error al actualizar los datos de la sala: ' . $e->getMessage());
            return false;
        }
    }

    public function calculateCtnPrice($bingoCtnPrice, $setPrice = false, $exchangeRate = 1)
    {
        // Calcular precio del carton en USDT 
        $formattedPrice = number_format($exchangeRate * $bingoCtnPrice, 2, '.');

        if ($setPrice) {
            // Calcular precio del carton en USDT y redondear de acuerdo a la moneda 
            $formattedPrice = number_format($formattedPrice / $exchangeRate, 3, '.', '');

            if (fmod($formattedPrice, 1) == 0) {
                $formattedPrice = number_format($formattedPrice, 2, '.', '');
            }
        }

        return $formattedPrice;
    }

    public function cartonStore($arrCtn)
    {
        (new HandleBingoRooms())->checkRoomClosure();

        $roomID = $arrCtn['room'];

        $room = $this->getRoomDetails($roomID);

        if (!$room) return ['Error' => 'Sala no disponible'];

        DB::beginTransaction();

        try {
            // Inicialización de variables
            $creator = $arrCtn['creator'];
            $seller = $arrCtn['seller'] ?? null;
            $playerUser = $arrCtn['playerUser'] ?? null;
            $coin = $arrCtn['coin'];
            $numOfCtns = $arrCtn['numOfCtns'];
            $gameMode = $room->Config->game_mode;

            // Formatear Monto de Venta
            $saleAmount = number_format($numOfCtns * $arrCtn['ctnPrice'], 2, '.');

            // Registrar o actualizar el usuario en la sala
            $roomUser = $this->registerOrUpdateRoomUser($room, $arrCtn);

            if (!$roomUser) {
                DB::rollBack();
                return ['Error' => 'Registrar o actualizar el usuario en la sala'];
            }

            // Anexar atributo al array
            $arrCtn['roomUser'] = $roomUser;

            if ($gameMode != 1 && ($creator['type'] === 'commerce' || $seller)) {
                // Calcular monto total de venta y comisiones si es un vendedor
                $sellerSaleCommn = $this->calculateSellerCommission($seller, $saleAmount, $roomID);
            }

            // Evaluar tasa de cambio
            if ($creator['type'] === 'customer') {
                $customerExchangeRate = CustomerExchangeRate::getById($coin, ['coin', 'exchange_rate']);
                $coin = $customerExchangeRate->coin;
                $exchangeRate = $customerExchangeRate->exchangeRate ?? 1;
            } else {
                $exchangeRate = CommerceExchangeRate::getCommerceCoinValue($creator['id'], $coin) ?? 1;
            }

            // Crear lote de cartones
            $lotID = $this->createCtnsLot($arrCtn, $exchangeRate, $saleAmount);

            // Generar y almacenar los cartones de bingo
            $generateAndStoreCtns = $this->generateAndStoreCtns($arrCtn, $lotID);

            if (!$generateAndStoreCtns) {
                DB::rollBack();
                return ['Error' => 'Error al generar cartones'];
            }

            // Si no es modo offline y el creador es un comercio, actualizar la billetera del jugador
            if ($gameMode != 2 && $creator['type']) {
                // Determinar la columna de saldo en función de la moneda
                $columnBalance = $coin == 1 ? 'usdt' : 'balance';

                // Descontar monto del saldo del usuario
                $walletUpdated = Wallet::where('user', $playerUser)->decrement($columnBalance, $saleAmount);

                // Verificar si se actualizó algún registro
                if ($walletUpdated === 0) {
                    DB::rollBack();
                    return ['Error' => 'Error al descontar saldo del usuario'];
                }
            }

            // Actualizar acumulado y comisiones de la sala
            $updateRoomMetrics = $this->updateRoomMetrics($room, $arrCtn, $saleAmount, $sellerSaleCommn ?? 0);

            // Verificar si se actualizó los valores de la sala
            if (!$updateRoomMetrics) {
                DB::rollBack();
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
                    DB::rollBack();
                    return ['Error' => 'Error al procesar la transferencia: ' . $transactionBet['Error']];
                }
            }

            // Generar un nuevo sorteo si aplica
            if (isset($arrCtn['ctnWin'])) {
                $this->handleDraw($roomID, $roomUser);
            }

            DB::commit();
            return $roomUser;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear los cartones cartonStore(): ' . $e->getMessage());
            return ['Error' => 'Error al crear los cartones'];
        }
    }

    public function handleCartonStore($data)
    {
        $room = BingoRoom::with(['Config:room,jackpot_type'])
            ->select('id', 'room_type', 'status')
            ->where('id', $data['room'])->first();

        if ($room->status > 2) {
            return ['Error' => 'Sala no disponible'];
        }
    }

    public function convertPrizeValue($roomID, $coin, $value = '')
    {
        $room = BingoRoom::getById($roomID, ['creator_user', 'jackpot']);

        $creatorUser = $room->creator_user;
        $userCreator = User::getCreatorIDByUserType($creatorUser, '', true);

        $creator = ['type' => $userCreator['type'], 'id' => $userCreator['id'], 'userId' => $creatorUser];

        $data = ['coin' => $coin, 'value' => $value ? $value : $room->jackpot, 'creator' => $creator];

        return $this->convertValueByGame('BINGO', $roomID, $data);
    }

    private function registerPrizeCtn($roomID, $cartons, $prizeType, $USDValue)
    {
        $recordsToInsert = $playersToUpdate = [];

        foreach ($cartons as $ctn) {

            //Determinar la columna según el modo de juego
            $playerColumn = $ctn->player_user ? 'player_user' : 'player_link';

            $playerColumnVal = $ctn->player_user ?? $ctn->player_link;

            if (!BingoPrizeCtns::existsPrizeCtn($ctn->id)) {

                // Verificar y obtener el tipo de cambio
                $exchangeRate = GameExchangeRate::getGameExchangeRateValue('BINGO', $roomID, $ctn->coin) ?? 1;

                $prizeWn = rtrim(rtrim(number_format($USDValue * $exchangeRate, 2, '.', ''), '0'), '.');

                $timestamp = now();

                $record = [
                    'active' => 'N',
                    'room' => $roomID,
                    'creator_user' => $ctn->creator_user,
                    'room_user' => $ctn->room_user,
                    'seller' => $ctn->seller,
                    'player_user' => $ctn->player_user,
                    'player_link' => $ctn->player_link,
                    'ctn' => $ctn->id,
                    'lot' => $ctn->lot,
                    'prize_type' => $prizeType,
                    'coin' => $ctn->coin,
                    'amount' => $prizeWn,
                    'status' => null,
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

            DB::commit();
            return true;
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al registrar carton ganador ===> $prizeType [' . $prizeType . '] : ' . $e->getMessage());
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
                $playerLink = BingoRoomUser::getValue($playerID, 'player_link');
                $coin = PlayerLink::getValueById($playerLink, 'coin');
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
            $winPlayer = BingoRoomUser::where('room', $room)->where('winner', 1)->exists();
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

    public function getArrayWinnersData($winPlayer, $winners, $jackpot)
    {
        // Inicializar el array para guardar información
        $infoPlayer = ['size' => 0, 'name' => [], 'gain' => [], 'ctn' => []];

        // Función para agregar la información de un jugador
        $addPlayerInfo = function ($player) use (&$infoPlayer) {

            $ctnId = optional($player->PrizeCtns[0])->ctn;

            $infoPlayer['name'][] = BingoCtns::getValueById($ctnId, 'serial');
            $infoPlayer['gain'][] = BingoPrizeCtns::getValueById($ctnId, 'amount');
            $infoPlayer['ctn'][] = encrypt($ctnId);
            $infoPlayer['size']++;
        };

        // Crear una colección para evitar duplicados
        $allPlayers = collect([$winPlayer])->merge($winners)->unique('id');

        // Iterar sobre todos los jugadores únicos y agregar la información
        foreach ($allPlayers as $player) {
            if ($player) {
                $addPlayerInfo($player);
            }
        }

        return $infoPlayer;
    }

    public function handleJackpotWinners($roomID)
    {
        // Obtener la información de la sala
        $room = BingoRoom::with('Config:room,pct_serials')
            ->select('id', 'creator_user', 'jackpot', 'jackpot_prize', 'serial_prize')
            ->where('id', $roomID)->firstOrFail();

        // Verificar si ya hay ganadores del premio mayor
        $prizeCtns = BingoPrizeCtns::getWinnersCountByPrizeType($roomID, 1);
        $winners = BingoRoomUser::getWinnersCountByRoom($roomID);

        if ($prizeCtns && $winners) return false; // Ya existen cartones premiados (jackpot)

        // Obtener el último chip sorteado
        $lastChip = BingoDraw::select('id', 'chip')->where('status', 3)->where('room', $roomID)->first();

        // Obtener todos los chips sorteados hasta el último
        $chipsDraw = BingoDraw::where('room', $roomID)
            ->where('id', '<=', $lastChip->id)
            ->pluck('chip')
            ->toArray();

        // Declarar ultima ficha
        $lastChipNum = $lastChip->chip;

        // Inicializar array de jugadores únicos
        $uniquePlayers = ['playerUser' => [], 'playerLink' => []];

        // Buscar los cartones que contienen el chip
        $ctnsPlayer = BingoCtns::where('room', $roomID)
            ->whereRaw(
                "b1 = ? OR b2 = ? OR b3 = ? OR b4 = ? OR b5 = ? 
                OR i1 = ? OR i2 = ? OR i3 = ? OR i4 = ? OR i5 = ?
                OR n1 = ? OR n2 = ? OR n3 = ? OR n4 = ? 
                OR g1 = ? OR g2 = ? OR g3 = ? OR g4 = ? OR g5 = ? 
                OR o1 = ? OR o2 = ? OR o3 = ? OR o4 = ? OR o5 = ?",
                array_fill(0, 24, $lastChipNum) // Repite el valor 24 veces
            )->select(['id', 'room_user', 'player_user', 'player_link'])->cursor();

        // Filtrar cartones ganadores
        $prizeCtns = [];
        $roomUserIds = [];
        $uniquePlayers = ['playerUser' => [], 'playerLink' => []];

        foreach ($ctnsPlayer as $ctn) {
            $playerId = $ctn->player_user ?: $ctn->player_link;
            $playerType = $ctn->player_user ? 'playerUser' : 'playerLink';

            // Evitar revalidaciones innecesarias con `isset()` en lugar de `in_array()`
            if (!isset($uniquePlayers[$playerType][$playerId])) {
                $cartonNumbers = BingoCtns::where('id', $ctn->id)
                    ->select([
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
                    ])->first()->toArray();

                if (count(array_intersect($cartonNumbers, $chipsDraw)) === count($cartonNumbers)) {
                    $uniquePlayers[$playerType][$playerId] = true;
                    $prizeCtns[] = $ctn->id;
                    $roomUserIds[] = $ctn->room_user;
                }
            }
        }

        // Obtener información de los cartones ganadores
        $ctnsJackpot = BingoCtns::where('room', $roomID)->whereIn('id', $prizeCtns)
            ->get(['id', 'creator_user', 'seller', 'room_user', 'player_user', 'player_link', 'coin', 'lot']);

        // Obtener la cantidad de ganadores
        $winnersCount = count(array_merge($uniquePlayers['playerUser'], $uniquePlayers['playerLink']));

        $nonWinnersCount = BingoRoomUser::getNonWinnersCountByRoom($roomID);

        $generateSerials = $nonWinnersCount - count($prizeCtns);

        DB::beginTransaction();

        try {

            // Definir valores a actualizar de la sala 
            $updateData = ['close_room' => Carbon::now()->timestamp];

            $jackpotPrize = $room->jackpot_prize;

            // Generar seriales ganadores si hay jugadores no ganadores
            if ($generateSerials && $room->Config->pct_serials && !$room->seller_commn && !BingoPrizeCtns::existsPrizeCtns($roomID, 'SERIAL')) {

                $serialPrize = $room->jackpot * $room->Config->pct_serials / 100;

                $serialWinners = $this->handleSerialWinners($roomID, $serialPrize, $roomUserIds);

                if ($serialWinners) {
                    // Calcular premios si hay premio serial
                    $jackpotPrize -= $serialPrize;

                    $updateData['jackpot_prize'] = $jackpotPrize;
                    $updateData['serial_prize'] = $serialPrize;
                } else {
                    DB::rollBack();
                }
            }

            // Calcular el valor de cada premio individual
            $winnerJackpotPrize = $jackpotPrize / $winnersCount;
            // Crear registros de ganadores y actualizar el estado de los jugadores
            $registerPrizeCtn = $this->registerPrizeCtn($roomID, $ctnsJackpot, 1, $winnerJackpotPrize);

            if (!$registerPrizeCtn) DB::rollBack();

            // Actualizar datos de la sala
            BingoRoom::where('id', $roomID)->update($updateData);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error en handleJackpotWinners: ' . $e->getMessage());
            // Devolver un mensaje de error
            return response()->json(['error' => 'Hubo un error al procesar la solicitud.'], 500);
        }

        // Validar Transacciones
        if (UserType::getValueById(User::getValueById($room->creator_user, 'user_type'), 'prefix') === 'customer') {
            $customer = Customer::getValueByUserId($room->creator_user, 'id');
            app(HandleTransactions::class)->validateTransactions($customer);
        }
    }

    public function getWinnersJackpot($roomID, $roomUser, $coin)
    {
        $data = $this->getWinnersData($roomID, 'JACKPOT', $roomUser);

        // Si existe algun error
        if (isset($data['Error'])) return $data['Error'];

        // Si no hay ganadores en la sala
        if (!count($data['winners'])) $this->handleJackpotWinners($roomID);

        $coin = $coin ? $coin : $data['coin'];
        $jackpotPrize = BingoRoom::getValueById($roomID, 'jackpot_prize');

        // Calcular el bote de la sala con respecto a la moneda del jugador
        $jackpot = $this->formatAmount($this->convertPrizeValue($roomID, $coin, $jackpotPrize), 'float');

        return $this->getArrayWinnersData($data['winPlayer'], $data['winners'], $jackpot);
    }

    public function handleSerialWinners($roomID, $serialPrize, $roomUserIds) // Obtener Seriales Ganadores
    {
        try {

            $getWinners = BingoPrizeCtns::getWinnersCountByPrizeType($roomID, 2);

            if ($getWinners) return false;

            // Obtener ultima ficha en salir (ganadora) 
            $lastChip = BingoDraw::where('room', $roomID)->where('status', 3)->value('id');

            $chipsDraw = array_flip(BingoDraw::where('room', $roomID)
                ->where('id', '<', $lastChip)->pluck('chip')->toArray());

            $countCtns = 0;
            $countSerials = BingoRoomConfig::getValueByRoom($roomID, 'num_serials');

            $ctnsPlayer = BingoCtns::where('room', $roomID)
                ->whereNotIn('room_user', $roomUserIds)
                ->lazyById(100)
                ->takeWhile(function () use (&$countCtns, $countSerials) {
                    return $countCtns < $countSerials;
                })->shuffle();

            $uniquePlayers = [];
            $serialCtns = [];

            foreach ($ctnsPlayer as $ctn) {
                // Obtener números del cartón 
                $numbers = [
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

                // Contar aciertos de forma más eficiente
                $count = count(array_intersect($numbers, array_keys($chipsDraw)));

                if ($count > 14 && $count < 24) {
                    $playerId = $ctn->player_user ?: $ctn->player_link;
                    $playerType = $ctn->player_user ? 'playerUser' : 'playerLink';

                    // Verificar si el jugador ya ha ganado antes
                    if (!isset($uniquePlayers[$playerType][$playerId])) {
                        $uniquePlayers[$playerType][$playerId] = true; // Registrar jugador
                        $serialCtns[] = $ctn->id;
                        $countCtns++;

                        // Detener la iteración si ya se alcanzó el límite
                        if ($countCtns == $countSerials) {
                            break;
                        }
                    }
                }
            }

            // Si no hay ganadores con aciertos entre 15-23
            if ($countCtns === 0) return false;

            $ctnsSerials = BingoCtns::whereIn('id', $serialCtns)
                ->where('room', $roomID)->get(['id', 'seller', 'creator_user', 'room_user', 'player_user', 'player_link', 'coin', 'lot']);

            $winnerJackpotPrize = $serialPrize / count($serialCtns);

            $this->registerPrizeCtn($roomID, $ctnsSerials, 2, $winnerJackpotPrize);

            return true;
        } catch (\Exception $e) {
            Log::error('Error en handleSerialWinners(): ' . $e->getMessage());
            return false;
        }
    }

    public function getWinnersSerials($roomID, $roomUser, $coin) // Obtener Seriales Ganadores
    {
        $data = $this->getWinnersData($roomID, 'SERIAL', $roomUser);

        // Si existe algun error
        if (isset($data['Error'])) return $data['Error'];

        $coin = $coin ? $coin : $data['coin'];

        // Obtener tasa de cambio de la sala
        $gameExchangeRate = GameExchangeRate::getGameExchangeRateValue('BINGO', $roomID, $coin);

        // Calcular el bote de seriales de la sala con respecto a la moneda del jugador
        $serialAmount = $this->formatAmount($data['serialPrize'] * $gameExchangeRate, 'float');

        return $this->getArrayWinnersData($data['winPlayer'], $data['winners'], $serialAmount);
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
                $playerLink = PlayerLink::getById($ctn->player_link, ['name', 'lastname']);
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

    function insertCartonPayment($arr)
    {
        $timestamp = now();
        // Crear un array de datos para insertar en la base de datos
        $data = [];
        foreach ($arr as $payment) {
            $data[] = [
                'payment' => $payment[0], // PAGO [CTN] ENVIADO DE VENDEDOR A LA COMERCIALIZADORA
                'room' => $payment[1],
                'sender' => $payment[2],
                'commerce' => $payment[3],
                'player_link' => $payment[4],
                'lot' => $payment[5],
                'ctn' => $payment[6],
                'coin' => $payment[7],
                'amount' => $payment[8],
                'payment_proof' => $payment[10],
                'created_at' => $timestamp,
                'updated_at' => $timestamp
            ];
        }
        // Inserción masiva en la base de datos
        return BingoCtnsPayment::insert($data);
    }

    function registerCartonPayment($payment, $lotCtn, $paymentImage)
    {
        return BingoCtnsPayment::create([
            'payment' => $payment,
            'room' => $lotCtn->room,
            'creator_user' => $lotCtn->creator_user,
            'seller' => $lotCtn->seller,
            'player_link' => $lotCtn->player_link,
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
                'player_link' => $prizeCtn->player_link,
                'sender' => $sender,
                'prize_type' => $prizeCtn->prize_type,
                'lot' => $prizeCtn->lot,
                'ctn' => $prizeCtn->ctn,
                'coin' => $prizeCtn->coin,
                'amount' => $prizeCtn->amount,
                'payment_proof' => $filePayment,
            ]);
        } catch (\Exception $e) {
            Log::error("'Error al guardar el registro del pago del carton ganador: " . $e->getMessage());
            return false;
        }
    }

    public function verifyExistenceOfSales($user, $dateFilter = '', $coin = '',  $room = '')
    {
        // Aplicar el filtro de moneda (coin) a la consulta
        $query = BingoCtnsLot::where('creator_user', $user['userId']);

        if ($coin) {
            try {
                // Intentar descifrar el valor
                $coin = decrypt($coin);
            } catch (DecryptException) {
                $coin = $coin;
            }
            $query->where('coin', $coin);
        }

        if ($room) {
            $query->where('room', $room);
        }

        $existsSale = $query->exists();

        if ($existsSale && $dateFilter) {
            $existsSaleDateFilter = $this->applyDateFilter($dateFilter, $query)->exists();
            if (!$existsSaleDateFilter) {
                return ['return' => true, 'coin' => $coin];
            }
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
                    break;
            }
            return ['return' => true, 'coin' => $coin];
        }

        return $coin;
    }
}
