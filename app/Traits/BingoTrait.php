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
use App\Models\Customer;
use App\Models\CustomerExchangeRate;
use App\Models\CustomerUser;
use App\Models\GameExchangeRate;
use App\Models\PlayerLink;
use App\Models\Seller;
use App\Models\User;
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

    public function newDraw($room, $roomUser = '')
    {
        $draw = BingoDraw::where('room', $room)->count() === 75;

        if (!$draw) {

            date_default_timezone_set(config('app.timezone'));

            $this->encryptDraw($room);

            // Consolidar consultas para mejorar rendimiento
            $roomInfo = BingoRoom::getRoomById($room, ['date', 'time']);
            $dataTime = strtotime("{$roomInfo->date} {$roomInfo->time}");

            $existingChips = BingoDraw::where('room', $room)->get(['chip', 'time']);
            $symbols = BingoDrawCode::where('room', $room)->pluck('sym', 'num');

            $lastDrawTime = $existingChips->max('time') ?? $dataTime;
            $existingChipsSet = $existingChips->pluck('chip')->flip();

            $chips = [];
            $inserts = [];
            $currentTimestamp = now();
            $timeIncrement = 8;

            for ($chp = 1; $chp <= 75; $chp++) {
                do {
                    $rnd = mt_rand(1, 75);
                } while (isset($chips[$rnd]) || isset($existingChipsSet[$rnd]));

                // Codificar el número
                $numEncoded = implode('', array_map(
                    fn($digit) => $symbols[$digit == '0' ? 10 : $digit] ?? '',
                    str_split($rnd)
                ));

                $currentDrawTime = $lastDrawTime + $timeIncrement;

                $inserts[] = [
                    "chip" => $rnd,
                    "code" => $numEncoded,
                    "room" => $room,
                    "time" => $currentDrawTime,
                    "created_at" => $currentTimestamp,
                    "updated_at" => $currentTimestamp,
                ];

                $chips[$rnd] = true;
                $lastDrawTime = $currentDrawTime;

                // Cambiar incremento después del primer chip
                $timeIncrement = 9;
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
            // Restablecer el estado de las fichas ganadoras
            BingoDraw::where('win', 1)->where('room', $room)->update(['status' => null]);

            // Si no hay jugadores, asignar manualmente la ficha ganadora
            if (!BingoRoomUser::where('room', $room)->exists()) {
                $winChip = BingoDraw::getFirstDrawChip($room, 'id')->id + 23;
                BingoDraw::where('id', $winChip)->where('room', $room)->update(['win' => 1]);

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
                BingoDraw::where('chip', $winningChip)->where('room', $room)->update(['win' => 1]);
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
        $lastChipTime = BingoDraw::where('win', 1)->where('room', $room->id)->value('time');

        if ($room->status == 3 && ($lastChipTime && $lastChipTime <= time())) {
            $room->update(['status' => 4]);
        }

        //Obtener Conteo de Ganadores
        $countWinnersJackpot = BingoPrizeCtns::getWinnersCountByPrizeType($room->id, 1);

        if (!$countWinnersJackpot) $this->handleJackpotWinners($room->id);

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
            'jackpot',
            'jackpot_prize',
            'ctns'
        )->where('status', 2)->where('id', $roomID)->first();
    }

    // Función para calcular el monto total de venta
    private function calculateTotalSale($saleAmount, $room, $coin, $seller, $commerce)
    {
        $exchangeRate = ($coin != 1) ? CommerceExchangeRate::getCommerceCoinValue($commerce, $coin) : 1;
        $totalSaleCtns = number_format(($saleAmount * $exchangeRate), 2, '.');

        // Comisiones del vendedor
        $saleCommn = $this->calculateSellerCommission($seller, $totalSaleCtns, $room->id);
        $sellerSaleCommn = number_format($saleCommn / $exchangeRate, 8, '.');;

        return ['exchangeRate' => $exchangeRate, 'sellerSaleCommn' => $sellerSaleCommn];
    }

    // Función para calcular la comisión del vendedor
    private function calculateSellerCommission($seller, $totalSaleCtns, $roomID)
    {
        if (!$seller) return 0;

        $commnSeller = SellerRoomLimits::getGameRoomValueBySeller(1, $seller, $roomID, 'commn') ?? Seller::getValueById($seller, 'commn');
        // Registar la comision del vendedor para la sala de jeugo
        SellerRoomLimits::firstOrCreate(['game' => 1, 'room' => $roomID, 'seller' => $seller], ['commn' => $commnSeller]);

        $commnSeller = ($totalSaleCtns * $commnSeller) / 100;

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

        return BingoCtnsLot::insertGetId([
            'customer' => $arrCtn['customer'] ?? null,
            'commerce' => $arrCtn['commerce'] ?? null,
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
                'customer' => $arrCtn['customer'] ?? null,
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

    // Función para actualizar el bote acumulado y otras métricas de la sala
    private function updateRoomMetrics($room, $arrCtn, $saleAmount, $sellerSaleCommn)
    {
        try {
            // Variable para definir monto a anexar al bote
            $amountAddJackpot = number_format($saleAmount - $sellerSaleCommn, 6, '.');

            if ($arrCtn['jackpotType'] == 2) {
                // Si el bote es tipo "Premio"
                $amountAddJackpot = 0;
            }

            // Obtener comision del creador/casa (comision por sala)\
            $creatorCommn = ($amountAddJackpot * BingoRoomConfig::getValueByRoom($room->id, 'room_commn')) / 100;
            $creatorCommn =  number_format($creatorCommn, 6, '.');

            // Restar al bote la comision del creador de la sala
            if ($creatorCommn) $amountAddJackpot -= $creatorCommn;

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

    public function calculateCtnPrice($exchangeRate, $roomCtnPrice)
    {
        // Calcular precio del carton y redondear de acuerdo a la moneda 
        $ctnPrice = number_format($exchangeRate * $roomCtnPrice, 2, '.');
        // Calcular precio del carton en USDT 
        return number_format($ctnPrice / $exchangeRate, 8, '.');
    }

    public function cartonStore($arrCtn)
    {
        $roomID = $arrCtn['room'];
        (new HandleBingoRooms())->checkRoomClosure();

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
            $saleAmount = $numOfCtns * $arrCtn['ctnPrice'];
            $sellerSaleCommn = 0;

            if ($customer) {
                $customerExchangeRate = CustomerExchangeRate::getById($coin, ['coin', 'exchange_rate']);
                $coin = $customerExchangeRate->coin;
                $exchangeRate = $customerExchangeRate->exchangeRate ?? 1;
            };

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
            $totalSaleAmount = number_format($exchangeRate * $saleAmount, 2, '.');

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

            // Definir coinISO
            if ($customer) {
                $coinISO = CustomerExchangeRate::getValueById($coin, 'coin');
            } else {
                $coinISO = $coin;
            }

            //Registrar nueva moneda en juego de ser necesario
            if (!CoinGameRoom::existsCoinGameRoom($coinISO, $roomID, 'BINGO', $gameMode)) {
                CoinGameRoom::createNewCoinInRoom($game, $roomID, $coinISO);
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
            ->where('id', $data['room'])->first();

        if ($room->status > 2) {
            return ['Error' => 'Sala no disponible'];
        }

        $roomID = $room->id;

        $isLogin = $data['isLogin'] ?? false;
        $data['room'] = $roomID;
        $coin = $data['coin'];
        $commerce = $data['commerce'] ?? null;
        $seller = $data['seller'] ?? null;
        $serialText = 'RKE';

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

            if (isset($data['customer'])) {
                $serialText = Customer::getValueById($data['customer'], 'brand') ?? strtoupper(bin2hex(random_bytes(2)));
            }

            $data['jackpotType'] = $room->Config->jackpot_type;
            $data['initials'] = $serialText;

            if (($commerce || $seller)) {
                $data['playerLink'] = $player;
                // Eliminar Valores innecesarios
                unset($data['country'], $data['playerPhone'], $data['playerName'], $data['playerLastname']);
            }

            // Generar cartones
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
            Log::error('Error en handleCartonStore(): ' . $e->getMessage());
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
        $recordsToInsert = $playersToUpdate = [];

        foreach ($cartons as $ctn) {

            //Determinar la columna según el modo de juego
            $playerColumn = $ctn->player_user ? 'player_user' : 'player_link';

            $playerColumnVal = $ctn->player_user ?? $ctn->player_link;

            if (!BingoPrizeCtns::existsPrizeCtn($roomID, $prizeType, $playerColumn, $playerColumnVal)) {

                // Verificar y obtener el tipo de cambio
                $exchangeRate = GameExchangeRate::getGameExchangeRateValue('BINGO', $roomID, $ctn->coin) ?? 1;

                $prizeWn = rtrim(rtrim(number_format($USDValue * $exchangeRate, 3, '.', ''), '0'), '.');

                $timestamp = now();

                $record = [
                    'active' => 'N',
                    'room' => $roomID,
                    'room_user' => $ctn->room_user,
                    'customer' => $ctn->customer,
                    'commerce' => $ctn->commerce,
                    'seller' => $ctn->seller,
                    'player_user' => $ctn->player_user,
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
            Log::error('Error al crear ganador o actualizar estado: ' . $e->getMessage());
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
        $room = BingoRoom::where('id', $roomID)->where('status', '>=', 3)->exists();

        if (!$room) return ['Error' => 'Sala no disponible'];

        // Definir $prizeType
        $prizeType = $prizeType === 'JACKPOT' ? 1 : 2;

        // Obtener todos los ganadores por tipo de premio 
        $winners = BingoRoomUser::with(['PrizeCtns:customer,room_user,prize_type,ctn'])
            ->whereHas('PrizeCtns', function ($query) use ($prizeType) {
                $query->where('prize_type', $prizeType);
            })->where('room', $roomID)->where('winner', '>', 0)->get();

        if ($roomUser) {
            // Obtener el creador de la sala
            $creator = BingoRoomUser::where('id', $roomUser)
                ->whereNotNull('customer')->exists() ? 'customer' : 'commerce';

            // Obtener la moneda del jugador
            $coin = $this->getCoinByPlayer($creator, $roomUser);
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
        // Calcular la ganancia de acuerdo a la cantidad de ganadores
        $decimals = count($winners) > 1 ? 3 : 2;
        $gain = rtrim(number_format($jackpot / max(1, count($winners)), $decimals, ',', '.'), ',0');

        // Inicializar el array para guardar información
        $infoPlayer = ['size' => 0, 'name' => [], 'gain' => [], 'ctn' => []];

        // Función para agregar la información de un jugador
        $addPlayerInfo = function ($player) use (&$infoPlayer, $gain) {
            $infoPlayer['name'][] = BingoCtns::getCtnSerialByRoomUser($player->id);
            $infoPlayer['gain'][] = $gain;
            $infoPlayer['ctn'][] = encrypt(optional($player->PrizeCtns[0])->ctn);
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
        $room = BingoRoom::with((['Config:room,val_serials']))
            ->select('id', 'customer', 'jackpot', 'jackpot_prize', 'serial_prize')
            ->where('id', $roomID)->firstOrFail();

        // Verificar si ya hay ganadores del premio mayor
        $prizeCtns = BingoPrizeCtns::getWinnersCountByPrizeType($roomID, 1);
        $winners = BingoRoomUser::getWinnersCountByRoom($roomID);

        if ($prizeCtns && $winners) return false; // Ya existen cartones premiados (jackpot)

        // Obtener el último chip sorteado
        $lastChip = BingoDraw::select('id', 'chip')->where('win', 1)->where('room', $roomID)->first();

        // Obtener todos los chips sorteados hasta el último
        $chipsDraw = BingoDraw::where('room', $roomID)
            ->where('id', '<=', $lastChip->id)
            ->pluck('chip')
            ->toArray();

        // Declarar ultima ficha
        $lastChipNum = $lastChip->chip;

        // Inicializar array de jugadores únicos
        $uniquePlayers = ['playerUser' => [], 'playerLink' => []];

        // Buscar directamente los cartones que contengan el chip específico
        $ctnsPlayer = BingoCtns::where('room', $roomID)
            ->where(function ($query) use ($lastChipNum) {
                $query->where('b1', $lastChipNum)
                    ->orWhere('b2', $lastChipNum)
                    ->orWhere('b3', $lastChipNum)
                    ->orWhere('b4', $lastChipNum)
                    ->orWhere('b5', $lastChipNum)
                    ->orWhere('i1', $lastChipNum)
                    ->orWhere('i2', $lastChipNum)
                    ->orWhere('i3', $lastChipNum)
                    ->orWhere('i4', $lastChipNum)
                    ->orWhere('i5', $lastChipNum)
                    ->orWhere('n1', $lastChipNum)
                    ->orWhere('n2', $lastChipNum)
                    ->orWhere('n3', $lastChipNum)
                    ->orWhere('n4', $lastChipNum)
                    ->orWhere('g1', $lastChipNum)
                    ->orWhere('g2', $lastChipNum)
                    ->orWhere('g3', $lastChipNum)
                    ->orWhere('g4', $lastChipNum)
                    ->orWhere('g5', $lastChipNum)
                    ->orWhere('o1', $lastChipNum)
                    ->orWhere('o2', $lastChipNum)
                    ->orWhere('o3', $lastChipNum)
                    ->orWhere('o4', $lastChipNum)
                    ->orWhere('o5', $lastChipNum);
            })->select([
                'id',
                'player_user',
                'player_link',
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
            ])->cursor(); // Procesa en lotes pequeños

        // Filtrar cartones ganadores
        $prizeCtns = [];

        foreach ($ctnsPlayer as $ctn) {
            $ctnFull = collect($ctn)->except(['id', 'player_user', 'player_link'])->values()->toArray();

            // Verificar si todos los valores están en $chipsDraw
            if (empty(array_diff($ctnFull, $chipsDraw))) {
                $playerId = $ctn->player_user ?: $ctn->player_link;
                $playerType = $ctn->player_user ? 'playerUser' : 'playerLink';

                if (!in_array($playerId, $uniquePlayers[$playerType])) {
                    $uniquePlayers[$playerType][] = $playerId;
                    $prizeCtns[] = $ctn->id; // Guardar solo el ID del cartón ganador
                }
            }
        }

        // Obtener información de los cartones ganadores
        $ctnsJackpot = BingoCtns::where('room', $roomID)->whereIn('id', $prizeCtns)
            ->get(['id', 'customer', 'commerce', 'seller', 'room_user', 'player_user', 'player_link', 'coin', 'lot']);

        // Obtener la cantidad de ganadores
        $winnersCount = count(array_merge($uniquePlayers['playerUser'], $uniquePlayers['playerLink']));

        $nonWinnersCount = BingoRoomUser::getNonWinnersCountByRoom($roomID);

        $generateSerials = $nonWinnersCount - count($prizeCtns);

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

            // Consultar si hay cartones premiados registrados
            if (!BingoPrizeCtns::existsPrizeCtns($roomID, 'JACKPOT')) {
                // Calcular el valor de cada premio individual
                $winnerJackpotPrize = $jackpotPrize / $winnersCount;
                // Crear registros de ganadores y actualizar el estado de los jugadores
                $registerPrizeCtn = $this->registerPrizeCtn($roomID, $ctnsJackpot, 1, $winnerJackpotPrize);

                if (!$registerPrizeCtn) DB::rollBack();
            }

            // Generar seriales ganadores si hay jugadores no ganadores
            if ($generateSerials && !BingoPrizeCtns::existsPrizeCtns($roomID, 'SERIAL')) {
                $this->handleSerialWinners($roomID); // Ejecutar Obtener Seriales Ganadores
            }
            // Cambiar Status Premios Pagados
            BingoRoom::where('id', $roomID)->update(['paid_win' => 1]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error en handleJackpotWinners: ' . $e->getMessage());
            // Devolver un mensaje de error
            return response()->json(['error' => 'Hubo un error al procesar la solicitud.'], 500);
        }

        // Validar Transacciones
        if ($room->customer) {
            app(HandleTransactions::class)->validateTransactions($room->customer);
        }
    }

    public function getWinnersJackpot($roomID, $roomUser)
    {
        $data = $this->getWinnersData($roomID, 'JACKPOT', $roomUser);

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
        $lastChip = BingoDraw::where('room', $roomID)->where('win', 1)->value('id');

        $chipsDraw = array_flip(BingoDraw::where('room', $roomID)
            ->where('id', '<', $lastChip)->pluck('chip')->toArray());

        // Obtener los IDs de los cartones ganadores del Jackpot
        $ctnsID = BingoPrizeCtns::getWinnersIDsByPrizeType($roomID, 1, 'ctn');

        // Traer los cartones sin cargarlos todos en memoria al mismo tiempo
        $ctnsPlayer = BingoCtns::select([
            'id',
            'player_user',
            'player_link',
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
        ])->where('room', $roomID)->whereNotIn('id', $ctnsID)->cursor();
        // Usar cursor para evitar cargar todos los registros en memoria

        // Obtener la cantidad de seriales permitidos para la sala
        $countSerials = BingoRoomConfig::getValueByRoom($roomID, 'num_serials');

        // Inicializar variables
        $countCtns = 0;
        $uniquePlayers = [];
        $serialCtns = [];

        // Filtrar los cartones
        foreach ($ctnsPlayer as $ctn) {
            // Detener el filtrado cuando se alcanza el límite de seriales
            if ($countCtns >= $countSerials) {
                break;
            }

            // Extraer los valores del cartón directamente
            $ctnFull = collect($ctn)->except(['id', 'player_user', 'player_link'])->values()->toArray();

            // Contar los aciertos
            $count = 0;
            foreach ($ctnFull as $chip) {
                if (isset($chipsDraw[$chip])) {
                    $count++;
                }
            }

            // Si hay 23 aciertos y el jugador es único, incluirlo en los ganadores
            if ($count === 23) {
                $playerId = $ctn->player_user ?: $ctn->player_link;
                $playerType = $ctn->player_user ? 'playerUser' : 'playerLink';

                if (!in_array($playerId, $uniquePlayers[$playerType])) {
                    $uniquePlayers[$playerType][] = $playerId; // Añadir a la lista de jugadores únicos
                    $serialCtns[] = $ctn->id;
                    $countCtns++; // Incrementar el contador de cartones
                }
            }
        }

        // Si no hay ganadores con 23 aciertos
        if ($countCtns === 0) {
            return false;
        }

        // Aquí puedes continuar con el procesamiento de los cartones ganadores

        $ctnsSerials = BingoCtns::whereIn('id', $serialCtns)
            ->where('room', $roomID)->get(['id', 'commerce', 'seller', 'room_user', 'player_user', 'player_link', 'coin', 'lot']);

        $winnerJackpotPrize = BingoRoom::getSerialPrize($roomID) / count($serialCtns);

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
        $data = $this->getWinnersData($roomID, 'SERIAL', $roomUser);

        // Si existe algun error
        if (isset($data['Error'])) return $data['Error'];

        // Obtener tasa de cambio de la sala
        $gameExchangeRate = GameExchangeRate::getGameExchangeRateValue('BINGO', $roomID, $data['coin']);

        // Calcular el bote de seriales de la sala con respecto a la moneda del jugador
        $serialAmount = $this->formatAmount($data['serialPrize'] * $gameExchangeRate, 'float');

        return $this->getArrayWinnersData($data['winPlayer'], $data['winners'], $serialAmount);
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
