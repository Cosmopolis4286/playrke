<?php

namespace App\Http\Controllers\Roles;

use App\Contracts\CurrencyRepositoryInterface;
use App\Contracts\Roles\Commerce\CommerceExchangeRateRepositoryInterface;
use App\Domains\Games\Bingo\Models\Card\CardPrice\BingoCardPrice;
use App\Domains\Games\Bingo\Models\Creator\BingoCreator;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Models\Room\BingoRoomConfig;
use App\Domains\Games\Bingo\Models\Room\User\BingoRoomUser;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Pivots\Roles\Seller\SellerCurrency;
use App\Models\Roles\Commerce;
use App\Models\Roles\Player\External\ExternalPlayer;
use App\Models\Roles\Seller;
use App\Traits\Helpers\EncryptTrait;
use App\Traits\Helpers\FiltersTrait;
use App\Traits\Users\UserTrait;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Http\Controllers\Controller;

class SellerController extends Controller
{
    use EncryptTrait, UserTrait, FiltersTrait;

    public function __construct(
        private readonly CurrencyRepositoryInterface $currencies,
        private readonly CommerceExchangeRateRepositoryInterface $commerceExchangeRates,
    ) {}

    public function getAvailableCountriesBySeller($roomId)
    {
        $seller = $this->seller(['id', 'manager_user_id']);
        $commerceId = Commerce::where('user_id', $seller->manager_user_id)->value('id');

        // Obtenemos las monedas disponibles para el vendedor y el comercio
        $sellerCurrencies = SellerCurrency::getAvailableCurrencies($seller->id);
        $commerceCurrencies = $this->commerceExchangeRates->getCurrenciesByCommerce($commerceId, 'ACTIVE');

        // Monedas permitidas en la sala (bingo_rooms_config.allowed_currencies, no en BingoCreator)
        $allowedCurrenciesRoom = BingoRoomConfig::where('room_id', $roomId)->value('allowed_currencies') ?? [];
        $allowedCurrenciesRoom = is_array($allowedCurrenciesRoom) ? array_map('strval', $allowedCurrenciesRoom) : [];

        $currenciesAvailable = array_values(array_intersect(
            array_map('strval', $sellerCurrencies),
            array_map('strval', $commerceCurrencies),
            $allowedCurrenciesRoom
        ));

        // Eliminamos '1' si existe
        $USDT = ($key = array_search('1', $currenciesAvailable, true)) !== false;
        if ($USDT) {
            unset($currenciesAvailable[$key]);
            $currenciesAvailable = array_values($currenciesAvailable);
        }

        $countryIds = Currency::whereIn('id', $currenciesAvailable)->pluck('country_id')->filter()->unique();

        $countries = Country::whereIn('id', $countryIds)->get(['id', 'name']);

        return ['countries' => $countries, 'USDT' => $USDT];
    }

    public function getCardPrice($roomId)
    {
        $room = BingoRoom::with('config:room_id,prize_pool_type_id,card_price_usd')
            ->select('id', 'prize_pool')
            ->find($roomId);

        if (!$room) {
            return response()->json(['type' => 'info', 'message' => 'Sala no encontrada']);
        }

        $countriesCurrencies = $this->getAvailableCountriesBySeller($room->id);

        return response()->json([
            'prizePoolType' => $room->config?->prize_pool_type_id,
            'prizePool' => $room->prize_pool,
            'currency' => 'USDT',
            'price' => $room->config?->card_price_usd,
            'countries' => $countriesCurrencies['countries'],
            'USDT' => $countriesCurrencies['USDT']
        ]);
    }

    public function getCardPriceCurrency($room, $country)
    {
        // Obtener moneda según el país
        $currency = $this->currencies->getCurrencyByCountry($country, ['id', 'symbol']);
        if (!$currency) {
            return response()->json(['error' => 'Moneda no encontrada'], 404);
        }

        // Obtener comercio asociado al usuario autenticado (vía manager_user_id → commerces.user_id)
        $managerUserId = Seller::getValueByUserId(Auth::id(), 'manager_user_id');
        $commerceId = $managerUserId ? Commerce::where('user_id', $managerUserId)->value('id') : null;
        if (!$commerceId) {
            return response()->json(['error' => 'Comercio no encontrado'], 404);
        }

        // Obtener tasa de cambio
        $exchangeRate = $this->commerceExchangeRates->getCommerceCurrencyValue($commerceId, $currency->id);

        $roomId = $room;

        $setPrice = false;
        $value = $exchangeRate;

        // Obtener el creador de la sala (comercio) para buscar precio personalizado
        $bingoCreatorID = BingoCreator::where('room_id', $roomId)
            ->where('user_id', $managerUserId)
            ->value('id');

        // Intentar obtener el precio configurado
        $cardPrice = BingoCardPrice::byRoom($roomId)
            ->byCurrency($currency->id)
            ->where('creator_user_id', $bingoCreatorID)
            ->value('card_price');

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
                $room = BingoRoom::select('id')->find($request->room);

                if (!$room) {
                    return response()->json(['response' => false]);
                }

                $player = ExternalPlayer::select('room_user_id', 'first_name', 'last_name', 'country_id', 'phone', 'link')
                    ->where('phone', $request->player_info)
                    ->where('game_id', 1)
                    ->where('room_id', $room->id)
                    ->first();

                if (!$player) return response()->json(['response' => false]);

                $route = $this->getGameLinkByPlayer($player->link);
                $cardsPlayer = BingoRoomUser::where('id', $player->room_user_id)->value('cards_purchased') ?? 0;

                return response()->json([
                    'response' => true,
                    'infoPlayer' => $player,
                    'cardsLink' => [$cardsPlayer, $player->link, $route]
                ]);

            default:
                return response()->json(['response' => false]);
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

    public function getPlayerInfo($playerLink) // Obtención de la información jugador
    {
        $player = ExternalPlayer::where('link', $playerLink)
            ->select(['identification', 'phone'])->first();
        if ($player) return json_encode($player);
    }
}
