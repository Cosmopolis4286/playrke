<?php

namespace App\Traits\Users;

use App\Models\Customer;
use App\Models\CustomerUser;
use App\Services\Users\Customer\CustomerSessionService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

trait CustomerTrait
{
    /**
     * Registra un usuario asociado a un cliente.
     *
     * @param int|string $customer   ID del cliente
     * @param int        $userID     ID del usuario
     * @param string     $token      Token único
     * @param string     $country    Código o nombre del país
     * @param string     $coin       Moneda asociada
     * @return bool      true si se registra correctamente, false si falla
     */
    public function registerUserByCustomer($customer, int $userID, string $token, string $country, string $coin): bool
    {
        DB::beginTransaction();

        try {
            CustomerUser::create([
                'customer' => $customer,
                'user_id' => $userID,
                'token' => $token,
                'country' => $country,
                'coin' => $coin,
                'last_activity' => now(),
            ]);

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error("Error al registrar usuario del cliente [customerID={$customer}]: " . $e->getMessage());

            return false;
        }
    }

    /**
     * Valida un cliente y su token.
     *
     * @param int|string $customerID ID del cliente (puede ser token o id)
     * @param string     $userToken  Token del usuario
     * @return int|string|array      Retorna el ID del cliente válido o un array con error
     */
    public function validateCustomer($customerID, string $userToken)
    {
        // Obtener el ID válido del cliente
        $validCustomerID = Customer::getValueByCustomerID($customerID, 'id');

        if (!$validCustomerID) {
            return ['Error' => 'ID del cliente no existe'];
        }

        // Si el token y customerID no coinciden, validar sesión
        if ($validCustomerID != $userToken) {
            $customerSessionService = new CustomerSessionService();
            $response = $customerSessionService->handleCustomerUserSession($validCustomerID, $userToken);

            if (isset($response['Error'])) {
                return ['Error' => $response['Error']];
            }
        }

        return $validCustomerID;
    }
}
