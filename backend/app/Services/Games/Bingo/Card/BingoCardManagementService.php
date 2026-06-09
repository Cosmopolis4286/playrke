<?php

namespace App\Services\Games\Bingo\Card;

use App\Domains\Games\Bingo\Models\Card\BingoCard;
use App\Domains\Games\Bingo\Models\Card\BingoCardLot;
use App\Domains\Games\Bingo\Models\Card\CardPayment\BingoCardPayment;
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
    public function createCtnsLot(array $cartonData, float $saleAmount): ?string
    {
        try {
            $lotData = [
                'room_id'        => $cartonData['roomId'],
                'user_id'        => $cartonData['creator']['id'],
                'seller_user_id' => $cartonData['seller_user_id'] ?? null,
                'seller_type'    => $cartonData['seller_type'] ?? 'USER',
                'room_user_id'   => $cartonData['roomUser'],
                'currency_id'    => $cartonData['currency'],
                'card_count'     => $cartonData['numOfCtns'],
                'card_price'     => $cartonData['cardPrice'],
                'sale_amount'    => $saleAmount,
                'exchange_rate'  => $cartonData['exchangeRate'] ?? 1,
                'status'         => 'ACTIVE',
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
     * @param array $cartonData Datos del cartón
     * @param string $lotId ID del lote
     * @return bool True si se generaron correctamente
     */
    public function generateAndStoreCtns(array $cartonData, string $lotId): bool
    {
        try {
            $cards = [];
            $numOfCtns  = $cartonData['numOfCtns'];
            $roomId     = $cartonData['roomId'];
            $roomUserId = $cartonData['roomUser'];
            $userId     = $cartonData['creator']['id'];
            $variant    = $cartonData['gameVariant'] ?? BingoVariant::BINGO_75;

            $cardIds = [];
            for ($i = 0; $i < $numOfCtns; $i++) {
                $cardIds[] = BingoCard::generateSnowflakeId();
            }

            for ($i = 0; $i < $numOfCtns; $i++) {
                $cardNumbers = BingoVariant::generateCardMatrix($variant);
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

    /**
     * Crear registro de pago de cartones
     *
     * @param array $cartonData Datos del cartón
     * @param string $lotId ID del lote
     * @param float $saleAmount Monto de la venta
     * @return string|null ID del pago creado
     */
    public function createCtnsPayment(array $cartonData, string $lotId, float $saleAmount): ?string
    {
        try {
            $paymentData = [
                'card_lot_id' => $lotId,
                'room_id' => $cartonData['room'],
                'creator_user_id' => $cartonData['creator']['id'],
                'user_id' => $cartonData['creator']['id'],
                'currency_id' => $cartonData['currency'],
                'payment_amount' => $saleAmount,
                'payment_method' => 'payment_system',
                'payment_status' => 'completed',
                'processed_at' => now(),
                'payment_proof' => 'system-generated',
            ];

            $payment = BingoCardPayment::create($paymentData);
            return $payment->id;
        } catch (\Throwable $e) {
            Log::error('Error al crear pago de cartones: ' . $e->getMessage(), [
                'cartonData' => $cartonData,
                'lotId' => $lotId,
                'saleAmount' => $saleAmount
            ]);
            return null;
        }
    }

    private function generateSerialNumber(): string
    {
        return 'BINGO-' . strtoupper(uniqid()) . '-' . rand(1000, 9999);
    }

    private function generateCardHash(string $roomId, string $roomUserId, array $numbers): string
    {
        $data = $roomId . $roomUserId . json_encode($numbers) . microtime();
        return hash('sha256', $data);
    }

    /**
     * Calcular precio del cartón con tipo de cambio
     *
     * @param float $basePrice Precio base
     * @param bool $setPrice Si es precio fijo
     * @param float $exchangeRate Tipo de cambio
     * @return float Precio calculado
     */
    public function calculateCtnPrice(float $basePrice, bool $setPrice = false, float $exchangeRate = 1.0): float
    {
        try {
            if ($setPrice) {
                return $basePrice;
            }

            return round($basePrice * $exchangeRate, 2);
        } catch (\Throwable $e) {
            Log::error('Error al calcular precio de cartón: ' . $e->getMessage(), [
                'basePrice' => $basePrice,
                'setPrice' => $setPrice,
                'exchangeRate' => $exchangeRate
            ]);
            return $basePrice;
        }
    }

    /**
     * Obtener cartones de un usuario en una sala
     *
     * @param string $roomUserId ID del usuario en la sala
     * @return \Illuminate\Database\Eloquent\Collection
     */
    public function getUserCards(string $roomUserId)
    {
        try {
            return BingoCard::where('room_user_id', $roomUserId)
                ->with('lot')
                ->orderBy('created_at', 'desc')
                ->get();
        } catch (\Throwable $e) {
            Log::error('Error al obtener cartones del usuario: ' . $e->getMessage(), [
                'roomUserId' => $roomUserId
            ]);
            return collect([]);
        }
    }
}
