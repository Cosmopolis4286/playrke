<?php

namespace App\Http\Controllers\Utils;

// Models

use App\Http\Controllers\Controller;
use App\Repositories\User\Roles\RoleRepository;
use App\Traits\Users\Roles\Customer\CustomerTrait;
// Traits
use App\Traits\Users\UserTrait;

class IframeController extends Controller
{
    use UserTrait, CustomerTrait;

    public function index()
    {
        $user = $this->user(['id', 'user_type']);

        if (RoleRepository::getValueById($user->user_type, 'prefix') !== 'customer') {
            return redirect()->back();
        }

        $customer = Customer::getByUserId($user->id, ['id', 'customer_id']);

        $iframes = [
            [
                'title' => 'Salas de Juego CRUD',
                'route' => route(
                    'bingo.room-summary',
                    [
                        'roomType' => 'private-rooms',
                        'creatorType' => 'customer',
                        'creatorId' => encrypt($customer->id)
                    ]
                )
            ],
            [
                'title' => 'Salas de Juego Vista',
                'route' => route(
                    'bingo.game-rooms-customer',
                    ['customer' => $customer->customer_id, 'user' => 'available-rooms']
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

        if (RoleRepository::getValueById($user->user_type, 'prefix') !== 'customer') {
            return redirect()->back();
        }

        $customerId = Customer::getValueByUserId($user->id, 'customer_id');

        return ['route' => route('bingo.game-rooms-customer', ['customer' => $customerId, 'user' => $userToken])];
    }
}
