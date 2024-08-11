<?php

namespace App\Traits;

use App\Events\Games\BingoOFF_ProccessDraw;
use App\Models\SellerRoomLimits;
use App\Models\BingoOffCtns;
use App\Models\BingoOffRoom;
use App\Models\BingoOffSort;
use App\Models\BingoOffSortCode;
use App\Models\BingoOffCtnsLot;
use App\Models\BingoOffCtnsPayment;
use App\Models\BingoOffPrizeCtns;
use App\Models\BingoOffPrizeCtnsPayment;
use App\Models\BingoOffRoomConfig;
use App\Models\BingoOffRoomUser;
use App\Models\Coin;
use App\Models\CoinGameRoom;
use App\Models\GameExchangeRate;
use App\Models\ExchangeRate;
use App\Models\PlayerLink;
use App\Models\Seller;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait BingoTrait
{
    use UserVal, EncryptRKE, GameTrait, PlayerLinkTrait;

    public function newSortOff($room, $PJLinkWin = '')
    {
        date_default_timezone_set('America/Caracas');

        $sort = BingoOffSort::getRoomSort($room);
        if (!count($sort)) {
            $this->encryptSort('bingo_off_sort_code', $room);

            $roomInfo = BingoOffRoom::getRoomById($room, ['date', 'time']);
            $dataTime = strtotime($roomInfo->date . ' ' . $roomInfo->time);

            // Obtener los símbolos de los números de la sala actual
            $symbols = BingoOffSortCode::where('room', $room)->pluck('sym', 'num')->toArray();

            // Obtener los números de chip ya existentes en la sala
            $existingChips = BingoOffSort::where('room', $room)->pluck('chip')->toArray();

            $chips = [];
            $chp = 1;
            while ($chp <= 75) {
                do {
                    $rnd = mt_rand(1, 75);
                } while (in_array($rnd, array_merge($chips, $existingChips)));

                $numEncoded = '';
                foreach (str_split($rnd) as $digit) {
                    $num_code = ($digit == '0') ? 10 : $digit;
                    $numEncoded .= $symbols[$num_code] ?? '';
                }

                $sort = BingoOffSort::where('room', $room)->orderByDesc('id')->first();

                BingoOffSort::create([
                    "chip" => $rnd,
                    "code" => $numEncoded,
                    "room" => $room,
                    "time" => ($sort ? $sort->time : $dataTime) + ($sort ? 8 : 12)
                ]);

                $chips[] = $rnd;
                $chp++;
            }
            return $this->chipWinOff($room);
        } else if (count($sort) && $PJLinkWin && $this->user()->user_type == 2) {

            $ctn = BingoOffCtns::where('room', $room)->where('player_link', $PJLinkWin)->inRandomOrder()->first();
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

            $sort = BingoOffSort::where('room', $room)->whereNotIn('chip', $ctnFull)->inRandomOrder()->pluck('chip')->toArray();

            $firstFour = array_slice($sort, 0, 4); // Obtener los primeros 4 elementos
            $sort = array_slice($sort, 4); // Eliminar los primeros 4 elementos del array original

            $newSort = array_merge($firstFour, $ctnFull, $sort); // Nuevo Sort

            $newSortValues = array_map(function ($chip) {
                return intval($chip);
            }, $newSort);

            $OrigSort = BingoOffSort::where('room', $room)->pluck('id');

            // Construir un arreglo asociativo de ID => Chip para actualizar
            $updateData = array_combine($OrigSort->toArray(), $newSortValues);

            // Construir la consulta SQL dinámica para actualizar los chips en función de sus IDs
            $sql = 'UPDATE bingo_off_sort SET chip = CASE id ';
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
            return true;
        }

        $winChip = BingoOffSort::getWinningChip($room);
        if (!$winChip) return $this->chipWinOff($room);
    }

    public function chipWinOff($room)
    {
        date_default_timezone_set('America/Caracas');

        DB::beginTransaction();

        try {
            // Actualizar el estado de las fichas que cumplen cierta condición
            BingoOffSort::where('win', 1)->where('room', $room)->update(['status' => NULL]);

            $allCartonsValues = array_map('array_values', BingoOffCtns::where('room', $room)
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
                $firstChip = BingoOffSort::getFirstSortChip($room, 'id');
                $winChip = $firstChip->id + 23;

                BingoOffSort::where('id', $winChip)->where('room', $room)->update(['win' => 1]);
                DB::commit();
                return BingoOffSort::where('id', $winChip)->where('room', $room)->first();
            }

            // Buscar la primera ficha común
            $chipsVan = [];
            // Obtener todas las fichas ordenadas de forma descendente
            $sortDesc = BingoOffSort::where('room', $room)->orderByDesc('id')->pluck('chip');

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

            $sortAsc = BingoOffSort::where('room', $room)->pluck('chip');
            $chipVan = $sortAsc->first(function ($value) use ($chipsVan) {
                return in_array($value, $chipsVan);
            });

            if ($chipVan) {
                BingoOffSort::where('chip', $chipVan)->where('room', $room)->update(['win' => 1]);

                $exchangeRate = $this->insertGameExchangeRate($room, 1); // Registrar Tasas de Cambio
                if (!$exchangeRate) DB::rollBack();

                DB::commit();
                return BingoOffSort::where('chip', $chipVan)->where('room', $room)->first();
            }
        } catch (\Throwable $th) {
            DB::rollBack();
        }
    }

    public function recorrersorteo($room)
    {
        date_default_timezone_set('America/Caracas');

        $encrypted = BingoOffRoom::getValueById($room, 'encrypted');

        $result = false;
        do {
            $chip = BingoOffSort::select('id', 'chip', 'status', 'win')->where('room', $room)
                ->where('time', '<=', time())->where('status', 1)->first();

            if ($chip->win == 1) $result = $this->getInfoWinners($room);
            event(new BingoOFF_ProccessDraw($encrypted, $chip, $result));

            $chip->update(['status' => 2]);

            if ($chip->win == 1) BingoOffRoom::where('id', $room)->update(['status' => 4]);
        } while (BingoOffRoom::where('id', $room)->where('status', 3)->exists());
    }

    public function getInfoWinners($room, $PJLink = '')
    {
        date_default_timezone_set('America/Caracas');

        $room = BingoOffRoom::getRoomByEncrypted($room, ['id', 'date', 'time', 'status', 'encrypted']);

        $startTime = $this->convertToUnixTimestamp($room->date, $room->time);

        if ($room->status == 3 && $startTime <= time()) {
            BingoOffRoom::where('id', $room->id)->update(['status' => 4]);
        }

        $getWinnersJackpot = BingoOffPrizeCtns::getWinnersCountByPrizeType($room->id, 1);

        if (!$getWinnersJackpot && $room->status == 4) $this->findWinnersJackpot($room->id);

        $jackpotWinners = $this->getWinnersJackpot($room->id, $PJLink);
        $serialsWinners = (BingoOffPrizeCtns::getWinnersCountByPrizeType($room->id, 2)) ? $this->getWinnersSerials($room->id, $PJLink) : [];
        $prizeSerialVal = BingoOffRoomConfig::getValueByRoom($room->id, 'val_serials');

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

    public function ctnStoreOffline($arrCtn)
    {
        date_default_timezone_set('America/Caracas');

        try {
            $room = BingoOffRoom::select(
                'id',
                'provider',
                'commerce',
                'players',
                'ctn_price',
                'all_sale',
                'commerce_commn',
                'seller_commn',
                'jackpot'
            )->where('status', 2)->where('id', $arrCtn['room'])->first();

            if (!$room) return redirect()->back();

            $roomId = $room->id;
            $provider = $arrCtn['provider'];
            $commerce = $arrCtn['commerce'];
            $seller = $arrCtn['seller'];
            $coin = $arrCtn['coin'];
            $numOfCtns = $arrCtn['numOfCtns'];
            $PJLink = $arrCtn['PJLink'];

            // Obtener o crear la comisión del vendedor para la sala
            $commnSeller = SellerRoomLimits::getGameRoomValueBySeller(1, $seller, $roomId, 'commn');
            if (!$commnSeller) {
                $commnSeller = Seller::getValueById($seller, 'commn');

                SellerRoomLimits::create([
                    'game' => 1, // Bingo Off
                    'room' => $roomId,
                    'seller' => $seller,
                    'commn' => $commnSeller
                ]);
            }

            // Conversión de Moneda para obtener valor de venta
            $exchangeRate = ExchangeRate::getLastValueByCoin($coin);
            $totalSaleCtns = $numOfCtns * $exchangeRate;
            $comSaleSellerCtns = number_format($totalSaleCtns * ($commnSeller / 100), 2, '.', '');
            $saleSellerCtns = $totalSaleCtns - $comSaleSellerCtns;

            // Crear lote de cartones de bingo
            $ctnsLote = BingoOffCtnsLot::create([
                'provider' => $provider,
                'commerce' => $commerce,
                'seller' => $seller,
                'room' => $roomId,
                'coin' => $coin,
                'exchange_rate' => $exchangeRate,
                'player_link' => $PJLink,
                'status' => 0,
                'ctns' => $numOfCtns,
                'sale_seller_ctns' => $saleSellerCtns,
            ]);

            $idLote = $ctnsLote->id;

            // Obtener cartones existentes para verificación de duplicados
            $existingCtns = BingoOffCtns::where('room', $roomId)->get()->map(function ($val) {
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
            $numLastCtn = BingoOffCtns::getLastCtnCreated();
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
                        'room' => $roomId,
                        'player_link' => $PJLink,
                        'provider' => $provider,
                        'commerce' => $commerce,
                        'seller' => $seller,
                        'coin' => $coin,
                        'lot' => $idLote,
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

            $res = true;
            if (!$res) DB::rollBack();
            // Insertar todos los registros en una sola consulta
            BingoOffCtns::insert($recordsToInsert);

            // Registrar usuario en la sala si no está ya registrado
            $roomUser = BingoOffRoomUser::firstOrCreate(
                ['room' => $roomId, 'player_link' => $PJLink],
                [
                    'provider' => $provider,
                    'commerce' => $commerce,
                    'seller' => $seller,
                    'room' => $roomId,
                    'player_link' => $PJLink
                ]
            );

            if ($roomUser->wasRecentlyCreated) {
                $room->increment('players');
            }

            // Actualización del bote acumulado y otras métricas de la sala
            $convertedJackpot = number_format($saleSellerCtns / $exchangeRate, 3, '.');
            $boatAcum = ($arrCtn['jackpotType'] == 1) ? $convertedJackpot : (($arrCtn['jackpotType'] == 2) ? 0 : null);
            $jackpot = $room->jackpot + $boatAcum;
            $comCommerce = ($jackpot * BingoOffRoomConfig::getValueByRoom($roomId, 'commerce_commn') / 100);
            $jackpot -= $comCommerce;

            $room->update([
                'all_sale' => $room->all_sale + ($numOfCtns * $room->ctn_price),
                'commerce_commn' => $room->commerce_commn + $comCommerce,
                'seller_commn' => $room->seller_commn + $comSaleSellerCtns,
                'jackpot' => $jackpot,
                'jackpot_prize' => $jackpot,
                'ctns' => $room->ctns + $numOfCtns
            ]);

            // Registrar Nueva Moneda en CoinsGameRoom si no existe
            if (!CoinGameRoom::checkCoinGameRoom($coin, $roomId, 1)) {
                CoinGameRoom::create(['game' => 1, 'room' => $roomId, 'coin' => $coin]);
            }

            if ($arrCtn['ctnWin'] == TRUE) $this->newSortOff($room, TRUE);

            return true;
        } catch (\Exception $e) {
            dd($e->getMessage());
            Log::error('Error al crear los cartones: ' . $e->getMessage());
            return false;
        }
    }

    public function convertPrizeValue($room, $coin, $type = 'string')
    {
        $status = BingoOffRoom::getValueById($room, 'status');

        $exchangeRate = ($status == 2)
            ? ExchangeRate::getLastValueByCoin($coin)
            : GameExchangeRate::getGameExchangeRateValue(1, $room, $coin);

        if (!$exchangeRate) $this->insertGameExchangeRate($room, 1);

        $jackpotPrize = BingoOffRoom::getValueById($room, 'jackpot_prize');
        $jackpot = $jackpotPrize * $exchangeRate;

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
        $existsGameExchangeRate = GameExchangeRate::getCountGameExchangeRate(1, $room);
        if (!$existsGameExchangeRate) $this->insertGameExchangeRate($room, 1);

        date_default_timezone_set('America/Caracas');

        try {
            $recordsToInsert = [];
            $playersToUpdate = [];

            foreach ($playerCtn as $ctn) {
                if (!BingoOffPrizeCtns::existsPrizeCtn($room, 1, $ctn->player_link)) {
                    $exchangeRate = GameExchangeRate::getGameExchangeRateValue(1, $room, $ctn->coin);
                    // Formatea `prizeWn` a dos decimales
                    $prizeWn = number_format($USDValue * $exchangeRate, 3, '.', '');
                    $timestamp = now();

                    $recordsToInsert[] = [
                        'room' => $room,
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

                    $playersToUpdate[] = $ctn->player_link;
                }
            }
            // Insertar los registros de una sola vez
            if (!empty($recordsToInsert)) {
                BingoOffPrizeCtns::insert($recordsToInsert);
            }

            // Verificar y actualizar el estado del jugador
            if (!empty($playersToUpdate)) {
                BingoOffRoomUser::where('room', $room)->whereIn('player_link', $playersToUpdate)->where('winner', 0)->update(['winner' => 1]);
            }

            return true;
        } catch (\Exception $e) {
            // Revertir la transacción en caso de error
            Log::error('Error al crear ganador o actualizar estado: ' . $e->getMessage());
            dd($e->getMessage());
            // Devolver el mensaje de error
            return false;
        }
    }

    public function findWinnersJackpot($room)
    {

        try {
            // Obtener la información de la sala
            $room = BingoOffRoom::with('Config')
                ->select('id', 'commerce', 'jackpot', 'jackpot_prize', 'serial_prize')
                ->where('id', $room)->firstOrFail();

            $roomId = $room->id; // ID Sala Bingo

            // Verificar si ya hay ganadores del premio mayor
            $getWinners = BingoOffPrizeCtns::getWinnersCountByPrizeType($roomId, 1);

            if ($getWinners) {
                DB::rollBack();
                return false;
            }
            // Obtener el último chip sorteado
            $chpVn = BingoOffSort::where('win', 1)->where('room', $roomId)->select('id', 'chip')->firstOrFail();

            // Obtener todos los chips sorteados hasta el último
            $chipSrt = BingoOffSort::where('room', $roomId)->where('id', '<=', $chpVn->id)->pluck('chip')->toArray();

            // Obtener todos los cartones de la sala
            $ctnsPlayer = BingoOffCtns::where('room', $roomId)->get();

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
            $playerCtn = BingoOffCtns::where('room', $roomId)->whereIn('id', $arrCtnWin)
                ->get(['id', 'coin', 'commerce', 'seller', 'player_link', 'lot']);

            // Obtener la cantidad de ganadores distintos
            $winnersCount = $filteredCtns->pluck('player_link')->unique()->count();

            $nonWinnersCount = BingoOffRoomUser::getNonWinnersCountByRoom($roomId);

            $generateSerials = $nonWinnersCount - count($arrCtnWin);

            // Calcular premios si hay premio serial
            if ($room->Config->val_serials > 0  && $room->serial_prize == 0 && $generateSerials > 0) {
                $serialPrize = $room->jackpot * $room->Config->val_serials / 100;
                $jackpotPrize = $room->jackpot - $serialPrize;

                BingoOffRoom::where('id', $roomId)->update([
                    'jackpot_prize' => $jackpotPrize,
                    'serial_prize' => $serialPrize,
                    'close_room' => Carbon::now('America/Caracas')->timestamp
                ]);
                $USDPrize = $jackpotPrize / $winnersCount;
            } else {
                $USDPrize = $room->jackpot_prize / $winnersCount;
            }

            // Crear registros de ganadores y actualizar el estado de los jugadores
            $registerPrizeCtn = $this->registerPrizeCtn($roomId, $playerCtn, 1, $USDPrize);
            if (!$registerPrizeCtn) DB::rollBack();

            // Generar seriales ganadores si hay jugadores no ganadores
            if ($generateSerials) {
                $this->generateWinningSerials($roomId); // Ejecutar Obtener Seriales Ganadores
            }

            DB::commit(); // Commit de la transacción si todo ha ido bien
        } catch (\Exception $e) {
            DB::rollBack();
            // Registrar el error
            Log::error('Error en findWinnersJackpot: ' . $e->getMessage());
            dd($e->getMessage());
            // Devolver un mensaje de error
            return response()->json(['error' => 'Hubo un error al procesar la solicitud.'], 500);
        }
    }

    public function getWinnersJackpot($room, $PJLink = "")
    {
        $room = BingoOffRoom::select('id', 'jackpot_prize')
            ->where('id', $room)->where('status', 4)->first();

        if ($room) {
            // Obtener todos los ganadores y verificar si el jugador específico está entre ellos
            $winners = BingoOffRoomUser::with('PlayerLink', 'WinCtns')
                ->whereHas('WinCtns', function ($query) {
                    $query->where('prize_type', 1);
                })->where('room', $room->id)->where('winner', '>', 0)->get();

            $numWinners = $winners->count(); // Contar todos los ganadores

            if ($numWinners == 0) $this->findWinnersJackpot($room);

            // Verificar si el jugador específico está entre los ganadores
            $winPlayer = $winners->firstWhere('player_link', $PJLink);

            // Obtener el país del jugador y la moneda correspondiente
            $playerCountry = PlayerLink::getValueById($PJLink, 'country');
            $coin = Coin::getCoinValueByCountry($playerCountry, 'id');
            $boat = $room->jackpot_prize * GameExchangeRate::getGameExchangeRateValue(1, $room->id, $coin);

            // Calcular la ganancia por jugador y formatearla
            $boat = $this->convertPrizeValue($room->id, $coin, 'float');
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
            foreach ($winners as $value) {
                // Evitar añadir el jugador específico de nuevo si ya está en la lista de ganadores
                if (!$winPlayer || $value->player_link != $winPlayer->player_link) {
                    $infoPlayer['size'] += 1;
                    $playerLastName = $this->initialCharacters($value->PlayerLink->lastname);
                    $infoPlayer['name'][] = $value->PlayerLink->name . ' ' . $playerLastName;
                    $infoPlayer['gain'][] = $gain;
                    $infoPlayer['ctns'][] = $value->PlayerLink->WinCtns[0]->ctn;
                }
            }

            return $infoPlayer;
        }
    }

    public function generateWinningSerials($room) // Obtener Seriales Ganadores
    {
        $getWinners = BingoOffPrizeCtns::getWinnersCountByPrizeType($room, 2);
        if ($getWinners) return false;

        $chpVn = BingoOffSort::where('room', $room)->where('status', 1)->value('id');

        $chipSrt = BingoOffSort::where('room', $room)->where('id', '<', $chpVn)->pluck('chip')->toArray();
        $ctnsPlayer = BingoOffCtns::where('room', $room)->get();

        $countSerials = BingoOffRoomConfig::getValueByRoom($room, 'num_serials');

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

            $existsPrizeCtn = BingoOffPrizeCtns::existsPrizeCtn($room, 1, $playerId);

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

        $ctnSerials = BingoOffCtns::whereIn('serial', $arrSerialsPlayers)
            ->where('room', $room)->get(['id', 'commerce', 'seller', 'player_link', 'coin', 'lot']);

        $USDPrize = BingoOffRoom::getSerialPrize($room) / count($arrSerialsPlayers);
        try {
            $registerPrizeCtn = $this->registerPrizeCtn($ctnSerials, 2, $USDPrize, $room);
            if (!$registerPrizeCtn) DB::rollBack();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al crear ganador o actualizar estado: ' . $e->getMessage());
            return response()->json(['error' => 'Hubo un error al procesar la solicitud.'], 500);
        }
    }

    public function getWinnersSerials($room, $PJLink = "") // Obtener Seriales Ganadores
    {
        $room = BingoOffRoom::select('id', 'serial_prize')
            ->where('id', $room)->where('status', 4)->first();

        if ($room) {
            // Obtener todos los ganadores y verificar si el jugador específico está entre ellos
            $winners = BingoOffRoomUser::with('PlayerLink')
                ->whereHas('PlayerLink.WinCtns', function ($query) {
                    $query->where('prize_type', 2); // (2) Seriales
                })->where('room', $room->id)->where('winner', '>', 0)->get();

            $numWinners = $winners->count(); // Contar todos los ganadores

            // Verificar si el jugador específico está entre los ganadores
            $winPlayer = $winners->firstWhere('player_link', $PJLink);

            // Obtener el país del jugador y la moneda correspondiente
            $playerCountry = PlayerLink::getValueById($PJLink, 'country');
            $coin = Coin::getCoinValueByCountry($playerCountry, 'id');
            $boat = $room->serial_prize * GameExchangeRate::getGameExchangeRateValue(1, $room->id, $coin);
            // Calcular la ganancia por jugador y formatearla
            $formattedNumber = number_format($boat / $numWinners, 3, ',', '.');
            $gain = rtrim(rtrim($formattedNumber, '0'), ',');

            // Preparar la información del jugador
            $infoPlayer = ['size' => 0, 'name' => [], 'foto' => [], 'gain' => [], 'ctns' => []];

            // Añadir información del jugador específico es un ganador
            if ($winPlayer) {
                $infoPlayer['size'] = 1;
                $infoPlayer['name'][] = BingoOffCtns::getCtnSerialByPlayerInRoom($room->id, $winPlayer->PlayerLink->id);
                $infoPlayer['gain'][] = $gain;
                $infoPlayer['ctns'][] = $winPlayer->PlayerLink->WinCtns[0]->ctn;
            }

            // Añadir información de todos los demás ganadores
            foreach ($winners as $value) {
                // Evitar añadir el jugador específico de nuevo si ya está en la lista de ganadores
                if (!$winPlayer || $value->player_link != $winPlayer->player_link) {
                    $infoPlayer['size'] += 1;
                    $infoPlayer['name'][] = BingoOffCtns::getCtnSerialByPlayerInRoom($room->id, $value->player_link);
                    $infoPlayer['gain'][] = $gain;
                    $infoPlayer['ctns'][] = $value->PlayerLink->WinCtns[0]->ctn;
                }
            }
            return $infoPlayer;
        }
    }

    public function getCtnWinOff($ctn)
    {
        $ctn = BingoOffCtns::with('PlayerLink')->where('id', $ctn)->first();

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

    public function checkRoomClosure()
    {
        date_default_timezone_set('America/Caracas');

        $rooms = BingoOffRoom::whereIn('status', [2, 3, 4])->get(['id', 'date', 'time']);
        foreach ($rooms as $room) {
            $triviaInit = strtotime($room->date . ' ' . $room->time);
            if (time() >= $triviaInit) {
                $room = $room->id;
                $existsCtns = BingoOffCtns::getCountCtnByRoom($room);
                if ($existsCtns) {
                    $winChip = BingoOffSort::getWinningChip($room);
                    if (!$winChip) $winChip = $this->newSortOff($room);

                    $status = ($winChip->time <= time()) ? 4 : 3;
                } else {
                    $status = 1; //N/D Borrador
                }
                BingoOffRoom::where('id', $room)->update(['status' => $status]);
            }
        }
        $this->hideRoomOff();
    }

    public function hideRoomOff()
    {
        date_default_timezone_set('America/Caracas');

        $rooms = BingoOffRoom::where('status', 4)->get(['id', 'date', 'time']);
        if (count($rooms)) {
            foreach ($rooms as $value) {
                $dataTime = strtotime($value->date . ' ' . $value->time);
                // Sumar 2 semanas (14 días * 24 horas * 60 minutos * 60 segundos)
                $dataTime2Week = $dataTime + (2 * 7 * 24 * 60 * 60);

                if ($dataTime2Week <= time()) {
                    BingoOffRoom::where('id', $value->id)->update(['status' => 6]);
                }
            }
        }
        return true;
    }

    public function getBingoInfoByDateTrait($commerce, $date = 'today') {}

    public function allWinCtnPaid($room)
    {
        // Verificar la cantidad total de cartones premiados y la cantidad pagada
        $winnersCount = BingoOffRoomUser::where('room', $room)->where('winner', '>', 0)->count();
        $winnersPaidCount = BingoOffRoomUser::where('room', $room)->where('winner', 2)->count();

        // Si la cantidad total de cartones premiados es igual a la cantidad pagada,
        // entonces todos los cartones premiados han sido pagados
        if ($winnersCount == $winnersPaidCount && $winnersCount > 0) {
            // Actualizar el estado de pago de la sala de bingo
            BingoOffRoom::where('id', $room)->update(['paid_win' => 1]);
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
        return BingoOffCtnsPayment::insert($data);
    }

    function registerCartonPayment($operation, $room, $commerce, $seller, $playerLink, $paymentType, $lot, $coin, $amount, $paymentImage = '')
    {
        date_default_timezone_set('America/Caracas');

        return BingoOffCtnsPayment::create([
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

        return BingoOffPrizeCtnsPayment::create([
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
