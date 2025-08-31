<?php

namespace App\Http\Controllers;

// Models
use App\Models\Customer;
use App\Models\Role;
use App\Repositories\User\RoleRepository;
// Traits
use App\Traits\Users\CustomerTrait;
use App\Traits\Users\UserValTrait;

class IframeController extends Controller
{
    use UserValTrait, CustomerTrait;

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
                    'bingo.handleGameRoomSummary',
                    [
                        'roomType' => 'private-rooms',
                        'creatorType' => 'customer',
                        'creatorID' => encrypt($customer->id)
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

        $customerID = Customer::getValueByUserId($user->id, 'customer_id');

        return ['route' => route('bingo.game-rooms-customer', ['customer' => $customerID, 'user' => $userToken])];
    }
}
