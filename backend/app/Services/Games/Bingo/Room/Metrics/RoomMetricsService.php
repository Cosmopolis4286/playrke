<?php

namespace App\Services\Games\Bingo\Room\Metrics;

use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Services\Games\Bingo\Contracts\RoomMetricsInterface;
use App\Services\Platform\PlatformWalletService;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para la gestión de métricas de salas de bingo
 */
class RoomMetricsService implements RoomMetricsInterface
{
    /**
     * Actualizar el contador de jugadores en la sala
     *
     * @param BingoRoom $room Sala a actualizar
     * @return bool True si se actualizó correctamente
     */
    public function updatePlayerCount(BingoRoom $room): bool
    {
        try {
            return $room->increment('total_players') > 0;
        } catch (\Throwable $e) {
            Log::error('Error al actualizar contador de jugadores: ' . $e->getMessage(), [
                'roomId' => $room->id
            ]);
            return false;
        }
    }

    /**
     * Actualizar el contador total de cartones en la sala
     *
     * @param BingoRoom $room Sala a actualizar
     * @param int $additionalCards Cantidad de cartones adicionales
     * @return bool True si se actualizó correctamente
     */
    public function updateTotalCards(BingoRoom $room, int $additionalCards): bool
    {
        try {
            if ($additionalCards <= 0) {
                return false;
            }

            return $room->increment('total_cards', $additionalCards) > 0;
        } catch (\Throwable $e) {
            Log::error('Error al actualizar total de cartones: ' . $e->getMessage(), [
                'roomId' => $room->id,
                'additionalCards' => $additionalCards
            ]);
            return false;
        }
    }

    /**
     * Actualizar el monto total de ventas de la sala
     *
     * @param BingoRoom $room Sala a actualizar
     * @param float $saleAmount Monto de la venta a agregar
     * @return bool True si se actualizó correctamente
     */
    public function updateTotalSales(BingoRoom $room, float $saleAmount): bool
    {
        try {
            if ($saleAmount <= 0) {
                return false;
            }

            return $room->increment('total_sales', $saleAmount) > 0;
        } catch (\Throwable $e) {
            Log::error('Error al actualizar total de ventas: ' . $e->getMessage(), [
                'roomId' => $room->id,
                'saleAmount' => $saleAmount
            ]);
            return false;
        }
    }

    /**
     * Actualizar el pozo de premios de la sala
     *
     * @param BingoRoom $room Sala a actualizar
     * @param float $amountToAdd Monto a agregar al pozo
     * @return bool True si se actualizó correctamente
     */
    public function updatePrizePool(BingoRoom $room, float $amountToAdd): bool
    {
        try {
            if ($amountToAdd <= 0) {
                return false;
            }

            return $room->increment('prize_pool', $amountToAdd) > 0;
        } catch (\Throwable $e) {
            Log::error('Error al actualizar pozo de premios: ' . $e->getMessage(), [
                'roomId' => $room->id,
                'amountToAdd' => $amountToAdd
            ]);
            return false;
        }
    }

    /**
     * Actualizar todas las métricas de la sala después de una venta
     *
     * @param BingoRoom $room Sala a actualizar
     * @param array $saleData Datos de la venta (amount, cards, etc.)
     * @return bool True si se actualizaron todas las métricas correctamente
     */
    public function updateAllMetrics(BingoRoom $room, array $saleData): bool
    {
        try {
            $success = true;

            // Actualizar total de cartones
            if (isset($saleData['numOfCtns']) && $saleData['numOfCtns'] > 0) {
                $success &= $this->updateTotalCards($room, $saleData['numOfCtns']);
            }

            // Actualizar total de ventas
            if (isset($saleData['saleAmount']) && $saleData['saleAmount'] > 0) {
                $success &= $this->updateTotalSales($room, $saleData['saleAmount']);
            }

            // Actualizar pozo de premios
            if (isset($saleData['prizePoolAmount']) && $saleData['prizePoolAmount'] > 0) {
                $success &= $this->updatePrizePool($room, $saleData['prizePoolAmount']);
            }

            // Actualizar contador de jugadores (solo para nuevos usuarios)
            if (isset($saleData['isNewUser']) && $saleData['isNewUser']) {
                $success &= $this->updatePlayerCount($room);
            }

            return $success;
        } catch (\Throwable $e) {
            Log::error('Error al actualizar todas las métricas: ' . $e->getMessage(), [
                'roomId' => $room->id,
                'saleData' => $saleData
            ]);
            return false;
        }
    }

    /**
     * Obtener métricas actuales de la sala
     *
     * @param BingoRoom $room Sala de la cual obtener métricas
     * @return array Array asociativo con todas las métricas
     */
    public function getRoomMetrics(BingoRoom $room): array
    {
        try {
            return [
                'total_players' => $room->total_players ?? 0,
                'total_cards' => $room->total_cards ?? 0,
                'total_sales' => $room->total_sales ?? 0,
                'prize_pool' => $room->prize_pool ?? 0,
                'full_house_prize' => $room->full_house_prize ?? 0,
                'room_commission' => $room->room_commission ?? 0,
                'seller_commission' => $room->seller_commission ?? 0,
                'rke_commission' => $room->rke_commission ?? 0,
            ];
        } catch (\Throwable $e) {
            Log::error('Error al obtener métricas de sala: ' . $e->getMessage(), [
                'roomId' => $room->id
            ]);
            
            return [
                'total_players' => 0,
                'total_cards' => 0,
                'total_sales' => 0,
                'prize_pool' => 0,
                'full_house_prize' => 0,
                'room_commission' => 0,
                'seller_commission' => 0,
                'rke_commission' => 0,
            ];
        }
    }

    /**
     * Actualizar métricas de sala con comisiones (método específico para BingoCardGenerationService)
     *
     * @param BingoRoom $room Sala a actualizar
     * @param array $saleData Datos de la venta
     * @param float $saleAmount Monto de la venta
     * @param float $sellerCommission Comisión del vendedor
     * @return bool True si se actualizó correctamente
     */
    public function updateRoomMetricsWithCommissions(BingoRoom $room, array $saleData, float $saleAmount, float $sellerCommission = 0): bool
    {
        try {
            // Debug: Log input parameters
            Log::info('updateRoomMetricsWithCommissions called', [
                'roomId' => $room->id,
                'saleData_keys' => array_keys($saleData),
                'saleAmount' => $saleAmount,
                'sellerCommission' => $sellerCommission
            ]);
            
            // Persistir mÃ©tricas monetarias de bingo_rooms en USD.
            // Los lotes/pagos pueden conservar moneda local; este agregado siempre queda normalizado.
            $exchangeRate = $saleData['exchangeRate'] ?? 1;
            
            // Prevent division by zero
            if ($exchangeRate <= 0) {
                Log::error('Exchange rate inválido', [
                    'roomId' => $room->id,
                    'exchangeRate' => $exchangeRate
                ]);
                return false;
            }
            
            $saleAmount = isset($saleData['saleAmountUsd'])
                ? round((float) $saleData['saleAmountUsd'], 3)
                : round($saleAmount / $exchangeRate, 3);
            $sellerCommission = isset($saleData['sellerCommissionUsd'])
                ? round((float) $saleData['sellerCommissionUsd'], 3)
                : round($sellerCommission / $exchangeRate, 3);

            // Obtener configuración de la sala
            $bingoConfig = \App\Domains\Games\Bingo\Models\Room\BingoRoomConfig::where('room_id', $room->id)
                ->select(['rke_commission', 'room_commission', 'prize_pool_type_id'])
                ->first();
            
            if (!$bingoConfig) {
                Log::error('Configuración de sala no encontrada', ['roomId' => $room->id]);
                return false;
            }

            $prizePoolType = $bingoConfig->prize_pool_type_id;
            $updateData = [];

            // Si el bote acumulado no es tipo "Premio"
            if ($prizePoolType != 2) {
                // Obtener comisión del creador/casa (comisión por sala)
                $creatorCommission = round(($saleAmount * $bingoConfig->room_commission) / 100, 3);
                // Comisión de PlayRKE
                $rkeCommission = round(($saleAmount * $bingoConfig->rke_commission) / 100, 3);
                // Restar al bote las comisiones
                $prizePool = $saleAmount - $rkeCommission - $creatorCommission - $sellerCommission;

                $updateData = [
                    'rke_commission' => ($room->rke_commission ?? 0) + $rkeCommission,
                    'room_commission' => ($room->room_commission ?? 0) + $creatorCommission,
                    'prize_pool' => ($room->prize_pool ?? 0) + $prizePool,
                    'full_house_prize' => ($room->full_house_prize ?? 0) + $prizePool,
                ];

                // Acreditar RKE a la billetera de plataforma
                if ($rkeCommission > 0) {
                    try {
                        $currencyId = $saleData['currency'] ?? null;
                        if ($currencyId) {
                            app(PlatformWalletService::class)->creditRke(
                                (string) $currencyId,
                                $rkeCommission,
                                'Comisión RKE – Sala #' . $room->uuid_code
                            );
                        }
                    } catch (\Throwable $e) {
                        // No interrumpir la venta si la billetera de plataforma falla
                        Log::warning('PlatformWallet: no se pudo acreditar RKE', [
                            'roomId' => $room->id,
                            'amount' => $rkeCommission,
                            'error'  => $e->getMessage(),
                        ]);
                    }
                }
            }

            // Actualizar métricas generales
            $updateData = array_merge($updateData, [
                'total_sales' => ($room->total_sales ?? 0) + $saleAmount,
                'total_cards' => ($room->total_cards ?? 0) + $saleData['numOfCtns'],
            ]);

            if (isset($saleData['seller'])) {
                $updateData['seller_commission'] = ($room->seller_commission ?? 0) + $sellerCommission;
            }

            return $room->update($updateData);
        } catch (\Throwable $e) {
            Log::error('Error al actualizar métricas con comisiones: ' . $e->getMessage(), [
                'roomId' => $room->id,
                'saleData' => $saleData,
                'saleAmount' => $saleAmount,
                'sellerCommission' => $sellerCommission,
                'trace' => $e->getTraceAsString()
            ]);
            return false;
        }
    }
}
