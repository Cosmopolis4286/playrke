<?php

namespace App\Domains\Games\Bingo\Services\Room\Metrics;

use App\Domains\Games\Bingo\Models\Creator\BingoCreatorSale;
use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Models\Room\BingoRoomConfig;
use App\Domains\Games\Bingo\Contracts\RoomMetricsInterface;
use App\Domains\Games\Bingo\Models\Round\BingoRound;
use App\Domains\Games\Bingo\ValueObjects\BingoRoomPrizeRules;
use App\Models\Games\Common\PrizePool\PrizePoolType;
use App\Services\Jackpot\JackpotLedgerService;
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
            if (isset($saleData['cardCount']) && $saleData['cardCount'] > 0) {
                $success = $this->updateTotalCards($room, $saleData['cardCount']) && $success;
            }

            // Actualizar total de ventas
            if (isset($saleData['saleAmount']) && $saleData['saleAmount'] > 0) {
                $success = $this->updateTotalSales($room, $saleData['saleAmount']) && $success;
            }

            // Actualizar pozo de premios
            if (isset($saleData['prizePoolAmount']) && $saleData['prizePoolAmount'] > 0) {
                $success = $this->updatePrizePool($room, $saleData['prizePoolAmount']) && $success;
            }

            // Actualizar contador de jugadores (solo para nuevos usuarios)
            if (isset($saleData['isNewUser']) && $saleData['isNewUser']) {
                $success = $this->updatePlayerCount($room) && $success;
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
     * @param array|null $breakdown (salida) Desglose exacto de ESTA venta — el
     *        caller lo persiste en el lote (bingo_card_lots.financial_breakdown)
     *        para poder revertirlo si el lote se reembolsa (ver BingoLotSaleReversalService).
     * @return bool True si se actualizó correctamente
     */
    public function updateRoomMetricsWithCommissions(BingoRoom $room, array $saleData, float $saleAmount, float $sellerCommission = 0, ?array &$breakdown = null): bool
    {
        $breakdown = null;
        try {
            // Releer la fila con lockForUpdate() DENTRO de la transacción ya abierta por
            // el caller (BingoCardGenerationService::generateCards(), siempre envuelto en
            // DB::transaction()). Sin este lock, dos compras concurrentes en la misma sala
            // leen el mismo snapshot de total_cards/total_sales/prize_pool/comisiones y la
            // segunda en comitear pisa el incremento de la primera (lost update) -- el
            // jugador paga bien (su wallet sí tiene lock propio en WalletService), pero el
            // pozo de premios y las comisiones de la casa quedan subestimados.
            $room = BingoRoom::where('id', $room->id)->lockForUpdate()->firstOrFail();

            // Persistir métricas monetarias de bingo_rooms en USD.
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
            
            // Montos en moneda local de la venta, antes de normalizar a USD
            // (usados para el desglose por moneda en `metrics_by_currency`).
            $saleAmountLocal = isset($saleData['saleAmountUsd'])
                ? round((float) $saleData['saleAmountUsd'] * $exchangeRate, 3)
                : round($saleAmount, 3);
            $sellerCommissionLocal = isset($saleData['sellerCommissionUsd'])
                ? round((float) $saleData['sellerCommissionUsd'] * $exchangeRate, 3)
                : round($sellerCommission, 3);

            $saleAmount = isset($saleData['saleAmountUsd'])
                ? round((float) $saleData['saleAmountUsd'], 3)
                : round($saleAmount / $exchangeRate, 3);
            $sellerCommission = isset($saleData['sellerCommissionUsd'])
                ? round((float) $saleData['sellerCommissionUsd'], 3)
                : round($sellerCommission / $exchangeRate, 3);

            // Porciones de agencia/comercializadora del reparto en cascada de la
            // red (CommissionCalculationService::networkSplit(), moneda local).
            // Salen del mismo lugar que la del vendedor: antes del bote.
            $subsidiaryCommissionLocal = round((float) ($saleData['networkCommission']['subsidiary'] ?? 0), 3);
            $commerceCommissionLocal   = round((float) ($saleData['networkCommission']['commerce'] ?? 0), 3);
            $subsidiaryCommission      = round($subsidiaryCommissionLocal / $exchangeRate, 3);
            $commerceCommission        = round($commerceCommissionLocal / $exchangeRate, 3);
            $networkOverrideLocal      = $subsidiaryCommissionLocal + $commerceCommissionLocal;
            $networkOverride           = $subsidiaryCommission + $commerceCommission;

            // Obtener configuración de la sala
            $bingoConfig = BingoRoomConfig::where('room_id', $room->id)
                ->select(['rke_commission', 'room_commission', 'prize_pool_type_id', 'jackpot_pct', 'jackpot_type_id'])
                ->first();
            
            if (!$bingoConfig) {
                Log::error('Configuración de sala no encontrada', ['roomId' => $room->id]);
                return false;
            }

            // Reparto de cada venta, igual para TODO tipo de bote: comisión RKE +
            // comisión del creador + comisión del vendedor + aporte al jackpot +
            // resto "para premios". Lo único que cambia según el tipo es a dónde va
            // ese resto (ver BingoRoomPrizeRules):
            //  - Bote en dinero de la sala (prize_pool) — todos los tipos, y
            //    Premio/Servicio HÍBRIDO (tiene alguna ronda %, que lo consume).
            //  - Premio/Servicio PURO (solo rondas de premio físico): no hay bote en
            //    dinero que repartir; el resto queda como fondos del premio que pone
            //    el creador (BingoCreatorSale.prize_pool_amount, metrics prize_funding).
            //
            // Antes había un `if ($prizePoolType != 2)` pensado para excluir "Premio"
            // (id 2 en el seeder original autoincremental) de TODO el reparto; con ids
            // snowflake nunca se cumplía. Excluirlo entero era incorrecto: la venta se
            // habría quedado sin comisión RKE, sin comisión del creador y sin registro.
            $poolCode = PrizePoolType::whereKey($bingoConfig->prize_pool_type_id)->value('code');
            $accruesMoneyPool = $poolCode !== BingoRoomPrizeRules::POOL_PRIZE
                || BingoRound::where('room_id', $room->id)->where('prize_type', 'PCT')->exists();

            $jackpotPct = ($bingoConfig->jackpot_type_id && $bingoConfig->jackpot_pct)
                ? (float) $bingoConfig->jackpot_pct
                : 0.0;

            // Equivalentes en moneda local, para el desglose por moneda.
            $creatorCommissionLocal = round(($saleAmountLocal * $bingoConfig->room_commission) / 100, 3);
            $rkeCommissionLocal = round(($saleAmountLocal * $bingoConfig->rke_commission) / 100, 3);
            $jackpotContributionLocal = round(($saleAmountLocal * $jackpotPct) / 100, 3);
            // El aporte al jackpot también sale del bote en moneda local — antes solo
            // se restaba en USD y el desglose por moneda quedaba inflado con él.
            $prizePoolLocal = $saleAmountLocal - $rkeCommissionLocal - $creatorCommissionLocal - $sellerCommissionLocal - $networkOverrideLocal - $jackpotContributionLocal;

            // Obtener comisión del creador/casa (comisión por sala)
            $creatorCommission = round(($saleAmount * $bingoConfig->room_commission) / 100, 3);
            // Comisión de PlayRKE
            $rkeCommission = round(($saleAmount * $bingoConfig->rke_commission) / 100, 3);
            // Aporte al jackpot (solo si la sala tiene un jackpot asignado Y un % configurado)
            $jackpotContribution = round(($saleAmount * $jackpotPct) / 100, 3);
            // Restar al bote las comisiones y el aporte al jackpot
            $prizePool = $saleAmount - $rkeCommission - $creatorCommission - $sellerCommission - $networkOverride - $jackpotContribution;

            $updateData = [
                'rke_commission' => ($room->rke_commission ?? 0) + $rkeCommission,
                'room_commission' => ($room->room_commission ?? 0) + $creatorCommission,
            ];
            if ($accruesMoneyPool) {
                $updateData['prize_pool'] = ($room->prize_pool ?? 0) + $prizePool;
                $updateData['full_house_prize'] = ($room->full_house_prize ?? 0) + $prizePool;
            }

            // Lo efectivamente acreditado fuera de la sala (para revertirlo exacto
            // si el lote se reembolsa): cualquiera de las dos acreditaciones puede
            // fallar en silencio (try/catch de abajo) y entonces no hay nada que revertir.
            $rkeCreditedLocal = 0.0;
            $rkeReceivableLocal = 0.0;
            $jackpotCreditedUsd = 0.0;

            // Canal de la venta: 'wallet' = el jugador pagó con su billetera (el
            // dinero está en el sistema); 'offline' = venta de vendedor con
            // comprobante o cobrada en mano por el comercio (el dinero lo tiene
            // el comercio). En 'offline' el RKE NO se acredita a la wallet de
            // plataforma: queda como deuda del comercio (PlatformReceivable, la
            // registra BingoCardGenerationService al crear el lote) y se
            // acredita recién al cobrarse. Default 'offline': es lo conservador
            // si un caller nuevo no declara el canal.
            $saleChannel = ($saleData['saleChannel'] ?? 'offline') === 'wallet' ? 'wallet' : 'offline';

            // Venta cruzada: quien vende (comercio dueño de la red) no es el creador
            // de la sala (sala PÚBLICA ajena, o creada por la plataforma).
            $sellerCommerceId = (string) ($saleData['creator']['id'] ?? '');
            $isCrossSale = $saleChannel === 'offline'
                && $sellerCommerceId !== ''
                && (string) $room->creator_user_id !== $sellerCommerceId;

            if ($rkeCommission > 0 && $saleChannel === 'offline') {
                $rkeCommissionLocal = round($rkeCommission * $exchangeRate, 2);
                $rkeReceivableLocal = $rkeCommissionLocal;
            }

            // Acreditar RKE a la billetera de plataforma (convertida a la moneda local de la venta)
            if ($rkeCommission > 0 && $saleChannel === 'wallet') {
                try {
                    $currencyId = $saleData['currency'] ?? null;
                    if ($currencyId) {
                        $rkeCommissionLocal = round($rkeCommission * $exchangeRate, 2);

                        app(PlatformWalletService::class)->creditRke(
                            (string) $currencyId,
                            $rkeCommissionLocal,
                            'Comisión RKE – Sala #' . $room->uuid_code
                        );
                        $rkeCreditedLocal = $rkeCommissionLocal;
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

            // Acreditar el aporte al pozo del jackpot — nunca debe interrumpir la venta.
            if ($jackpotContribution > 0) {
                try {
                    app(JackpotLedgerService::class)->contribute(
                        jackpotTypeId: (string) $bingoConfig->jackpot_type_id,
                        amount: $jackpotContribution,
                        roomId: (string) $room->id,
                        description: 'Aporte jackpot – Sala #' . $room->uuid_code,
                        metadata: ['currency_id' => $saleData['currency'] ?? null],
                    );
                    $jackpotCreditedUsd = $jackpotContribution;
                } catch (\Throwable $e) {
                    Log::warning('JackpotLedger: no se pudo acreditar aporte', [
                        'roomId' => $room->id,
                        'amount' => $jackpotContribution,
                        'error'  => $e->getMessage(),
                    ]);
                }
            }

            // Actualizar métricas generales
            $updateData = array_merge($updateData, [
                'total_sales' => ($room->total_sales ?? 0) + $saleAmount,
                'total_cards' => ($room->total_cards ?? 0) + $saleData['cardCount'],
            ]);

            if (isset($saleData['seller'])) {
                $updateData['seller_commission'] = ($room->seller_commission ?? 0) + $sellerCommission;
            }
            if ($subsidiaryCommission > 0) {
                $updateData['subsidiary_commission'] = ($room->subsidiary_commission ?? 0) + $subsidiaryCommission;
            }
            if ($commerceCommission > 0) {
                $updateData['commerce_commission'] = ($room->commerce_commission ?? 0) + $commerceCommission;
            }

            // Desglose por moneda (en moneda local de la venta)
            $currencyId = (string) ($saleData['currency'] ?? '');
            if ($currencyId !== '') {
                $metricsByCurrency = $room->metrics_by_currency ?? [];
                $current = $metricsByCurrency[$currencyId] ?? [
                    'total_sales' => 0,
                    'total_cards' => 0,
                    'prize_pool' => 0,
                    'full_house_prize' => 0,
                    'rke_commission' => 0,
                    'room_commission' => 0,
                    'seller_commission' => 0,
                    'jackpot_contribution' => 0,
                ];

                $current['total_sales'] = round($current['total_sales'] + $saleAmountLocal, 3);
                $current['total_cards'] += $saleData['cardCount'];
                if ($accruesMoneyPool) {
                    $current['prize_pool'] = round($current['prize_pool'] + $prizePoolLocal, 3);
                    $current['full_house_prize'] = round($current['full_house_prize'] + $prizePoolLocal, 3);
                } else {
                    // Premio/Servicio puro: fondos del premio físico (los pone el creador).
                    $current['prize_funding'] = round(($current['prize_funding'] ?? 0) + $prizePoolLocal, 3);
                }
                $current['rke_commission'] = round($current['rke_commission'] + $rkeCommissionLocal, 3);
                $current['room_commission'] = round($current['room_commission'] + $creatorCommissionLocal, 3);
                // Clave nueva: salas con ventas previas no la tienen, de ahí el ?? 0.
                $current['jackpot_contribution'] = round(($current['jackpot_contribution'] ?? 0) + $jackpotContributionLocal, 3);

                if (isset($saleData['seller'])) {
                    $current['seller_commission'] = round($current['seller_commission'] + $sellerCommissionLocal, 3);
                }
                // Claves nuevas: salas con ventas previas no las tienen, de ahí el ?? 0.
                if ($subsidiaryCommissionLocal > 0) {
                    $current['subsidiary_commission'] = round(($current['subsidiary_commission'] ?? 0) + $subsidiaryCommissionLocal, 3);
                }
                if ($commerceCommissionLocal > 0) {
                    $current['commerce_commission'] = round(($current['commerce_commission'] ?? 0) + $commerceCommissionLocal, 3);
                }

                $metricsByCurrency[$currencyId] = $current;
                $updateData['metrics_by_currency'] = $metricsByCurrency;
            }

            $result = $room->update($updateData);

            // Registrar venta por sala/creador/moneda para el panel de Comisiones RKE
            // (para todo tipo de bote — ver comentario del reparto más arriba).
            $creatorSale = null;
            try {
                $creatorUserId = (string) ($saleData['creator']['id'] ?? null);
                $currencyIdStr = (string) ($saleData['currency'] ?? '');

                if ($creatorUserId && $currencyIdStr) {
                    // $rkeCommissionLocal puede haber sido sobreescrito en el bloque
                    // de PlatformWallet con un valor recalculado desde USD.
                    // Recalcular prize_pool con ese valor final para que la integridad
                    // sale_amount == rke + room + seller + jackpot + prize_pool se mantenga.
                    // prize_pool_amount = lo destinado a premios: bote en dinero, o
                    // fondos del premio físico en salas Premio/Servicio puras.
                    $finalRkeLocal    = $rkeCommissionLocal;
                    $finalPrizeLocal  = round($saleAmountLocal - $finalRkeLocal - $creatorCommissionLocal - $sellerCommissionLocal - $networkOverrideLocal - $jackpotContributionLocal, 3);

                    $existing = BingoCreatorSale::where('room_id', $room->id)
                        ->where('creator_user_id', $creatorUserId)
                        ->where('currency_id', $currencyIdStr)
                        ->first();

                    if ($existing) {
                        $existing->increment('sale_amount',      $saleAmountLocal);
                        $existing->increment('rke_commission',   $finalRkeLocal);
                        $existing->increment('room_commission',  $creatorCommissionLocal);
                        $existing->increment('seller_commission',$sellerCommissionLocal);
                        $existing->increment('subsidiary_commission', $subsidiaryCommissionLocal);
                        $existing->increment('commerce_commission', $commerceCommissionLocal);
                        $existing->increment('jackpot_contribution', $jackpotContributionLocal);
                        $existing->increment('prize_pool_amount',$finalPrizeLocal);
                    } else {
                        BingoCreatorSale::create([
                            'room_id'           => $room->id,
                            'creator_user_id'   => $creatorUserId,
                            'currency_id'       => $currencyIdStr,
                            'sale_amount'       => $saleAmountLocal,
                            'rke_commission'    => $finalRkeLocal,
                            'room_commission'   => $creatorCommissionLocal,
                            'seller_commission' => $sellerCommissionLocal,
                            'subsidiary_commission' => $subsidiaryCommissionLocal,
                            'commerce_commission'   => $commerceCommissionLocal,
                            'jackpot_contribution' => $jackpotContributionLocal,
                            'prize_pool_amount' => $finalPrizeLocal,
                        ]);
                    }

                    $creatorSale = [
                        'creator_user_id'      => $creatorUserId,
                        'sale_amount'          => round($saleAmountLocal, 3),
                        'rke_commission'       => round($finalRkeLocal, 3),
                        'room_commission'      => round($creatorCommissionLocal, 3),
                        'seller_commission'    => round($sellerCommissionLocal, 3),
                        'subsidiary_commission' => round($subsidiaryCommissionLocal, 3),
                        'commerce_commission'   => round($commerceCommissionLocal, 3),
                        'jackpot_contribution' => round($jackpotContributionLocal, 3),
                        'prize_pool_amount'    => $finalPrizeLocal,
                    ];
                }
            } catch (\Throwable $e) {
                Log::warning('No se pudo registrar BingoCreatorSale', [
                    'roomId' => $room->id,
                    'error'  => $e->getMessage(),
                ]);
            }

            // Desglose exacto de lo que ESTA venta sumó en cada lugar — se guarda en
            // el lote para revertirlo tal cual si se reembolsa (sala cancelada o pago
            // rechazado). Recalcularlo después con los % de la sala no sirve: la
            // comisión y el jackpot siguen editables tras vender.
            // IMPORTANTE: todo monto nuevo que esta venta sume a bingo_rooms,
            // metrics_by_currency o bingo_creator_sales (ej. comisiones de agencia /
            // comercializadora) debe agregarse acá con el MISMO nombre de columna/clave:
            // BingoLotSaleReversalService revierte genéricamente cada clave del desglose.
            $breakdown = [
                'version'     => 1,
                'currency_id' => $currencyId !== '' ? $currencyId : null,
                'room_usd' => [
                    'total_sales'       => round($saleAmount, 3),
                    'total_cards'       => (int) $saleData['cardCount'],
                    'rke_commission'    => $rkeCommission,
                    'room_commission'   => $creatorCommission,
                    'seller_commission' => isset($saleData['seller']) ? $sellerCommission : 0.0,
                    'subsidiary_commission' => $subsidiaryCommission,
                    'commerce_commission'   => $commerceCommission,
                    'prize_pool'        => $accruesMoneyPool ? $prizePool : 0.0,
                ],
                'room_local' => $currencyId !== '' ? [
                    'total_sales'          => round($saleAmountLocal, 3),
                    'total_cards'          => (int) $saleData['cardCount'],
                    'prize_pool'           => $accruesMoneyPool ? round($prizePoolLocal, 3) : 0.0,
                    'prize_funding'        => $accruesMoneyPool ? 0.0 : round($prizePoolLocal, 3),
                    'rke_commission'       => round($rkeCommissionLocal, 3),
                    'room_commission'      => round($creatorCommissionLocal, 3),
                    'seller_commission'    => isset($saleData['seller']) ? round($sellerCommissionLocal, 3) : 0.0,
                    'subsidiary_commission' => round($subsidiaryCommissionLocal, 3),
                    'commerce_commission'   => round($commerceCommissionLocal, 3),
                    'jackpot_contribution' => round($jackpotContributionLocal, 3),
                ] : null,
                'creator_sale'        => $creatorSale,
                'channel'             => $saleChannel,
                'rke_credited_local'  => $rkeCreditedLocal,
                'rke_receivable_local'=> $rkeReceivableLocal,
                // Venta fuera del sistema: lo que el comercio retuvo en efectivo y le
                // debe a la plataforma, que es quien paga los premios a billeteras y
                // acumula el jackpot. NO incluye las comisiones de la red (vendedor,
                // agencia, comercializadora) ni la del creador: esas se las queda la
                // red. prize_pool solo si la sala acumula bote en dinero (en
                // Premio/Servicio pura esos fondos son del creador, que pone el premio).
                'receivable_local' => $saleChannel === 'offline' ? [
                    'rke'        => round($rkeReceivableLocal, 2),
                    'jackpot'    => $jackpotCreditedUsd > 0 ? round($jackpotContributionLocal, 2) : 0.0,
                    'prize_pool' => $accruesMoneyPool ? round(max(0, $prizePoolLocal), 2) : 0.0,
                    // Venta cruzada: un comercio vende cartones de una sala PÚBLICA
                    // creada por otro (o por la plataforma). Cobra en efectivo también
                    // la comisión del creador, que no es suya → entra en la deuda y la
                    // plataforma se la paga al creador (custodia hasta el cierre).
                    // En sus propias salas se la queda, como siempre.
                    'creator'    => $isCrossSale ? round($creatorCommissionLocal, 2) : 0.0,
                ] : null,
                // A quién le corresponde la parte 'creator' de la deuda.
                'room_creator_user_id' => $room->creator_user_id ? (string) $room->creator_user_id : null,
                'jackpot_type_id'     => $jackpotCreditedUsd > 0 ? (string) $bingoConfig->jackpot_type_id : null,
                'jackpot_credited_usd'=> $jackpotCreditedUsd,
            ];

            return $result;
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
