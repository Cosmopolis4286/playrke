<?php

namespace App\Http\Controllers;

use App\Traits\UserVal;
use App\Traits\EncryptRKE;
use App\Models\Wallet;
use App\Models\User;
use App\Models\Coin;
use App\Models\HistGame;
use App\Http\Controllers\Controller;
use App\Models\Commerce;
use App\Models\CommerceExchangeRate;
use App\Models\Country;
use App\Models\GameModes;
use App\Models\JackpotType;
use App\Models\PollaDraw;
use App\Traits\Filters;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Session;

class PollaController extends Controller
{
    use EncryptRKE;
    use UserVal;
    use Filters;

    // CRUD //     
    public function getGameRoomsInfo($creator, $coin) //  Lista Salas CRUD de Juego
    {
        //$this->checkRoomClosure();

        switch ($creator['type']) {
            case 'admin': // Admin
                break;
            case 'commerce': // Comercio

                $creatorID = $creator['id'];

                $isLogin = true;
                $coinSym = Coin::getValue($coin, 'symbol');
                $exchangeRate = CommerceExchangeRate::getCommerceCoinValue($creatorID, $coin);

                $coins = CommerceExchangeRate::getCoinsByCommerce($creatorID);
                $coins[] = 1; // ID Coin (USDT)
                $coins = Coin::getCoinsById($coins);

                $routeName = 'bingo.all-commerce-rooms';
                $GMRoute =  route('bingo.game-rooms-commerce', ['coin' => encrypt($coin)]);

                $moreViewData = [
                    'commerceUUID' => Commerce::getValueById($creatorID, 'code_uuid'),
                    'wallet' => $this->wallet(),
                    'countries' => Country::getAllAvailableCountries()
                ];

                if ($creatorID == 1) {
                    $roomType = [
                        0 => ['id' => 0, 'description' => 'Privada'],
                        1 => ['id' => 1, 'description' => 'Pública']
                    ];
                }
                break;
            case 'customer': // Cliente

                // $creatorID = $creator['id'];
                // $isLogin = false;

                // $coinSym = $coin;
                // $exchangeRate = CustomerExchangeRate::getCustomerCoinValue($creatorID, $coin);
                // $coins = CustomerExchangeRate::getCoinsIDsByCustomer($creatorID);

                // $returnBtn = false;
                // $token = Customer::getValueById($creatorID, 'token');

                // $routeName = 'bingo.all-customer-rooms';
                // $GMRoute =  route(
                //     'bingo.game-rooms-customer',
                //     ['customer' => $token, 'user' => $token, 'coin' => encrypt($coin)]
                // );

                // $moreViewData = ['customer' => true, 'userToken' => $token];

                break;

            default:
                return false;
                break;
        }

        // $gameJackpotType = GameJackpotType::getAvailableByGameMode(1, 2);
        // $jackpotType = JackpotType::getByIds($gameJackpotType);

        $rooms = PollaDraw::with([
            'Config.JackpotType',
            'GameMode',
            'PlayersRoom' => function ($query) {
                $query->where('winner', '>', 0);
            },
            'GameExchangeRate' => function ($query) use ($coin) {
                $query->where('coin', $coin)->where('game', 1);
            }
        ])->where($creator['type'], $creatorID)->orderByDesc('date')
            ->orderByDesc('time')->orderBy('status')->get();

        $viewData = [
            'layout' => $isLogin ? 'layouts.app_rke' : 'layouts.app_rke_off',
            'title' => 'Salas de Bingo',
            'name' => 'bingocash',
            'returnBtn' => $returnBtn ?? true,
            'routeName' => $routeName,
            'creatorType' => $creator['type'],
            'userID' => $creatorID,
            'coins' => $coins,
            'coin' => $coin,
            'coinSym' => $coinSym,
            'exchangeRate' => $exchangeRate ?? 1,
            'rooms' => $rooms,
            'GMRoute' => $GMRoute,
            'activeRooms' => $rooms->whereIn('status', [2, 3])->count(),
            'roomType' => $roomType ?? [],
            'jackpotType' => JackpotType::all(),
            'gameModes' => GameModes::all()
        ];

        if (isset($moreViewData)) {
            $viewData = array_merge($viewData, $moreViewData);
        }

        return ['route' => 'modules.game.polla.form.index', 'data' => $viewData];
    }

    public function allCommerceRooms($coin = '')
    {
        $commerceId = Commerce::getValueByUserId(Auth::id(), 'id');

        if (!$commerceId) return redirect()->back();

        $creator = ['type' => 'commerce', 'id' => $commerceId];

        $viewData = $this->getGameRoomsInfo($creator, decrypt($coin));

        // Retorna a vista con la informacion 
        return $this->handleViewData($viewData, $creator['type']);
    }

    public function roomsSalesReport() {}

    public function lotteryResults() {}
}
