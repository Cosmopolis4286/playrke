<?php

namespace App\Http\Controllers\Utils;

// Models

use App\Http\Controllers\Controller;
use App\Models\Roles\Customer;
use App\Traits\Users\Roles\Customer\CustomerTrait;
// Traits
use App\Traits\Users\UserTrait;

class IframeController extends Controller
{
    use UserTrait, CustomerTrait;

    public function index()
    {
        $user = $this->user(['id']);

        if (!$user || !$user->roles->contains('prefix', 'customer')) {
            return redirect()->back();
        }

        $customer = Customer::where('user_id', $user->id)->select(['id', 'customer_id'])->first();

        $iframes = [
            [
                'title' => 'Salas de Juego CRUD',
                'route' => route(
                    'bingo-rooms.index',
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
                    'bingo-rooms.index-customer',
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
        $user = $this->user(['id']);

        if (!$user || !$user->roles->contains('prefix', 'customer')) {
            return redirect()->back();
        }

        $customerId = Customer::getValueByUserId($user->id, 'customer_id');

        return ['route' => route('bingo-rooms.index-customer', ['customer' => $customerId, 'user' => $userToken])];
    }
}
