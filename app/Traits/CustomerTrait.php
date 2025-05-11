<?php

namespace App\Traits;

use App\Models\Customer;
use App\Models\CustomerUser;
use App\Services\Customer\CustomerSessionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Error al registar usuario del Cliente ['customerID' => $customer]: " . $e->getMessage());
            return false;
        }
    }

    public function validateCustomer($customerID, $userToken)
    {
        // Obtener el ID del cliente definido
        $customerID = Customer::getValueByCustomerID($customerID, 'id');

        // Verificar si el cliente existe
        if (!$customerID) {
            return ['Error' => 'ID del cliente no existe'];
        }

        if ($customerID != $userToken) {
            // Consultar información de usuario y crear variables de sesión
            $customerSessionService = new CustomerSessionService();
            $response = $customerSessionService->handleCustomerUserSession($customerID, $userToken);

            // Verificar si existe el usuario por su token
            if (isset($response['Error'])) {
                return ['Error' => $response['Error']];
            }
        }

        return $customerID;
    }
}
