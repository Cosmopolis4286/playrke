<?php

namespace App\Domains\Games\Bingo\Services\Card;

use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Models\Card\CardPayment\BingoCardPayment;
use App\Models\Games\Common\GameRoom\CurrencyGameRoom;
use App\Domains\Games\Bingo\Services\Room\User\RoomUserRegistrationService;
use App\Domains\Games\Bingo\Services\Payment\CommissionCalculationService;
use App\Domains\Games\Bingo\Services\Room\BingoRoomCapacityGuard;
use App\Domains\Games\Bingo\Services\Room\BingoRoomCardCapacityGuard;
use App\Domains\Games\Bingo\Services\Room\Metrics\RoomMetricsService;
use App\Domains\Games\Bingo\Services\Card\BingoCardManagementService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Servicio especializado en la generación de cartones de bingo.
 *
 * Encapsula la lógica de generación de cartones que estaba en BingoTrait,
 * proporcionando una interfaz limpia y desacoplada.
 *
 * @package App\Domains\Games\Bingo\Services\Card
 * @author Sistema PlayRKE
 * @version 1.0
 */
final class BingoCardGenerationService
{
    private RoomUserRegistrationService $roomUserRegistration;
    private CommissionCalculationService $commissionCalculation;
    private RoomMetricsService $roomMetrics;
    private BingoCardManagementService $cardManagement;

    public function __construct(
        RoomUserRegistrationService $roomUserRegistration,
        CommissionCalculationService $commissionCalculation,
        RoomMetricsService $roomMetrics,
        BingoCardManagementService $cardManagement
    ) {
        $this->roomUserRegistration = $roomUserRegistration;
        $this->commissionCalculation = $commissionCalculation;
        $this->roomMetrics = $roomMetrics;
        $this->cardManagement = $cardManagement;
    }

    /**
     * Genera cartones de bingo usando el flujo simplificado para jugadores.
     *
     * @param array $cardOrder Array con los datos necesarios para generar cartones
     * @return array Resultado de la generación con room_user o error
     * @throws \DomainException Si hay errores en el proceso
     */
    public function generateCards(array $cardOrder): array
    {
        $roomId = $cardOrder['roomId'];

        // Cargamos la sala con su estado directamente
        try {
            $room = BingoRoom::with(['status', 'config'])
                ->where('id', $roomId)
                ->first();
        } catch (\Throwable $e) {
            throw new \DomainException('Error al cargar sala: ' . $e->getMessage());
        }

        if (!$room) {
            throw new \DomainException('Sala no encontrada con ID: ' . $roomId);
        }

        if (!in_array($room->status?->code, ['ACTIVE', 'IN_PROGRESS'])) {
            throw new \DomainException('Estado inválido: "' . ($room->status?->code ?? 'NULL') . '" — status_id: ' . $room->status_id);
        }

        try {
            // Inicialización de variables
            $creator = $cardOrder['creator'];
            $seller = $cardOrder['seller'] ?? null;
            $currency = $cardOrder['currency'];
            $cardCount = $cardOrder['cardCount'];

            // Formatear Monto de Venta
            $saleAmount = round($cardCount * $cardOrder['cardPrice'], 2);
            $exchangeRate = (float) ($cardOrder['exchangeRate'] ?? 1);
            $saleAmountUsd = isset($cardOrder['saleAmountUsd'])
                ? round((float) $cardOrder['saleAmountUsd'], 3)
                : $this->toUsdAmount($saleAmount, $exchangeRate);

            $roomUserResult = $this->roomUserRegistration->registerOrUpdateRoomUser($cardOrder);

            if (!$roomUserResult) {
                throw new \DomainException('Error al registrar usuario en la sala');
            }

            $roomUserId = $roomUserResult['id'];
            $isNewRoomUser = $roomUserResult['isNew'];

            // Anexar atributo al array
            $cardOrder['roomUser'] = $roomUserId;

            // Obtener el connection_type_id real de la config de la sala (Snowflake ID)
            $gameModeId = $room->config?->connection_type_id;

            // Reparto en cascada de la red comercial (vendedor → agencia →
            // comercializadora), en moneda local — ver
            // CommissionCalculationService::networkSplit(). Sin vendedor no
            // hay comisión de red (compra directa de player o del propio commerce).
            $networkSplit = $seller
                ? $this->commissionCalculation->networkSplit($saleAmount, (string) $seller, $roomId)
                : null;
            $sellerSaleCommn = $networkSplit['seller_amount'] ?? 0;
            $cardOrder['networkCommission'] = [
                'subsidiary' => (float) ($networkSplit['subsidiary_amount'] ?? 0),
                'commerce'   => (float) ($networkSplit['commerce_amount'] ?? 0),
            ];

            // Verificar tope de cartones ANTES de tocar total_cards — debe ir
            // antes de updateRoomMetricsWithCommissions() (que ya incrementa
            // total_cards) o el guard llegaría tarde: la venta ya habría
            // quedado contabilizada aunque excediera el tope configurado.
            BingoRoomCardCapacityGuard::assertHasCapacityFor($room, $cardCount);

            // Actualizar acumulado y comisiones de la sala
            $saleBreakdown = null;
            $updateRoomMetrics = $this->roomMetrics->updateRoomMetricsWithCommissions($room, $cardOrder, $saleAmountUsd, $sellerSaleCommn ?? 0, $saleBreakdown);
            // Se guarda en el lote para poder revertir la venta si se reembolsa.
            $cardOrder['financialBreakdown'] = $saleBreakdown;

            if (!$updateRoomMetrics) {
                throw new \DomainException('Error al actualizar los valores de la sala');
            }

            // Verificar cupo antes de registrar nuevo jugador (dentro de la transacción con lock).
            if ($isNewRoomUser) {
                BingoRoomCapacityGuard::assertHasCapacity($room);
                $this->roomMetrics->updatePlayerCount($room);
            }

            // Validar que el usuario autenticado exista en la tabla users
            $authenticatedUserId = auth()->id();
            $sellerExists = \DB::table('users')->where('id', $authenticatedUserId)->exists();
            if (!$sellerExists) {
                throw new \DomainException('El usuario autenticado no existe en el sistema');
            }

            // Usar el usuario autenticado como seller_user_id
            $cardOrder['seller_user_id'] = $authenticatedUserId;

            // Inyectar variante de juego desde la config de la sala
            $cardOrder['gameVariant'] = $room->config?->game_variant ?? \App\Domains\Games\Bingo\ValueObjects\BingoVariant::BINGO_75;

            // Comisión del vendedor de ESTA venta (moneda local) — se persiste en
            // el lote. Antes nunca se guardaba: bingo_card_lots.commission_amount
            // quedaba en NULL y todo lo que la lee (dashboard/ranking/reportes
            // del vendedor, ventas por vendedor del comercio/agencia) mostraba 0.
            $cardOrder['sellerCommissionAmount'] = $sellerSaleCommn ?? 0;
            // Porciones de agencia/comercializadora + snapshot de a quién
            // correspondían al vender (el vendedor puede reasignarse después).
            $cardOrder['subsidiaryCommissionAmount'] = $networkSplit['subsidiary_amount'] ?? 0;
            $cardOrder['commerceCommissionAmount']   = $networkSplit['commerce_amount'] ?? 0;
            $cardOrder['networkSubsidiaryId']        = $networkSplit['subsidiary_id'] ?? null;
            $cardOrder['networkCommerceId']          = $networkSplit['commerce_id'] ?? null;

            // Crear lote de cartones
            $lotID = $this->cardManagement->createCardLot($cardOrder, $saleAmount);

            // Venta fuera del sistema (el dinero lo tiene el comercio): lo que no
            // es comisión de la red ni del creador (RKE + jackpot + bote) queda
            // como deuda del comercio con la plataforma, que es quien paga los
            // premios y acumula el jackpot (ver PlatformReceivableService).
            if (is_array($saleBreakdown['receivable_local'] ?? null)) {
                app(\App\Services\Platform\PlatformReceivableService::class)->record(
                    debtorUserId: (string) $creator['id'],
                    currencyId: (string) $currency,
                    parts: $saleBreakdown['receivable_local'],
                    roomId: (string) $room->id,
                    lotId: (string) $lotID,
                    notes: "Venta – Sala #{$room->uuid_code}",
                    // Venta cruzada: la parte 'creator' es del creador de la sala.
                    creatorPayeeUserId: $saleBreakdown['room_creator_user_id'] ?? null,
                );
            }

            // Generar y almacenar los cartones de bingo (lanza excepción si falla)
            $cardIds = $this->cardManagement->generateAndStoreCards($cardOrder, $lotID);

            // Crear registro de pago para jugadores externos o internos con comprobante
            // (createCardPayment guarda el comprobante — antes además se guardaba
            // una segunda copia huérfana vía storeReceiptImage()).
            if (isset($cardOrder['externalPlayerId']) && $cardOrder['externalPlayerId']) {
                // Jugador externo
                $this->createCardPayment($cardOrder, $roomUserId, $lotID);
            } elseif (isset($cardOrder['receiptNumber']) && $cardOrder['receiptNumber'] && isset($cardOrder['receiptImage'])) {
                // Jugador interno con comprobante de pago
                $this->createCardPayment($cardOrder, $roomUserId, $lotID);
            }

            // Vincular cada cartón recién creado a su ExternalPlayer específico —
            // sin esto, todos los compradores externos de un mismo vendedor
            // comparten room_user_id y es imposible separar "mis cartones" de
            // los de otro comprador (ver App\Models\Roles\Player\External\
            // ExternalPlayerParticipation, patrón polimórfico manual como Audit,
            // pensado para reusarse en otros juegos además de Bingo).
            if (isset($cardOrder['externalPlayerId']) && $cardOrder['externalPlayerId']) {
                foreach ($cardIds as $cardId) {
                    \App\Models\Roles\Player\External\ExternalPlayerParticipation::recordFor(
                        $cardOrder['externalPlayerId'],
                        \App\Domains\Games\Bingo\Models\Card\BingoCard::class,
                        $cardId,
                    );
                }
            }

            // El descuento de billetera para jugadores internos NO ocurre aquí: el único
            // caller que usa creator.type='player' (BingoCardGenerateService::playerBuyFromGameRooms)
            // siempre marca playerLink=true y descuenta vía WalletService::recordCardPurchase()
            // — que ya valida saldo y hace lockForUpdate() sobre la wallet dentro de su propia
            // transacción. La columna 'usdt'/'saldo' del esquema legacy ya no existe en la
            // tabla wallets actual (solo tiene 'balance'), así que ese descuento aquí nunca
            // se ejecuta en la práctica y se eliminó para no mantener una ruta muerta.

            // Registrar moneda en la sala si aún no existe
            if ($gameModeId && !CurrencyGameRoom::existsCurrencyGameRoom($currency, $roomId, 'BINGO', $gameModeId)) {
                CurrencyGameRoom::createNewCurrencyInRoom(
                    ['name' => 'BINGO', 'game_mode' => $gameModeId],
                    $roomId,
                    $currency
                );
            }

            return ['room_user_id' => $roomUserId];

        } catch (\App\Exceptions\Domain\DomainException $e) {
            // Errores de dominio con código propio (comprobante, capacidad,
            // etc.) se propagan intactos para que el usuario vea su mensaje
            // traducido — el catch genérico de abajo los ocultaba.
            throw $e;
        } catch (\Throwable $e) {
            throw new \DomainException('Error en generación de cartones: ' . $e->getMessage());
        }
    }

    /**
     * Genera cartones dentro de una transacción de base de datos.
     *
     * @param array $cardOrder Datos para generación
     * @return array Resultado con room_user
     * @throws \DomainException
     */
    public function generateInTransaction(array $cardOrder): array
    {
        return DB::transaction(fn () => $this->generateCards($cardOrder));
    }

    private function toUsdAmount(float $amount, float $exchangeRate): float
    {
        if ($exchangeRate <= 0) {
            throw new \DomainException('Tasa de cambio inválida para convertir el monto a USD.');
        }

        return round($amount / $exchangeRate, 3);
    }

    /**
     * Crear registro de pago para cartones de jugadores externos.
     *
     * @param array $cardOrder Datos de generación de cartones
     * @param int $roomUserId ID del usuario en la sala
     * @param int $lotID ID del lote de cartones
     * @return void
     */
    private function createCardPayment(array $cardOrder, int $roomUserId, int $lotID): void
    {
        $paymentProof = null;

        try {
            // Comprobante en disco PRIVADO (no servido por la web): antes iba
            // al disco 'public' con un nombre adivinable
            // (receipt_{roomUser}_{lot}_{time}) — cualquiera con la URL veía
            // datos bancarios del comprador. Se sirve solo vía
            // BingoCardPaymentReviewController::proof() con autorización.
            // Nombre aleatorio y extensión según el MIME real, no la del cliente.
            if (isset($cardOrder['receiptImage']) && $cardOrder['receiptImage'] instanceof \Illuminate\Http\UploadedFile) {
                $file = $cardOrder['receiptImage'];
                $paymentProof = $file->storeAs(
                    BingoCardPayment::PROOF_DIRECTORY . '/' . date('Y/m'),
                    \Illuminate\Support\Str::uuid() . '.' . ($file->extension() ?: 'jpg'),
                    BingoCardPayment::PROOF_DISK,
                );

                if (!$paymentProof) {
                    throw new \RuntimeException('No se pudo almacenar el comprobante.');
                }
            }

            // El pago de un ExternalPlayer (comprador sin cuenta, vía vendedor)
            // nace PENDING: BingoGameService::getUserCartonsForExternalPlayer()
            // oculta sus cartones hasta que el comercio/admin lo apruebe (ver
            // BingoCardPaymentReviewService). Un pago interno con comprobante
            // nace APPROVED: es informativo, no gate de acceso.
            //
            // Columnas y estados alineados con el schema real de
            // bingo_card_payments (seller_user_id requerido; enum
            // PENDING/APPROVED/REJECTED/CANCELLED). Antes se escribían
            // creator_user_id (no existe), seller_id (no está en $fillable) y
            // estados COMPLETED/FAILED fuera del enum: el INSERT fallaba SIEMPRE,
            // el error se tragaba, y sin fila PENDING los cartones quedaban
            // jugables sin que nadie revisara el comprobante.
            $isExternalPlayerPayment = !empty($cardOrder['externalPlayerId']);

            BingoCardPayment::create([
                // cash | mobile_payment | bank_transfer (elegido en el punto de
                // venta). Antes siempre 'seller_cash', aunque se exigiera comprobante.
                'payment_method'        => $cardOrder['paymentMethod'] ?? 'cash',
                'description'           => 'Venta de ' . $cardOrder['cardCount'] . ' cartón(es) por vendedor',
                'room_id'               => $cardOrder['roomId'],
                'seller_user_id'        => $cardOrder['seller_user_id'],
                'user_id'               => $cardOrder['seller_user_id'] ?? null,
                'external_player_id'    => $cardOrder['externalPlayerId'] ?? null,
                'currency_id'           => $cardOrder['currency'],
                'card_lot_id'           => $lotID,
                'payment_amount'        => round($cardOrder['cardPrice'] * $cardOrder['cardCount'], 2),
                'payment_proof'         => $paymentProof ?? '',
                'payment_status'        => $isExternalPlayerPayment ? 'PENDING' : 'APPROVED',
                'processed_at'          => $isExternalPlayerPayment ? null : now(),
                'transaction_reference' => $cardOrder['receiptNumber'] ?? null,
            ]);
        } catch (\Throwable $e) {
            if ($paymentProof) {
                \Illuminate\Support\Facades\Storage::disk(BingoCardPayment::PROOF_DISK)->delete($paymentProof);
            }

            Log::error('Error creating card payment', [
                'error'        => $e->getMessage(),
                'room_user_id' => $roomUserId,
                'lot_id'       => $lotID,
            ]);

            // Sin registro de pago no hay revisión: la venta completa se revierte
            // (estamos dentro de la transacción de generateCards()).
            throw \App\Domains\Games\Bingo\Exceptions\Card\BingoCardGenerateException::paymentRecordFailed();
        }
    }
}
