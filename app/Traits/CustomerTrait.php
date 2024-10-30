<?php

namespace App\Traits;

use App\Models\Customer;
use App\Models\CustomerUser;
use App\Services\CustomerSessionService;
use Illuminate\Support\Facades\DB;

trait CustomerTrait
{
    public function registerUserByCustomer($customer, $userID, $token, $country, $coin)
    {
                DB::beginTransaction();

        try {

            CustomerUser::create([
                'customer' => $customer,
                'user_id' => $userID,
                'token' => $token,
                'country' => $country,
                'coin' => $coin,
                'last_activity' => now()
            ]);

            DB::commit();
        } catch (\Throwable $th) {
            //throw $th;
            DB::rollBack();
            return false;
        }
    }

    public function validateCustomer($customerToken, $userToken)
    {
        // Obtener el ID del cliente usando el token
        $customerId = Customer::getValueByToken($customerToken, 'id');

        // Verificar si el cliente existe
        if (!$customerId) {
            return ['Error' => 'Token del cliente no existe'];
        }

        if ($customerToken != $userToken) {
            // Consultar información de usuario y crear variables de sesión
            $customerSessionService = new CustomerSessionService();
            $response = $customerSessionService->handleCustomerUserSession($customerId, $userToken);

            // Verificar si existe el usuario por su token
            if (isset($response['Error'])) {
                return ['Error' => $response['Error']];
            }
        }

        return $customerId;
    }
}
