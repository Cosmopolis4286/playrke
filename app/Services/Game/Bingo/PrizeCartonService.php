<?php

namespace App\Services\Game\Bingo;

use App\Models\Customer;
use App\Models\BingoPrizeCtns;
use App\Models\BingoRoom;
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

        // Obtener premios no procesados del cliente
        $prizeCtns = BingoPrizeCtns::where('customer', $customerID)->where('active', 'N')->get();

        if ($prizeCtns->isEmpty()) {
            return ['status' => 'info', 'message' => 'No hay premios asociados a este cliente.'];
        }

        DB::beginTransaction();

        try {
            foreach ($prizeCtns as $ctn) {
                // Obtener roomUUID con validación
                $roomUUID = $this->getRoomUUID($ctn->room);

                // Registrar la transacción
                $this->processPrizeTransaction($ctn, $customerID, $roomUUID);

                // Actualizar estado del premio
                if (!$ctn->update(['active' => 'Y'])) {
                    throw new Exception("Error al actualizar el estado del premio con ID: {$ctn->id}");
                }
            }

            DB::commit();
            return ['status' => 'success', 'message' => 'Premios procesados exitosamente.'];
        } catch (\Throwable $th) {
            DB::rollBack();
            // Log del error o notificación
            throw new Exception("Error procesando los premios: " . $th->getMessage());
        }
    }

    private function getRoomUUID($roomId)
    {
        $roomUUID = BingoRoom::getValueById($roomId, 'code_uuid');

        if (!$roomUUID) {
            throw new Exception("No se encontró un código UUID para la sala con ID: $roomId");
        }

        return $roomUUID;
    }

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
            throw new Exception("Error al registrar la transacción para el premio con ID: {$ctn->id}");
        }
    }
}
