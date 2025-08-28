<?php

namespace App\Services\Games\Bingo\Cartons;

use App\Models\BingoCtnPrice;
use App\Models\BingoCtnPriceDrafts;
use Illuminate\Support\Facades\Log;

class PrizeCartonService
{
    /**
     * Registra los precios de cartones para una sala.
     *
     * @param int $roomId
     * @return bool Retorna true si los precios se registraron correctamente.
     * @throws \Exception Si los datos de draft son inconsistentes o hay un error de base de datos.
     */
    public function registerCtnPrices(int $roomId): bool
    {
        try {
            // Obtener draft de precios para la sala
            $draft = BingoCtnPriceDrafts::getByRoom($roomId, ['creator_user', 'coins', 'ctn_prices', 'status']);

            if (!$draft) {
                Log::info("No se encontró draft de precios para sala ID {$roomId}");
                return false;
            }

            $coins = (array) $draft->coins;
            $prices = (array) $draft->ctn_prices;

            // Validar integridad de datos
            if (count($coins) !== count($prices)) {
                throw new \Exception("Datos inconsistentes: la cantidad de monedas no coincide con la de precios. Sala ID {$roomId}");
            }

            // Registrar precios usando updateOrCreate para evitar duplicados
            foreach ($coins as $index => $coin) {
                BingoCtnPrice::updateOrCreate(
                    ['room' => $roomId, 'coin' => $coin],
                    [
                        'creator_user' => $draft->creator_user,
                        'ctn_price' => $prices[$index],
                    ]
                );
            }

            // Marcar draft como completado
            $draft->update(['status' => 'FINISHED']);

            return true;
        } catch (\Exception $e) {
            Log::error("Error al registrar precios de cartones para sala ID {$roomId}: " . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

 



// namespace App\Services\Games\Bingo;

// use App\Models\Customer;
// use App\Models\BingoPrizeCtns;
// use App\Models\BingoRoom;
// use App\Services\Users\Customer\HandleTransactions;
// use App\Traits\SeamlessWallet;
// use Illuminate\Support\Facades\DB;
// use Exception;

// class PrizeCartonService
// {
//     use SeamlessWallet;

//     public function handleCustomerPrizeCarton($creatorUser)
//     {
//         // Buscar información del cliente
//         $customerID = Customer::where('user', $creatorUser)->value('id');

//         if (!$customerID) {
//             throw new Exception("Cliente no encontrado.");
//         }

//         // Verificar si el cliente tiene premios no procesados
//         $hasPrizes = BingoPrizeCtns::where('creator_user', $creatorUser)
//             ->where('active', 'N')->exists();

//         if (!$hasPrizes) {
//             return ['status' => 'info', 'message' => 'No hay premios asociados a este cliente.'];
//         }

//         // Procesar premios en fragmentos
//         BingoPrizeCtns::where('creator_user', $creatorUser)
//             ->where('active', 'N')
//             ->chunk(100, function ($prizeCtns) use ($customerID) {
//                 $this->processPrizeCtns($prizeCtns, $customerID);
//             });

//         // Ejecutar validación de transacciones después del procesamiento
//         app(HandleTransactions::class)->validateTransactions($customerID);

//         return ['status' => 'success', 'message' => 'Premios procesados exitosamente.'];
//     }

//     /**
//      * Procesar premios no procesados
//      */
//     private function processPrizeCtns($prizeCtns, $customerID)
//     {
//         DB::beginTransaction();

//         try {

//             foreach ($prizeCtns as $ctn) {
//                 // Obtener roomUUID
//                 $roomUUID = $this->getRoomUUID($ctn->room);

//                 // Registrar la transacción
//                 $this->processPrizeTransaction($ctn, $customerID, $roomUUID);

//                 // Actualizar estado del premio
//                 if (!$ctn->update(['active' => 'Y'])) {
//                     throw new Exception("Error al actualizar el estado del premio con ID: {$ctn->id}");
//                 }
//             }

//             DB::commit();
//         } catch (\Throwable $th) {
//             DB::rollBack();
//             // Log del error
//             throw new Exception("Error procesando los premios: " . $th->getMessage());
//         }
//     }

//     /**
//      * Obtener UUID de la sala
//      */
//     private function getRoomUUID($roomId)
//     {
//         $roomUUID = BingoRoom::getValueById($roomId, 'code_uuid');

//         if (!$roomUUID) {
//             throw new Exception("No se encontró un código UUID para la sala con ID: $roomId");
//         }

//         return $roomUUID;
//     }

//     /**
//      * Procesar transacción del premio
//      */
//     private function processPrizeTransaction($ctn, $customerID, $roomUUID)
//     {
//         $transaction = $this->registerTransaction(
//             ['name' => 'BINGO', 'game_mode' => 1],
//             $customerID,
//             $ctn->player_user,
//             $roomUUID,
//             'WIN',
//             $ctn->coin,
//             $ctn->amount
//         );

//         if (!$transaction) {
//             throw new Exception("Error al registrar la transacción para el premio de BingoPrizeCtns con ID: {$ctn->id}");
//         }
//     }
// }







}
