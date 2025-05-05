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

        if (!CustomerUser::existsUserByCustomer($customerID, $userID)) {
            $this->registerUserByCustomer(
                $customerID,
                $userID,
                $userToken,
                $customerResponse['Data']['Country'],
                $customerResponse['Data']['DefaultCurrencyCode']
            );
        }

        return true;
    }
}
