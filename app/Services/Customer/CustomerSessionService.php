<?php

namespace App\Services\Customer;

use App\Models\CustomerUser;
use App\Traits\SeamlessWallet;

class CustomerSessionService
{
    use SeamlessWallet;

    public function handleCustomerUserSession($customerID, $userToken)
    {
        $customerResponse = $this->getUserInfoFromCustomer($customerID, $userToken);

        if (isset($customerResponse['Error'])) {
            return ['Error' => $customerResponse['Error']];
        }

        if (!$customerResponse) {
            return ['Error' => 'Error en la solicitud HTTP'];
        }

        $userID = $customerResponse['Data']['ID'];
        $country = $customerResponse['Data']['Country'];
        $coin = $customerResponse['Data']['DefaultCurrencyCode'];

        if (!CustomerUser::existsUserByCustomer($customerID, $userID)) {
            $this->registerUserByCustomer(
                $customerID,
                $userID,
                $userToken,
                $country,
                $coin
            );
        } else {
            CustomerUser::where('user_id', $userID)->update([
                'customer' => $customerID,
                'user_id' => $userID,
                'token' => $userToken,
                'country' => $country,
                'coin' => $coin,
                'last_activity' => now()
            ]);
        }

        return true;
    }
}
