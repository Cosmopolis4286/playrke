<?php

namespace App\Services\Games\Bingo\GameRooms;

use DomainException;

final class BingoGameRoomsService
{

    public function __construct() {}

    /**
     * Resolver contexto para BingoGameRooms.
     *
     * @param string $context
     * @return mixed
     */
    public function context(string $context)
    {
        return match ($context) {
            'fetch' => $this->fetchGameRooms(),
            'summary' => $this->summaryGameRooms(),
            default => throw new DomainException('No existe el contexto: ' . $context),
        };
    }

    /**
     * Obtener las salas de juego de bingo.
     *
     * @return array
     */
    private function fetchGameRooms(): array
    {
        return [];
    }

    private function summaryGameRooms($creator, $currency, $roomType)
    {
        //app(HandleBingoRoomsService::class)->checkRoomClosure();

        $view = $roomType === 'private-rooms' ? 'private' : 'public';

        switch ($creator['type']) {
            case 'admin': // Admin
                break;
            case 'commerce': // Comercio
                $creatorId = decrypt($creator['id']);

                $isLogged = true;
                $currencySym = Currency::getValue($currency, 'symbol');
                $exchangeRate = CommerceExchangeRate::getCommerceCurrencyValue($creatorId, $currency);

                $currencies = Currency::getCurrenciesById(
                    [...CommerceExchangeRate::getCurrenciesByCommerce($creatorId), 1],
                    ['id', 'iso_code', 'name']
                );

                $routeWithCurrency = [
                    'route' => 'bingo.game-rooms',
                    'parameters' => [
                        'roomType' => $roomType,
                        'creatorType' => 'commerce',
                        'creatorId' => encrypt($creator['id'])
                    ]
                ];

                $gameRoomRoute =  route('bingo.gameRoomsCommerce', ['roomType' => $view, 'currency' => encrypt($currency)]);

                $moreViewData = [
                    'uuidCode' => Commerce::getValueById($creatorId, 'uuid_code'),
                    'countries' => Country::getAllAvailableCountries()
                ];

                break;
            case 'customer': // Cliente

                $creatorId = decrypt($creator['id']);
                $isLogged = $returnBtn = false;

                $customerExchangeRate = CustomerExchangeRate::getById($currency);
                $exchangeRate = $customerExchangeRate->exchange_rate;
                $currencySym = $customerExchangeRate->currency;
                $currencies = CustomerExchangeRate::getCurrenciesIDsByCustomer($creatorId);

                $customer = Customer::getById($creatorId, ['customer_id', 'token']);

                $routeWithCurrency = [
                    'route' => 'bingo.game-rooms',
                    'parameters' => [
                        'roomType' => $roomType,
                        'creatorType' => 'customer',
                        'creatorId' => encrypt($creator['id'])
                    ]
                ];

                $gameRoomRoute =  route(
                    'bingo.game-rooms-customer',
                    [
                        'customer' => $customer->customer_id,
                        'user' => 'available-rooms',
                        'currency' => encrypt($currency)
                    ]
                );

                $gameId = Game::getValueByName('BINGO');

                $customerGameID = CustomerGame::getByGameID($creatorId, $gameId) ?? null;

                $moreViewData = [
                    'customer' => true,
                    'userToken' => $customer->token,
                    'customerGameID' => $customerGameID
                ];

                break;

            default:
                return false;
                break;
        }

        $creatorUser = $creator['userId'];

        $rooms = $this->getInfoRooms($creatorUser, $roomType, $currency);

        $bingoPatterns = BingoPatterns::getAllByUser($creatorUser, ['id', 'name'], 'ACTIVE');

        $bingoPatterns = BingoPatterns::where(function ($query) use ($creatorUser) {
            $query->where('user', $creatorUser)
                ->orWhereNull('user');
        })->where('status', '<>', 'DELETED')->get();

        $timeZoneID = User::getValueById($creatorUser, 'time_zone');
        $timeZone = TimeZone::getById($timeZoneID, ['name', 'utc']);

        $viewData = [
            'bladeLayout' => $isLogged ? 'layouts.auth-layout' : 'layouts.guest-layout',
            'title' => 'Salas de Bingo',
            'subtitle' => $view === 'private' ? 'Privadas' : 'Publicas',
            'view' => strtoupper($view),
            'name' => 'bingo',
            'returnBtn' => $returnBtn ?? true,
            'routeWithCurrency' => $routeWithCurrency,
            'routeRKE' => $this->routeService->getBaseUrl(),
            'creator' => $creator,
            'currencies' => $currencies,
            'currency' => $currency,
            'currencySymbol' => $currencySym,
            'exchangeRate' => $exchangeRate ?? 1,
            'roomType' => $view === 'private' ? 'PV' : 'PB',
            'bingoPatterns' => $bingoPatterns,
            'timeZone' => $timeZone,
            'rooms' => $rooms,
            'gameRoomRoute' => $gameRoomRoute,
            'activeRooms' => $rooms->whereIn('status', [2, 3])->count(),
            'typesPrizePool' => PrizePoolType::all(),
            'gameModes' => GameModes::all()
        ];

        if (isset($moreViewData)) {
            $viewData = array_merge($viewData, $moreViewData);
        }

        return ['route' => 'modules.game.bingo.summary.index', 'data' => $viewData];
    }
}
