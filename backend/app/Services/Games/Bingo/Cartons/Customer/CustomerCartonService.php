<?php

namespace App\Services\Games\Bingo\Cards\Customer;

use App\Models\{BingoPrizeCtns, BingoRoom, Customer};
use App\Services\Users\Customer\HandleTransactions;
use App\Traits\Games\SeamlessWallet;
use Illuminate\Support\Facades\DB;
use Exception;

class CustomerCartonService
{
    use SeamlessWallet;

    public function handleCustomerPrizeCarton($creatorUser)
    {
        // Buscar información del cliente
        $customerId = Customer::where('user', $creatorUser)->value('id');

        if (!$customerId) {
            throw new Exception("Cliente no encontrado.");
        }

        // Verificar si el cliente tiene premios no procesados
        $hasPrizes = BingoPrizeCtns::where('creator_user_id', $creatorUser)
            ->where('active', 'N')->exists();

        if (!$hasPrizes) {
            return ['status' => 'info', 'message' => 'No hay premios asociados a este cliente.'];
        }

        // Procesar premios en fragmentos
        BingoPrizeCtns::where('creator_user_id', $creatorUser)
            ->where('active', 'N')
            ->chunk(100, function ($prizeCtns) use ($customerId) {
                $this->processPrizeCtns($prizeCtns, $customerId);
            });

        // Ejecutar validación de transacciones después del procesamiento
        app(HandleTransactions::class)->validateTransactions($customerId);

        return ['status' => 'success', 'message' => 'Premios procesados exitosamente.'];
    }

    /**
     * Procesar premios no procesados
     */
    private function processPrizeCtns($prizeCtns, $customerId)
    {
        DB::beginTransaction();

        try {

            foreach ($prizeCtns as $ctn) {
                // Obtener roomUUID
                $roomUUID = $this->getRoomUUID($ctn->room);

                // Registrar la transacción
                $this->processPrizeTransaction($ctn, $customerId, $roomUUID);

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
        $roomUUID = BingoRoom::getValueById($roomId, 'uuid_code');

        if (!$roomUUID) {
            throw new Exception("No se encontró un código UUID para la sala con ID: $roomId");
        }

        return $roomUUID;
    }

    /**
     * Procesar transacción del premio
     */
    private function processPrizeTransaction($ctn, $customerId, $roomUUID)
    {
        $transaction = $this->registerTransaction(
            ['name' => 'BINGO', 'game_mode' => 1],
            $customerId,
            $ctn->player_user,
            $roomUUID,
            'WIN',
            $ctn->currency,
            $ctn->amount
        );

        if (!$transaction) {
            throw new Exception("Error al registrar la transacción para el premio de BingoPrizeCtns con ID: {$ctn->id}");
        }
    }
}
