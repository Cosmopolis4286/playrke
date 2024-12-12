<?php

namespace App\Services\Game\Bingo;

use App\Models\Customer;
use App\Models\BingoPrizeCtns;
use App\Models\Transactions;
use Illuminate\Support\Facades\Log;

class PrizeCartonService
{
    /**
     * Maneja la asignación de premios para un cliente.
     *
     * @param int $customerId
     * @param int $cartonId
     * @param string $prize
     * @return array
     * @throws \Exception
     */
    public function handleCustomerPrizeCarton(int $customerId, int $cartonId, string $prize): array
    {
        // Verificar que los datos necesarios están presentes
        if (!$customerId || !$cartonId || !$prize) {
            throw new \InvalidArgumentException("Datos incompletos para manejar el premio del cartón.");
        }

        // 1. Buscar información del cliente
        $customer = Customer::find($customerId);
        if (!$customer) {
            throw new \Exception("Cliente no encontrado.");
        }

        // 2. Verificar el cartón ganador
        $carton = BingoCarton::where('id', $cartonId)
            ->where('customer_id', $customerId)
            ->first();

        if (!$carton) {
            throw new \Exception("Cartón no válido o no pertenece al cliente.");
        }

        // 3. Asignar el premio al cliente
        $prizeAssigned = Prize::create([
            'customer_id' => $customerId,
            'carton_id' => $cartonId,
            'prize' => $prize,
            'assigned_at' => now(),
        ]);

        if (!$prizeAssigned) {
            throw new \Exception("Error al asignar el premio al cliente.");
        }

        // 4. Actualizar el estado del cartón
        $carton->update(['status' => 'prize_assigned']);

        // 5. Registrar la acción en los logs
        Log::info("Premio asignado: Cliente #$customerId, Cartón #$cartonId, Premio: $prize");

        // 6. Notificar al cliente
        $this->notifyCustomer($customer, $prize);

        return [
            'success' => true,
            'message' => "Premio asignado exitosamente.",
            'data' => [
                'customer' => $customer,
                'carton' => $carton,
                'prize' => $prize,
            ],
        ];
    }
}
