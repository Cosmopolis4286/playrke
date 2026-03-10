<?php

namespace App\Services\Games\Bingo\Card;

use App\Models\BingoCtns;
use App\Models\BingoCtnsLot;
use App\Models\BingoCtnsPayment;
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
                'room_id' => $cartonData['room'],
                'user_id' => $cartonData['creator']['id'],
                'room_user_id' => $cartonData['roomUser'],
                'quantity' => $cartonData['numOfCtns'],
                'price' => $cartonData['cardPrice'],
                'total_amount' => $saleAmount,
                'currency_id' => $cartonData['currency'],
                'exchange_rate' => $cartonData['exchangeRate'] ?? 1,
                'status' => 'active',
            ];

            $lot = BingoCtnsLot::create($lotData);
            return $lot->id;
        } catch (\Throwable $e) {
            Log::error('Error al crear lote de cartones: ' . $e->getMessage(), [
                'cartonData' => $cartonData,
                'saleAmount' => $saleAmount
            ]);
            return null;
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
            $numOfCtns = $cartonData['numOfCtns'];
            $roomId = $cartonData['room'];
            $roomUserId = $cartonData['roomUser'];

            // Generar cartones únicos
            for ($i = 0; $i < $numOfCtns; $i++) {
                $cardData = $this->generateSingleCard($roomId, $roomUserId);
                $cards[] = [
                    'lot_id' => $lotId,
                    'room_id' => $roomId,
                    'room_user_id' => $roomUserId,
                    'card_data' => json_encode($cardData),
                    'hash' => $cardData['hash'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            // Insertar todos los cartones en lote
            return BingoCtns::insert($cards) > 0;
        } catch (\Throwable $e) {
            Log::error('Error al generar y almacenar cartones: ' . $e->getMessage(), [
                'cartonData' => $cartonData,
                'lotId' => $lotId
            ]);
            return false;
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
                'lot_id' => $lotId,
                'room_id' => $cartonData['room'],
                'user_id' => $cartonData['creator']['id'],
                'amount' => $saleAmount,
                'currency_id' => $cartonData['currency'],
                'exchange_rate' => $cartonData['exchangeRate'] ?? 1,
                'payment_method' => 'wallet', // Por defecto
                'status' => 'completed',
            ];

            $payment = BingoCtnsPayment::create($paymentData);
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

    /**
     * Generar un solo cartón de bingo
     *
     * @param string $roomId ID de la sala
     * @param string $roomUserId ID del usuario en la sala
     * @return array Datos del cartón generado
     */
    private function generateSingleCard(string $roomId, string $roomUserId): array
    {
        // Generar números aleatorios para el cartón
        $cardNumbers = $this->generateCardNumbers();
        
        // Generar hash único para el cartón
        $hash = $this->generateCardHash($roomId, $roomUserId, $cardNumbers);

        return [
            'numbers' => $cardNumbers,
            'hash' => $hash,
            'generated_at' => now()->toISOString(),
        ];
    }

    /**
     * Generar números para un cartón de bingo
     *
     * @return array Array con los números del cartón
     */
    private function generateCardNumbers(): array
    {
        // B: 1-15, I: 16-30, N: 31-45, G: 46-60, O: 61-75
        $card = [
            'B' => $this->generateUniqueNumbers(1, 15, 5),
            'I' => $this->generateUniqueNumbers(16, 30, 5),
            'N' => $this->generateUniqueNumbers(31, 45, 4), // Centro libre
            'G' => $this->generateUniqueNumbers(46, 60, 5),
            'O' => $this->generateUniqueNumbers(61, 75, 5),
        ];

        return $card;
    }

    /**
     * Generar números únicos en un rango
     *
     * @param int $min Valor mínimo
     * @param int $max Valor máximo
     * @param int $count Cantidad de números
     * @return array Array de números únicos
     */
    private function generateUniqueNumbers(int $min, int $max, int $count): array
    {
        $numbers = range($min, $max);
        shuffle($numbers);
        return array_slice($numbers, 0, $count);
    }

    /**
     * Generar hash único para el cartón
     *
     * @param string $roomId ID de la sala
     * @param string $roomUserId ID del usuario
     * @param array $numbers Números del cartón
     * @return string Hash único
     */
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
            return BingoCtns::where('room_user_id', $roomUserId)
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
