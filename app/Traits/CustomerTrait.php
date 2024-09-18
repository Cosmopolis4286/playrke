<?php

namespace App\Traits;

use App\Models\Customer;
use App\Models\CustomerUser;

trait CustomerTrait
{
    use SeamlessWallet;

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

    public function validateCustomer($customerToken, $userToken)
    {
        // Obtener el ID del cliente usando el token
        $customerId = Customer::getValueByToken($customerToken, 'id');

        // Verificar si el cliente existe
        if (!$customerId) {
            return ['Error' => 'Token del cliente no existe'];
        }

        // Consultar información de usuario y crear variables de sesión
        $response = $this->handleCustomerUserSession($customerId, $userToken);

        // Verificar si existe el usuario por su token
        if (isset($response->Error)) {
            return ['Error' => 'Token de usuario incorrecto'];
        }

        return $customerId;
    }
}
