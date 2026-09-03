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

            if ($creator['type'] === 'commerce_id' || $seller) {
                $sellerSaleCommn = $this->commissionCalculation->calculateSellerCommission($saleAmount, $seller, $roomId);
            }

            // Verificar tope de cartones ANTES de tocar total_cards — debe ir
            // antes de updateRoomMetricsWithCommissions() (que ya incrementa
            // total_cards) o el guard llegaría tarde: la venta ya habría
            // quedado contabilizada aunque excediera el tope configurado.
            BingoRoomCardCapacityGuard::assertHasCapacityFor($room, $cardCount);

            // Actualizar acumulado y comisiones de la sala
            $updateRoomMetrics = $this->roomMetrics->updateRoomMetricsWithCommissions($room, $cardOrder, $saleAmountUsd, $sellerSaleCommn ?? 0);

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

            // Crear lote de cartones
            $lotID = $this->cardManagement->createCardLot($cardOrder, $saleAmount);

            // Generar y almacenar los cartones de bingo (lanza excepción si falla)
            $cardIds = $this->cardManagement->generateAndStoreCards($cardOrder, $lotID);

            // Guardar comprobante de pago si se proporcionó
            if (isset($cardOrder['receiptImage']) && $cardOrder['receiptImage'] instanceof \Illuminate\Http\UploadedFile) {
                $this->storeReceiptImage($cardOrder, $roomUserId, $lotID);
            }

            // Crear registro de pago para jugadores externos o internos con comprobante
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
        try {
            $paymentProof = null;
            
            // Si hay imagen de comprobante, guardarla y obtener la ruta
            if (isset($cardOrder['receiptImage']) && $cardOrder['receiptImage'] instanceof \Illuminate\Http\UploadedFile) {
                $directory = 'receipts/bingo/' . date('Y/m');
                $fileName = 'receipt_' . $roomUserId . '_' . $lotID . '_' . time() . '.' . $cardOrder['receiptImage']->getClientOriginalExtension();
                $paymentProof = $cardOrder['receiptImage']->storeAs($directory, $fileName, 'public');
            }

            BingoCardPayment::create([
                'payment_method' => 'seller_cash',
                'description' => $cardOrder['receiptNumber'] ?? 'Pago por generación de cartones',
                'room_id' => $cardOrder['roomId'],
                'creator_user_id' => $cardOrder['creator']['id'] ?? null,
                'seller_id' => $cardOrder['seller'] ?? null,
                'user_id' => $cardOrder['seller_user_id'] ?? null,
                'external_player_id' => $cardOrder['externalPlayerId'] ?? null,
                'currency_id' => $cardOrder['currency'],
                'card_lot_id' => $lotID,
                'payment_amount' => $cardOrder['cardPrice'] * $cardOrder['cardCount'],
                'payment_proof' => $paymentProof,
                'payment_status' => 'COMPLETED',
                'processed_at' => now(),
                'transaction_reference' => $cardOrder['receiptNumber'] ?? null,
            ]);

        } catch (\Throwable $e) {
            Log::error('Error creating card payment', [
                'error' => $e->getMessage(),
                'room_user_id' => $roomUserId,
                'lot_id' => $lotID
            ]);
            // No lanzamos excepción para no interrumpir la generación de cartones
        }
    }

    /**
     * Almacena la imagen del comprobante de pago
     *
     * @param array $cardOrder Datos de generación de cartones
     * @param int $roomUserId ID del usuario en la sala
     * @param int $lotID ID del lote de cartones
     * @return void
     */
    private function storeReceiptImage(array $cardOrder, int $roomUserId, int $lotID): void
    {
        try {
            $receiptImage = $cardOrder['receiptImage'];
            $receiptNumber = $cardOrder['receiptNumber'] ?? null;
            
            // Crear directorio si no existe
            $directory = 'receipts/bingo/' . date('Y/m');
            $fileName = 'receipt_' . $roomUserId . '_' . $lotID . '_' . time() . '.' . $receiptImage->getClientOriginalExtension();
            
            // Guardar archivo
            $path = $receiptImage->storeAs($directory, $fileName, 'public');
            
        } catch (\Throwable $e) {
            Log::error('Error storing receipt image', [
                'error' => $e->getMessage(),
                'room_user_id' => $roomUserId,
                'lot_id' => $lotID
            ]);
            // No lanzamos excepción para no interrumpir la generación de cartones
        }
    }
}
