<?php

namespace App\Http\Controllers\Roles;

use App\Console\Commands\HandleExchangeRatesCommand;
use App\Models\BingoRoom;
use App\Models\BingoCtns;
use App\Models\BingoCtnsLot;
use App\Models\ExternalPlayers;
use App\Models\Seller;
use App\Models\DocumentType;
use App\Traits\Helpers\EncryptTrait;
use App\Traits\Games\Bingo\BingoTrait;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\BingoCreator;
use App\Models\Games\Bingo\Card\CardPrice\BingoCardPrice;
use App\Models\BingoCtnsPayment;
use App\Models\BingoPrizeCtns;
use App\Models\Currency;
use App\Models\Commerce;
use App\Models\Roles\Commerce\CommerceExchangeRate;
use App\Models\Country;
use App\Models\SellerCurrency;
use App\Services\Games\Bingo\HandleBingoRooms;
use App\Traits\Helpers\FiltersTrait;
use App\Traits\Users\UserTrait;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Facades\Storage;

class SellerController extends Controller
{
    use EncryptTrait, BingoTrait,  UserTrait, FiltersTrait;

    public function getAvailableCountriesBySeller($roomId)
    {
        $seller = $this->seller(['id', 'commerce_id']);
        $creatorUser = Commerce::getValueById($seller->commerce, 'user');

        // Obtenemos las monedas disponibles para el vendedor y el comercio
        $sellerCurrencies = SellerCurrency::getAvailableCurrencies($seller->id);
        $commerceCurrencies = CommerceExchangeRate::getCurrenciesByCommerce($seller->commerce, 'ACTIVE');

        // Obtenemos el campo allowed_currencies (json o null) y lo forzamos a array
        $allowedCurrenciesRoom = BingoCreator::where('creator_user_id', $creatorUser)
            ->where('room', $roomId)->value('allowed_currencies');

        $allowedCurrenciesRoom = is_array($allowedCurrenciesRoom) ? $allowedCurrenciesRoom : [];

        $currenciesAvailable = array_values(array_intersect(
            array_map('strval', $sellerCurrencies),
            array_map('strval', $commerceCurrencies),
            array_map('strval', $allowedCurrenciesRoom)
        ));

        // Eliminamos '1' si existe
        $USDT = ($key = array_search('1', $currenciesAvailable, true)) !== false;
        if ($USDT) {
            unset($currenciesAvailable[$key]);
            $currenciesAvailable = array_values($currenciesAvailable);
        }

        $countriesAvailable = Currency::getCountriesByCurrencyInArray($currenciesAvailable);

        $countries = Country::getCountries($countriesAvailable, ['id', 'name']);

        return ['countries' => $countries, 'USDT' => $USDT];
    }

    public function getCtnPrice($roomId)
    {
        $room = BingoRoom::with('Config:room,prize_pool_type,card_price')
            ->select('id', 'encrypted', 'prize_pool')
            ->where('encrypted', $roomId)->first();

        if (!$room) {
            return response()->json(['type' => 'info', 'message' => 'Sala no encontrada']);
        }

        $countriesCurrencies = $this->getAvailableCountriesBySeller($room->id);

        return response()->json([
            'prizePoolType' => $room->Config->prize_pool_type,
            'prizePool' => $room->prize_pool,
            'currency' => 'USDT',
            'price' => $room->Config->card_price,
            'countries' => $countriesCurrencies['countries'],
            'USDT' => $countriesCurrencies['USDT']
        ]);
    }

    public function getCtnPriceCurrency($room, $country)
    {
        // Obtener moneda según el país
        $currency = Currency::getCurrencyByCountry($country, ['id', 'symbol']);
        if (!$currency) {
            return response()->json(['error' => 'Moneda no encontrada'], 404);
        }

        // Obtener comercio asociado al usuario autenticado
        $commerce = Seller::getValueByUserId(Auth::id(), 'commerce_id');
        if (!$commerce) {
            return response()->json(['error' => 'Comercio no encontrado'], 404);
        }

        // Obtener tasa de cambio
        $exchangeRate = CommerceExchangeRate::getCommerceCurrencyValue($commerce, $currency->id);

        $roomId = BingoRoom::getValueByEncrypted($room, 'id');
        $creator = ['type' => 'commerce_id', 'id' => $commerce];

        $setPrice = false;
        $value = $exchangeRate;

        //Obtener $bingoCreatorID
        $bingoCreatorID = BingoCreator::getValueByCreatorRoom($creator, $roomId, 'id');

        // Intentar obtener el precio configurado
        $cardPrice = BingoCardPrice::getValueCtnPrice($roomId, $currency->id, $bingoCreatorID);

        if ($cardPrice) {
            $setPrice = true;
            $value = $cardPrice;
        }

        // Construir y devolver la respuesta
        return response()->json([
            'sym' => $currency->symbol,
            'exchangeRate' => $exchangeRate,
            'setPrice' => $setPrice,
            'value' => $value,
        ]);
    }

    public function getPlayer(Request $request)
    {
        switch ($request->game) {
            case 'BINGO':
                $room = BingoRoom::getByEncrypted($request->room, ['id', 'room_type']);

                $player = ExternalPlayers::select('room_user', 'name', 'lastname', 'country', 'phone', 'link')
                    ->where('phone', $request->player_info)->where('game', 1)->where('room', $room->id)->first();

                if (!$player)  return response()->json(['response' => false]);

                $route = $this->getGameLinkByPlayer($player->link);
                $ctnsPlayer = BingoCtns::where(['room_user' => $player->room_user, 'room' => $room->id])->count();

                return response()->json([
                    'response' => true,
                    'infoPlayer' => $player,
                    'ctnsLink' => [$ctnsPlayer, $player->link, $route]
                ]);

                break;

            default:
                break;
        }
    }

    public function getPaymentCommerceDetails($currency)
    {
        try {
            $user = Commerce::getValueById($this->seller('commerce_id')->commerce, 'user');

            // Obtener detalles de pago por comercio usando el ID del usuario autenticado y la moneda decriptada
            $data = $this->getPaymentDetailsByUserId($user, decrypt($currency));

            // Verificar si se obtuvieron datos y retornar una respuesta JSON adecuada
            if (!isset($data['error'])) {
                return response()->json($data);
            } else {
                return response()->json(['error' => 'El comercio no ha cargado los datos bancarios']);
            }
        } catch (\Exception $e) {
            // Manejar cualquier excepción que ocurra y retornar un error del servidor
            return response()->json(['error' => 'Ha ocurrido un error al procesar la solicitud.'], 500);
        }
    }

    public function getWinnerInfoForCtn($ctnId)
    {
        $ctnId = decrypt($ctnId);
        $roomUser = BingoPrizeCtns::getValueById($ctnId, 'room_user');
        $player = ExternalPlayers::getByRoomUser('BINGO', $roomUser, ['name', 'lastname', 'phone']);

        return json_encode(array(
            'name' => $player->name,
            'lastname' => $player->lastname,
            'phone' => $player->phone
        ));
    }

    public function getPlayerInfo($playerLink) // Obtención de la información jugador
    {
        $player = ExternalPlayers::where('link', $playerLink)
            ->select(['type_id', 'identification', 'phone'])->first();
        if ($player) return json_encode($player);
    }

    public function ctnsLotPayment(Request $request)
    {
        DB::beginTransaction();

        try {
            $seller = $this->seller()->id;

            // Buscar el lote
            $lotCtn = BingoCtnsLot::where('id', $request->lot)->where('seller', $seller)->firstOrFail();

            $notifyAllCtns = $request->notify_all_ctns;

            if (!$lotCtn && !$notifyAllCtns) {
                return redirect()->back();
            }

            $filePayment = $request->file('payment_proof');
            $newFileName = FileController::saveImage($filePayment, 'game/bingo/payments/all-ctns', 'PRIVATE');

            if (!$newFileName) {
                DB::rollBack();
                return redirect()->back()->with(['type' => 'warning', 'message' => 'Error al guardar el archivo del pago']);
            }

            if ($notifyAllCtns) {
                $notifyCtns = $this->notifyAllPendingLotCtns($seller, $newFileName);
            } else {
                $notifyCtns = $this->notifySpecificLotCtn($lotCtn, $newFileName);
            }

            if (!$notifyCtns) {
                Storage::delete('private/game/bingo/payments/all-ctns/' . $newFileName);
                DB::rollBack();

                Session::flash('type', 'warning');
                Session::flash('message', 'Error al notificar el pago');
            } else {
                DB::commit();
                Session::flash('message', 'Pago notificado con éxito');
            }
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al notificar el pago de la venta de cartones ctnsLotPayment(): ' . $e->getMessage());
        }

        return redirect()->back();
    }

    private function notifyAllPendingLotCtns($seller, $paymentProof)
    {
        $allPendingCtns = BingoCtns::with('Lot:id,ctns,sale_amount')
            ->whereHas('Lot', function ($query) use ($seller) {
                $query->where(['seller' => $seller, 'status' => 0]);
            })->where('seller', $seller)->get(['room', 'creator_user_id', 'room_user', 'currency', 'lot']);

        try {

            $paymentToInsert = [];
            $timestamp = now();

            foreach ($allPendingCtns as $ctn) {

                $cardPrice = $ctn->Lot->sale_amount / $ctn->Lot->ctns;
                $playerLink = ExternalPlayers::getValueByRoomUser('BINGO', $ctn->room_user, 'id');

                $paymentToInsert[] = [
                    'payment' => 'SC',
                    'room' => $ctn->room,
                    'creator_user_id' => $ctn->creator_user,
                    'seller' => $seller,
                    'external_player_id' => $playerLink,
                    'lot' => $ctn->lot,
                    'currency' => $ctn->currency,
                    'amount' => $cardPrice,
                    'payment_proof' => $paymentProof,
                    'created_at' => $timestamp,
                    'updated_at' => $timestamp
                ];
            }

            BingoCtnsPayment::insert($paymentToInsert);

            BingoCtnsLot::where('seller', $seller)->where('status', 0)->update(['status' => 1]);
            return true;
        } catch (\Exception $e) {
            Log::error('Error al registrar y/o notificar la creacion y venta de todos los lotes de cartones: ' . $e->getMessage());
            return false;
        }
    }

    private function notifySpecificLotCtn($lotCtn, $paymentProof)
    {
        try {
            $this->registerCartonPayment('SC', $lotCtn, $paymentProof);

            BingoCtnsLot::where('id', $lotCtn->id)->where('status', 0)->update(['status' => 1]);
            return true;
        } catch (\Exception $e) {
            Log::error("Error al registrar y/o notificar la creacion venta del lote de cartones LOTE [$lotCtn->id] ::: " . $e->getMessage());
            return false;
        }
    }
}
