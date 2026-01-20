<?php

namespace App\Services\Users\Roles\Customer;

use App\Traits\Games\SeamlessWallet;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class HandleTransactions
{
    use SeamlessWallet;

    public function validateTransactions($customer = null)
    {
        DB::beginTransaction();  // Iniciar transacción

        try {
            // Obtener transacciones pendientes
            $query = Transaction::where('status', 0);

            if ($customer) {
                $query->where('customer', $customer);
            }

            $transactions = $query->get();

            // Verificar si hay transacciones pendientes
            if ($transactions->isEmpty()) {
                DB::commit(); // Si no hay transacciones, confirmar y salir
                return false;
            }

            foreach ($transactions as $transaction) {

                try {
                    // Declarar ID de la transacción
                    $transactionID = $transaction->id;

                    // Procesar la transferencia de dinero
                    $transferResponse = $this->transferMoney($transaction);

                    // Verificar si la respuesta contiene un error
                    if (isset($transferResponse['Error']) && $transferResponse['Error']) {
                        Log::error("Error al procesar la transacción: '{$transactionID}': {$transferResponse['Error']}");
                        continue;
                    }

                    // Actualizar la transacción si es exitosa
                    Transaction::where('id', $transactionID)->update([
                        'status' => 1,
                        'processing_data' => Carbon::now(),
                        'updated_at' => now(),
                    ]);
                } catch (\Exception $e) {
                    // Manejo de excepciones inesperadas
                    Log::error("Excepción al procesar la transacción: '{$transactionID}', Error: {$e->getMessage()}");
                    DB::rollBack();  // Si ocurre un error, revertir la transacción
                    return ['Error' => 'Ocurrió un error al procesar las transacciones'];
                }
            }

            DB::commit();

        } catch (\Exception $e) {
            // Si ocurre un error en el proceso global, hacer rollback
            DB::rollBack();
            Log::error("Error al procesar todas las transacciones: " . $e->getMessage());
            return ['Error' => 'Ocurrió un error al procesar las transacciones'];
        }
    }
}
