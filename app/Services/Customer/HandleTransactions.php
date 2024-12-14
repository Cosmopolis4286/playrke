<?php

namespace App\Services\Customer;

use App\Models\CustomerUser;
use App\Models\Transactions;
use App\Traits\SeamlessWallet;
use Carbon\Carbon;
use Illuminate\Support\Facades\Log;

class HandleTransactions
{
    use SeamlessWallet;

    public function validateTransactions($customer = null)
    {
        // Obtener transacciones pendientes
        $query = Transactions::where('status', 0);

        if ($customer) {
            $query->where('customer', $customer);
        }
        
        $transactions = $query->get();

        // Verificar si hay transacciones pendientes
        if ($transactions->isEmpty()) {
            return false;
        }

        foreach ($transactions as $transaction) {
            try {
                // Obtener información del jugador
                $player = CustomerUser::getById($transaction->user_id, ['user_id', 'token']);

                // Declarar ID de la transaccion
                $transactionID = $transaction->id;

                // Verificar si el jugador existe
                if (!$player) {
                    Log::error("Jugador no encontrado para la transacción: '{$transactionID}'");
                    continue;
                }

                // Procesar la transferencia de dinero [enviar datos de la transaccion]
                $transferResponse = $this->transferMoney($transaction);

                // Verificar si la respuesta contiene un error
                if (isset($transferResponse['Error']) && $transferResponse['Error']) {
                    Log::error("Error al procesar la transacción: '{$transactionID}': {$transferResponse['Error']}");
                    continue;
                }

                // Actualizar la transacción si es exitosa
                Transactions::where('id', $transactionID)->update([
                    'status' => 1,
                    'processing_data' => Carbon::now(),
                    'updated_at' => now(),
                ]);
            } catch (\Exception $e) {
                // Manejo de excepciones inesperadas
                Log::error("Excepción al procesar la transacción: '{$transactionID}', Error: {$e->getMessage()}");
            }
        }
    }
}
