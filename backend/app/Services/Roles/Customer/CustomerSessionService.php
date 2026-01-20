<?php

namespace App\Services\Users\Roles\Customer;

use App\Models\CustomerUser;
use App\Traits\Games\SeamlessWallet;

class CustomerSessionService
{
    use SeamlessWallet;

    public function handleCustomerUserSession($customerId, $userToken)
    {
        $customerResponse = $this->getUserInfoFromCustomer($customerId, $userToken);

        if (isset($customerResponse['Error'])) {
            return ['Error' => $customerResponse['Error']];
        }

        if (!$customerResponse) {
            return ['Error' => 'Error en la solicitud HTTP'];
        }

        $userId = $customerResponse['Data']['ID'];
        $country = $customerResponse['Data']['Country'] ?? null;
        $currency = $customerResponse['Data']['DefaultCurrencyCode'];

        if (!CustomerUser::existsUserByCustomer($customerId, $userId)) {
            $this->registerUserByCustomer(
                $customerId,
                $userId,
                $userToken,
                $country,
                $currency
            );
        } else {
            CustomerUser::where('user_id', $userId)->update([
                'customer' => $customerId,
                'user_id' => $userId,
                'token' => $userToken,
                'country' => $country,
                'currency' => $currency,
                'last_activity' => now()
            ]);
        }

        return true;
    }
}
