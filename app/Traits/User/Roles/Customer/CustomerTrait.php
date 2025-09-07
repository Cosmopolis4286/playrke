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
     * @param int        $userId     ID del usuario
     * @param string     $token      Token único
     * @param string     $country    Código o nombre del país
     * @param string     $currency       Moneda asociada
     * @return bool      true si se registra correctamente, false si falla
     */
    public function registerUserByCustomer($customer, int $userId, string $token, string $country, string $currency): bool
    {
        DB::beginTransaction();

        try {
            CustomerUser::create([
                'customer' => $customer,
                'user_id' => $userId,
                'token' => $token,
                'country' => $country,
                'currency' => $currency,
                'last_activity' => now(),
            ]);

            DB::commit();

            return true;
        } catch (\Exception $e) {
            DB::rollBack();

            Log::error("Error al registrar usuario del cliente [customerId={$customer}]: " . $e->getMessage());

            return false;
        }
    }

    /**
     * Valida un cliente y su token.
     *
     * @param int|string $customerId ID del cliente (puede ser token o id)
     * @param string     $userToken  Token del usuario
     * @return int|string|array      Retorna el ID del cliente válido o un array con error
     */
    public function validateCustomer($customerId, string $userToken)
    {
        // Obtener el ID válido del cliente
        $validCustomerID = Customer::getValueByCustomerID($customerId, 'id');

        if (!$validCustomerID) {
            return ['Error' => 'ID del cliente no existe'];
        }

        // Si el token y customerId no coinciden, validar sesión
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
