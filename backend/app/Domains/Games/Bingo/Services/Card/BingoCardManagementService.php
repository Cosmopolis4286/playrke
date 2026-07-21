<?php

namespace App\Domains\Games\Bingo\Services\Card;

use App\Domains\Games\Bingo\Models\Card\BingoCard;
use App\Domains\Games\Bingo\Models\Card\BingoCardLot;
use App\Domains\Games\Bingo\ValueObjects\BingoVariant;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para la gestión de cartones de bingo
 */
class BingoCardManagementService
{
    /**
     * Crear un lote de cartones
     *
     * @param array $cartonData Datos del cartón
     * @param float $saleAmount Monto de la venta
     * @return string|null ID del lote creado
     */
    public function createCardLot(array $cartonData, float $saleAmount): ?string
    {
        try {
            $isCustom = !empty($cartonData['customCardNumbers']);

            $lotData = [
                'room_id'          => $cartonData['roomId'],
                'user_id'          => $cartonData['creator']['id'],
                'seller_user_id'   => $cartonData['seller_user_id'] ?? null,
                'seller_type'      => $cartonData['seller_type'] ?? 'USER',
                'room_user_id'     => $cartonData['roomUser'],
                'currency_id'      => $cartonData['currency'],
                'card_count'       => $cartonData['cardCount'],
                'card_price'       => $cartonData['cardPrice'],
                'sale_amount'      => $saleAmount,
                'exchange_rate'    => $cartonData['exchangeRate'] ?? 1,
                'status'           => 'ACTIVE',
                'has_custom_cards' => $isCustom,
            ];

            $lot = BingoCardLot::create($lotData);
            return $lot->id;
        } catch (\Throwable $e) {
            Log::error('Error al crear lote de cartones: ' . $e->getMessage(), [
                'cartonData' => $cartonData,
                'saleAmount' => $saleAmount,
                'trace' => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    /**
     * Generar y almacenar cartones de bingo
     *
     * @param array $cartonData Datos del cartón. Si incluye `customCardNumbers`
     *   (matriz ya validada por BingoVariant::validateCustomMatrix) y cardCount
     *   es 1, ese cartón usa los números elegidos por el jugador en vez de
     *   generarlos al azar.
     * @param string $lotId ID del lote
     * @return bool True si se generaron correctamente
     */
    public function generateAndStoreCards(array $cartonData, string $lotId): bool
    {
        try {
            $cards = [];
            $cardCount  = $cartonData['cardCount'];
            $roomId     = $cartonData['roomId'];
            $roomUserId = $cartonData['roomUser'];
            $userId     = $cartonData['creator']['id'];
            $variant      = $cartonData['gameVariant'] ?? BingoVariant::BINGO_75;
            // customCardNumbers es array de matrices (una por cartón, 0..cardCount
            // elementos) o null. Los índices 0..count-1 usan la matriz personalizada;
            // el resto (si el array es más corto que cardCount) se genera al azar.
            $customMatrices = $cartonData['customCardNumbers'] ?? null;

            $cardIds = [];
            for ($i = 0; $i < $cardCount; $i++) {
                $cardIds[] = BingoCard::generateSnowflakeId();
            }

            for ($i = 0; $i < $cardCount; $i++) {
                $hasCustomMatrix = $customMatrices !== null && array_key_exists($i, $customMatrices);
                $cardNumbers = $hasCustomMatrix ? $customMatrices[$i] : BingoVariant::generateCardMatrix($variant);
                $hash        = $this->generateCardHash($roomId, $roomUserId, $cardNumbers);

                $cards[] = [
                    'id'              => $cardIds[$i],
                    'card_lot_id'     => $lotId,
                    'room_id'         => $roomId,
                    'creator_user_id' => $userId,
                    'room_user_id'    => $roomUserId,
                    'seller_user_id'  => $cartonData['seller_user_id'] ?? null,
                    'seller_type'     => $cartonData['seller_type'] ?? 'USER',
                    'currency_id'     => $cartonData['currency'],
                    'serial_number'   => $this->generateSerialNumber(),
                    'card_numbers'    => json_encode($cardNumbers),
                    'card_hash'       => $hash,
                    'status'          => 1,
                    'is_validated'    => 0,
                    'is_custom_card'  => $hasCustomMatrix ? 1 : 0,
                    'validated_at'    => null,
                    'created_at'      => now(),
                    'updated_at'      => now(),
                ];
            }

            return BingoCard::insert($cards);
        } catch (\Throwable $e) {
            Log::error('Error al generar y almacenar cartones: ' . $e->getMessage(), [
                'cartonData' => $cartonData,
                'lotId'      => $lotId,
                'trace'      => $e->getTraceAsString(),
            ]);
            throw $e;
        }
    }

    private function generateSerialNumber(): string
    {
        // Str::uuid() usa random_bytes() — entropía criptográfica, sin colisiones bajo carga concurrente.
        // uniqid() tenía resolución de microsegundos: dos workers en el mismo µs generaban el mismo serial.
        return 'BINGO-' . strtoupper(substr(\Illuminate\Support\Str::uuid()->toString(), 0, 13));
    }

    private function generateCardHash(string $roomId, string $roomUserId, array $numbers): string
    {
        // Hash opaco de trazabilidad — NO es un identificador de deduplicación de números.
        // microtime() garantiza unicidad entre cartones del mismo lote aunque los números coincidan.
        $data = $roomId . $roomUserId . json_encode($numbers) . microtime(true);
        return hash('sha256', $data);
    }

}
