<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\UserType;
use App\Traits\CustomerTrait;
use App\Traits\UserVal;

class IframeController extends Controller
{
    use UserVal, CustomerTrait;

    public function index()
    {
        $user = $this->user(['id', 'user_type']);

        if (UserType::getValueById($user->user_type, 'prefix') !== 'customer') {
            return redirect()->back();
        }

        $customer = Customer::getByUserId($user->id, ['id', 'token']);

        $customerToken = $customer->token;

        $iframes = [
            [
                'title' => 'Salas de Juego CRUD',
                'route' => route('bingo.all-rooms', ['creatorType' => 'customer', 'userID' => $customerToken])
            ],
            [
                'title' => 'Salas de Juego Vista',
                'route' => route(
                    'bingo.game-rooms-customer',
                    ['customer' => $customerToken, 'user' => '1']
                ),
            ],
        ];

        return view('modules.all.iframe.index')->with([
            'title' => 'Iframes',
            'iframes' => $iframes
        ]);
    }

    public function getRouteGameRooms($userToken)
    {
        $user = $this->user(['id', 'user_type']);

        if (UserType::getValueById($user->user_type, 'prefix') !== 'customer') {
            return redirect()->back();
        }

        $customerToken = Customer::getValueByUserId($user->id, 'token');

        return ['route' => route('bingo.game-rooms-customer', ['customer' => $customerToken, 'user' => $userToken])];
    }
}
