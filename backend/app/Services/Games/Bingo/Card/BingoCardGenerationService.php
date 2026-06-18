<?php

namespace App\Services\Games\Bingo\Card;

use App\Domains\Games\Bingo\Models\Room\BingoRoom;
use App\Domains\Games\Bingo\Models\Card\CardPayment\BingoCardPayment;
use App\Models\Currency;
use App\Models\Games\Common\GameRoom\CurrencyGameRoom;
use App\Services\Games\Bingo\Room\Utils\HandleBingoRoomsService;
use App\Services\Games\Bingo\Room\Utils\RoomClosureService;
use App\Services\Games\Bingo\Room\Utils\FinishedRoomService;
use App\Services\Games\Bingo\Round\RoundsService;
use App\Services\Games\Bingo\Card\PrizeCartonService;
use App\Services\Games\Bingo\Round\RoundsService as BingoRoundsService;
use App\Services\Games\Bingo\Card\Customer\CustomerCartonService;
use App\Services\Games\Bingo\Room\User\RoomUserRegistrationService;
use App\Services\Games\Bingo\Room\Validation\RoomValidationService;
use App\Services\Games\Bingo\Payment\CommissionCalculationService;
use App\Services\Games\Bingo\Room\Metrics\RoomMetricsService;
use App\Services\Games\Bingo\Card\BingoCardManagementService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use DomainException;

/**
 * Servicio especializado en la generación de cartones de bingo.
 *
 * Encapsula la lógica de generación de cartones que estaba en BingoTrait,
 * proporcionando una interfaz limpia y desacoplada.
 *
 * @package App\Services\Games\Bingo\Card
 * @author Sistema PlayRKE
 * @version 1.0
 */
final class BingoCardGenerationService
{
    private HandleBingoRoomsService $handleBingoRooms;
    private RoomUserRegistrationService $roomUserRegistration;
    private RoomValidationService $roomValidation;
    private CommissionCalculationService $commissionCalculation;
    private RoomMetricsService $roomMetrics;
    private BingoCardManagementService $cardManagement;

    public function __construct(
        BingoRoundsService $roundsService,
        PrizeCartonService $prizeCartonService,
        CustomerCartonService $customerCartonService,
        RoomUserRegistrationService $roomUserRegistration,
        RoomValidationService $roomValidation,
        CommissionCalculationService $commissionCalculation,
        RoomMetricsService $roomMetrics,
        BingoCardManagementService $cardManagement
    ) {
        $closureService = new RoomClosureService($roundsService, $prizeCartonService);
        // Temporalmente sin BingoWinnerService hasta resolver autocarga
        $finishedService = new FinishedRoomService($roundsService, $customerCartonService, null);
        $this->handleBingoRooms = new HandleBingoRoomsService($closureService, $finishedService);
        $this->roomUserRegistration = $roomUserRegistration;
        $this->roomValidation = $roomValidation;
        $this->commissionCalculation = $commissionCalculation;
        $this->roomMetrics = $roomMetrics;
        $this->cardManagement = $cardManagement;
    }

    /**
     * Genera cartones de bingo usando el flujo simplificado para jugadores.
     *
     * @param array $arrCtn Array con los datos necesarios para generar cartones
     * @return array Resultado de la generación con room_user o error
     * @throws \DomainException Si hay errores en el proceso
     */
    public function generateCards(array $arrCtn): array
    {
        Log::info('generateCards started', ['arrCtn' => $arrCtn]);
        
        // Validar cierre de sala
        $this->handleBingoRooms->checkRoomClosure();

        $roomId = $arrCtn['roomId'];

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
            $creator = $arrCtn['creator'];
            $seller = $arrCtn['seller'] ?? null;
            $playerUser = $arrCtn['playerUser'] ?? null;
            $currency = $arrCtn['currency'];
            $numOfCtns = $arrCtn['numOfCtns'];

            // Formatear Monto de Venta
            $saleAmount = round($numOfCtns * $arrCtn['cardPrice'], 2);
            $exchangeRate = (float) ($arrCtn['exchangeRate'] ?? 1);
            $saleAmountUsd = isset($arrCtn['saleAmountUsd'])
                ? round((float) $arrCtn['saleAmountUsd'], 3)
                : $this->toUsdAmount($saleAmount, $exchangeRate);

            Log::info('Variables initialized', [
                'creator' => $creator,
                'saleAmount' => $saleAmount,
                'saleAmountUsd' => $saleAmountUsd,
                'currency' => $currency,
                'numOfCtns' => $numOfCtns
            ]);

            // Registrar o actualizar el usuario en la sala
            Log::info('Attempting to register room user', [
                'room' => $room->toArray(),
                'arrCtn' => $arrCtn
            ]);
            
            $roomUserResult = $this->roomUserRegistration->registerOrUpdateRoomUser($arrCtn);

            Log::info('Room user registration result', ['roomUserResult' => $roomUserResult]);

            if (!$roomUserResult) {
                Log::error('Failed to register user in room');
                throw new \DomainException('Error al registrar usuario en la sala');
            }

            $roomUserId = $roomUserResult['id'];
            $isNewRoomUser = $roomUserResult['isNew'];

            // Anexar atributo al array
            $arrCtn['roomUser'] = $roomUserId;

            // Obtener el game_mode_id real de la config de la sala (Snowflake ID)
            $gameModeId = $room->config?->game_mode_id;

            if ($creator['type'] === 'commerce_id' || $seller) {
                $sellerSaleCommn = $this->commissionCalculation->calculateSellerCommission($saleAmount, $seller, $roomId);
            }

            // Actualizar acumulado y comisiones de la sala
            $updateRoomMetrics = $this->roomMetrics->updateRoomMetricsWithCommissions($room, $arrCtn, $saleAmountUsd, $sellerSaleCommn ?? 0);

            Log::info('Room metrics update result', ['updateRoomMetrics' => $updateRoomMetrics]);

            if (!$updateRoomMetrics) {
                Log::error('Failed to update room metrics');
                throw new \DomainException('Error al actualizar los valores de la sala');
            }

            // Incrementar total_players solo si es un nuevo jugador en la sala
            if ($isNewRoomUser) {
                $this->roomMetrics->updatePlayerCount($room);
            }

            // Validar que el usuario autenticado exista en la tabla users
            $authenticatedUserId = auth()->id();
            $sellerExists = \DB::table('users')->where('id', $authenticatedUserId)->exists();
            if (!$sellerExists) {
                Log::error('Authenticated user ID does not exist in users table', ['authenticated_user_id' => $authenticatedUserId]);
                throw new \DomainException('El usuario autenticado no existe en el sistema');
            }
            Log::info('Authenticated user ID validated', ['authenticated_user_id' => $authenticatedUserId]);

            // Usar el usuario autenticado como seller_user_id
            $arrCtn['seller_user_id'] = $authenticatedUserId;

            // Inyectar variante de juego desde la config de la sala
            $arrCtn['gameVariant'] = $room->config?->game_variant ?? \App\Domains\Games\Bingo\ValueObjects\BingoVariant::BINGO_75;

            // Crear lote de cartones
            $lotID = $this->cardManagement->createCtnsLot($arrCtn, $saleAmount);

            Log::info('Card lot created', ['lotID' => $lotID]);

            // Generar y almacenar los cartones de bingo (lanza excepción si falla)
            $this->cardManagement->generateAndStoreCtns($arrCtn, $lotID);

            Log::info('Cards generated and stored successfully', ['lotID' => $lotID, 'count' => $arrCtn['numOfCtns']]);

            // Guardar comprobante de pago si se proporcionó
            if (isset($arrCtn['receiptImage']) && $arrCtn['receiptImage'] instanceof \Illuminate\Http\UploadedFile) {
                $this->storeReceiptImage($arrCtn, $roomUserId, $lotID);
            }

            // Crear registro de pago para jugadores externos o internos con comprobante
            if (isset($arrCtn['externalPlayerId']) && $arrCtn['externalPlayerId']) {
                // Jugador externo
                $this->createCardPayment($arrCtn, $roomUserId, $lotID);
            } elseif (isset($arrCtn['receiptNumber']) && $arrCtn['receiptNumber'] && isset($arrCtn['receiptImage'])) {
                // Jugador interno con comprobante de pago
                $this->createCardPayment($arrCtn, $roomUserId, $lotID);
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

            // Si la sala es de un cliente
            if ($creator['type'] === 'customer') {
                // Registrar transacción (se consulta nuevamente el saldo del jugador)
                $roomId = BingoRoom::where('id', $roomId)->value('uuid_code') ?? $this->getCodeUUID('BingoRoom');

                // TODO: Implementar handleTransactionByBET method
                // $transactionBet = $this->handleTransactionByBET(
                //     $game,
                //     $creator['id'],
                //     $roomId,
                //     $playerUser,
                //     $currency,
                //     $saleAmount
                // );

                // if (isset($transactionBet['Error'])) {
                //     throw new \DomainException('Error al registrar transacción');
                // }
            }

            Log::info('generateCards completed successfully', ['room_user_id' => $roomUserId]);

            return ['room_user_id' => $roomUserId];

        } catch (\Exception $e) {
            Log::error('Error in generateCards', [
                'message' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            throw new \DomainException('Error en generación de cartones: ' . $e->getMessage());
        }
    }

    /**
     * Genera cartones dentro de una transacción de base de datos.
     *
     * @param array $arrCtn Datos para generación
     * @param BingoRoom $room Sala de bingo
     * @param float $totalAmount Monto total de la venta
     * @return array Resultado con room_user
     * @throws \DomainException
     */
    public function generateInTransaction(array $arrCtn, BingoRoom $room, float $totalAmount): array
    {
        return DB::transaction(function () use ($arrCtn, $room, $totalAmount) {
            $roomUser = $this->generateCards($arrCtn);
            return $roomUser;
        });
    }

    private function toUsdAmount(float $amount, float $exchangeRate): float
    {
        if ($exchangeRate <= 0) {
            throw new \DomainException('Tasa de cambio invÃ¡lida para convertir el monto a USD.');
        }

        return round($amount / $exchangeRate, 3);
    }

    /**
     * Crear registro de pago para cartones de jugadores externos.
     *
     * @param array $arrCtn Datos de generación de cartones
     * @param int $roomUserId ID del usuario en la sala
     * @param int $lotID ID del lote de cartones
     * @return void
     */
    private function createCardPayment(array $arrCtn, int $roomUserId, int $lotID): void
    {
        try {
            $paymentProof = null;
            
            // Si hay imagen de comprobante, guardarla y obtener la ruta
            if (isset($arrCtn['receiptImage']) && $arrCtn['receiptImage'] instanceof \Illuminate\Http\UploadedFile) {
                $directory = 'receipts/bingo/' . date('Y/m');
                $fileName = 'receipt_' . $roomUserId . '_' . $lotID . '_' . time() . '.' . $arrCtn['receiptImage']->getClientOriginalExtension();
                $paymentProof = $arrCtn['receiptImage']->storeAs($directory, $fileName, 'public');
            }

            BingoCardPayment::create([
                'payment_method' => 'seller_cash',
                'description' => $arrCtn['receiptNumber'] ?? 'Pago por generación de cartones',
                'room_id' => $arrCtn['roomId'],
                'creator_user_id' => $arrCtn['creator']['id'] ?? null,
                'seller_id' => $arrCtn['seller'] ?? null,
                'user_id' => $arrCtn['seller_user_id'] ?? null,
                'external_player_id' => $arrCtn['externalPlayerId'] ?? null,
                'currency_id' => $arrCtn['currency'],
                'card_lot_id' => $lotID,
                'payment_amount' => $arrCtn['cardPrice'] * $arrCtn['numOfCtns'],
                'payment_proof' => $paymentProof,
                'payment_status' => 'COMPLETED',
                'processed_at' => now(),
                'transaction_reference' => $arrCtn['receiptNumber'] ?? null,
            ]);

            Log::info('Card payment created successfully', [
                'room_user_id' => $roomUserId,
                'lot_id' => $lotID,
                'payment_amount' => $arrCtn['cardPrice'] * $arrCtn['numOfCtns'],
                'receipt_number' => $arrCtn['receiptNumber'] ?? null,
                'payment_proof' => $paymentProof,
            ]);

        } catch (\Exception $e) {
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
     * @param array $arrCtn Datos de generación de cartones
     * @param int $roomUserId ID del usuario en la sala
     * @param int $lotID ID del lote de cartones
     * @return void
     */
    private function storeReceiptImage(array $arrCtn, int $roomUserId, int $lotID): void
    {
        try {
            $receiptImage = $arrCtn['receiptImage'];
            $receiptNumber = $arrCtn['receiptNumber'] ?? null;
            
            // Crear directorio si no existe
            $directory = 'receipts/bingo/' . date('Y/m');
            $fileName = 'receipt_' . $roomUserId . '_' . $lotID . '_' . time() . '.' . $receiptImage->getClientOriginalExtension();
            
            // Guardar archivo
            $path = $receiptImage->storeAs($directory, $fileName, 'public');
            
            // Guardar información en log para demostración
            Log::info('Receipt stored', [
                'room_user_id' => $roomUserId,
                'lot_id' => $lotID,
                'receipt_number' => $receiptNumber,
                'file_path' => $path,
                'original_name' => $receiptImage->getClientOriginalName(),
                'file_size' => $receiptImage->getSize(),
            ]);
            
        } catch (\Exception $e) {
            Log::error('Error storing receipt image', [
                'error' => $e->getMessage(),
                'room_user_id' => $roomUserId,
                'lot_id' => $lotID
            ]);
            // No lanzamos excepción para no interrumpir la generación de cartones
        }
    }
}
