<?php

namespace App\Traits;

use App\Models\CustomerUser;

trait CustomerTrait
{
    public function registerUserByCustomer($customer, $userID, $country, $coin)
    {
        date_default_timezone_set('America/Caracas');

        CustomerUser::create([
            'customer' => $customer,
            'user_id' => $userID,
            'country' => $country,
            'coin' => $coin,
            'first_entry' => now()
        ]);
    }
}
