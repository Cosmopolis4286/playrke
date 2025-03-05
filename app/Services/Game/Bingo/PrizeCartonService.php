<?php

namespace App\Services\Game\Bingo;

use App\Models\Customer;
use App\Models\BingoPrizeCtns;
use App\Models\BingoRoom;
use App\Services\Customer\HandleTransactions;
use App\Traits\SeamlessWallet;
use Illuminate\Support\Facades\DB;
use Exception;

class PrizeCartonService
{
    use SeamlessWallet;

    public function handleCustomerPrizeCarton($customerID)
    {
        // Buscar información del cliente
        $customer = Customer::find($customerID);

        if (!$customer) {
            throw new Exception("Cliente no encontrado.");
        }

        // Verificar si el cliente tiene premios no procesados
        $hasPrizes = BingoPrizeCtns::where('customer', $customerID)
            ->where('active', 'N')->exists();

        if (!$hasPrizes) {
            return ['status' => 'info', 'message' => 'No hay premios asociados a este cliente.'];
        }

        // Procesar premios en fragmentos
        BingoPrizeCtns::where('customer', $customerID)
            ->where('active', 'N')
            ->chunk(100, function ($prizeCtns) use ($customerID) {
                $this->processPrizeCtns($prizeCtns, $customerID);
            });

        // Ejecutar validación de transacciones después del procesamiento
        app(HandleTransactions::class)->validateTransactions($customerID);

        return ['status' => 'success', 'message' => 'Premios procesados exitosamente.'];
    }

    /**
     * Obtener cliente con validación
     */
    private function getCustomer($customerID)
    {
        $customer = Customer::find($customerID);

        if (!$customer) {
            throw new Exception("Cliente no encontrado.");
        }

        return $customer;
    }

    /**
     * Procesar premios no procesados
     */
    private function processPrizeCtns($prizeCtns, $customerID)
    {
        DB::beginTransaction();

        try {
            foreach ($prizeCtns as $ctn) {
                // Obtener roomUUID
                $roomUUID = $this->getRoomUUID($ctn->room);

                // Registrar la transacción
                $this->processPrizeTransaction($ctn, $customerID, $roomUUID);

                // Actualizar estado del premio
                if (!$ctn->update(['active' => 'Y'])) {
                    throw new Exception("Error al actualizar el estado del premio con ID: {$ctn->id}");
                }
            }

            DB::commit();
        } catch (\Throwable $th) {
            DB::rollBack();
            // Log del error
            throw new Exception("Error procesando los premios: " . $th->getMessage());
        }
    }

    /**
     * Obtener UUID de la sala
     */
    private function getRoomUUID($roomId)
    {
        $roomUUID = BingoRoom::getValueById($roomId, 'code_uuid');

        if (!$roomUUID) {
            throw new Exception("No se encontró un código UUID para la sala con ID: $roomId");
        }

        return $roomUUID;
    }

    /**
     * Procesar transacción del premio
     */
    private function processPrizeTransaction($ctn, $customerID, $roomUUID)
    {
        $transaction = $this->registerTransaction(
            ['name' => 'BINGO', 'game_mode' => 1],
            $customerID,
            $ctn->player_user,
            $roomUUID,
            'WIN',
            $ctn->coin,
            $ctn->amount
        );

        if (!$transaction) {
            throw new Exception("Error al registrar la transacción para el premio de BingoPrizeCtns con ID: {$ctn->id}");
        }
    }
}
