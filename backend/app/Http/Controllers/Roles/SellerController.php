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
use App\Services\Roles\Player\External\ExternalPlayerLinkService;
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
        private readonly ExternalPlayerLinkService $externalPlayerLink,
    ) {}

    public function getAvailableCountriesBySeller($roomId)
    {
        $seller = $this->seller(['id', 'manager_user_id', 'subsidiary_id']);
        $commerceId = $seller->resolveCommerce()?->id;

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

        // Obtener comercio real asociado al vendedor autenticado — resuelve
        // directo (manager_user_id → Commerce) o vía agencia intermedia
        // (subsidiary_id → Subsidiary → Commerce), ver Seller::resolveCommerce().
        $seller = Seller::where('user_id', Auth::id())->first(['id', 'manager_user_id', 'subsidiary_id']);
        $commerce = $seller?->resolveCommerce();
        $commerceId = $commerce?->id;
        if (!$commerceId) {
            return response()->json(['error' => 'Comercio no encontrado'], 404);
        }

        // Obtener tasa de cambio
        $exchangeRate = $this->commerceExchangeRates->getCommerceCurrencyValue($commerceId, $currency->id);

        $roomId = $room;

        $setPrice = false;
        $value = $exchangeRate;

        // Obtener el creador de la sala (comercio) para buscar precio personalizado
        // — usa el user_id del Commerce resuelto, no manager_user_id crudo:
        // cuando el seller pertenece a una Subsidiary, manager_user_id apunta
        // al usuario de la agencia, no al del Commerce dueño de la sala.
        $bingoCreatorID = BingoCreator::where('room_id', $roomId)
            ->where('user_id', $commerce->user_id)
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
                // Scope por comercio: antes cualquier seller autenticado
                // podía consultar datos de contacto de un jugador captado
                // por OTRO vendedor con solo conocer/adivinar el room_id de
                // una sala ajena — mismo patrón de fuga ya corregido en
                // BingoReportController (accessibleBy + resolveCommerce()).
                $seller = $this->seller(['id', 'manager_user_id', 'subsidiary_id']);
                $commerceUserId = $seller?->resolveCommerce()?->user_id;

                if (!$commerceUserId) {
                    return response()->json(['response' => false]);
                }

                // Comercializadora + agencia del vendedor (Seller::roomOwnerUserIds()).
                $room = BingoRoom::select('id')
                    ->accessibleBy($seller->roomOwnerUserIds())
                    ->find($request->room);

                if (!$room) {
                    return response()->json(['response' => false]);
                }

                // accessibleBy() valida que el seller pueda VENDER en la sala;
                // el jugador además debe pertenecer a su red comercial — en una
                // sala PUBLIC el mismo teléfono puede ser de otro comercio.
                $player = ExternalPlayer::select('room_user_id', 'first_name', 'last_name', 'country_id', 'phone', 'link')
                    ->where('phone', $request->player_info)
                    ->where('game_id', 1)
                    ->where('room_id', $room->id)
                    ->whereIn('seller_user_id', $this->externalPlayerLink->actorNetworkUserIds(Auth::id()))
                    ->first();

                if (!$player) return response()->json(['response' => false]);

                $route = $this->externalPlayerLink->resolveGameUrl($player->link);
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
        // Scope por comercio — mismo criterio que getPlayer(): el link es un
        // Str::random(28) no enumerable por fuerza bruta, pero si un seller
        // llega a conocer el link de un jugador captado por OTRO vendedor
        // (reenvío, logs compartidos, etc.) no debe poder consultar su
        // identificación/teléfono sin pertenecer al mismo comercio.
        $seller = $this->seller(['id', 'manager_user_id', 'subsidiary_id']);
        $commerceUserId = $seller?->resolveCommerce()?->user_id;

        if (!$commerceUserId) {
            return json_encode(null);
        }

        // Por red comercial (seller_user_id), no por salas accesibles: con
        // accessibleBy() cualquier PUBLIC entraba, y con ella los jugadores
        // captados en esa sala por OTROS comercios.
        $player = ExternalPlayer::where('link', $playerLink)
            ->whereIn('seller_user_id', $this->externalPlayerLink->actorNetworkUserIds(Auth::id()))
            ->select(['identification', 'phone'])->first();
        if ($player) return json_encode($player);
    }
}
