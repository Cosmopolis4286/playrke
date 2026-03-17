<?php

namespace App\Services\Games\Bingo\Card;

use App\Models\Games\Bingo\Room\BingoRoom;
use App\Models\Currency;
use App\Models\Games\Common\GameRoom\CurrencyGameRoom;
use App\Models\Wallet;
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

        $roomId = $arrCtn['room'];
        $room = $this->roomValidation->getRoomDetails($roomId);

        if (!$room) {
            Log::error('Room not available', ['roomId' => $roomId]);
            throw new \DomainException('Sala no disponible');
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

            Log::info('Variables initialized', [
                'creator' => $creator,
                'saleAmount' => $saleAmount,
                'currency' => $currency,
                'numOfCtns' => $numOfCtns
            ]);

            // Registrar o actualizar el usuario en la sala
            $roomUserResult = $this->roomUserRegistration->registerOrUpdateRoomUser($room, $arrCtn);

            Log::info('Room user registration result', ['roomUserResult' => $roomUserResult]);

            if (!$roomUserResult) {
                Log::error('Failed to register user in room');
                throw new \DomainException('Error al registrar usuario en la sala');
            }

            $roomUserId = $roomUserResult['id'];
            $isNewRoomUser = $roomUserResult['isNew'];

            // Anexar atributo al array
            $arrCtn['roomUser'] = $roomUserId;

            $gameMode = $room->Config->game_mode;

            if ($gameMode != 1 && ($creator['type'] === 'commerce_id' || $seller)) {
                // Calcular monto total de venta y comisiones si es un vendedor
                $sellerSaleCommn = $this->commissionCalculation->calculateSellerCommission($saleAmount, $seller, $roomId);
            }

            // Actualizar acumulado y comisiones de la sala
            $updateRoomMetrics = $this->roomMetrics->updateRoomMetricsWithCommissions($room, $arrCtn, $saleAmount, $sellerSaleCommn ?? 0);

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

            // Crear lote de cartones
            $lotID = $this->cardManagement->createCtnsLot($arrCtn, $saleAmount);

            Log::info('Card lot created', ['lotID' => $lotID]);

            // Generar y almacenar los cartones de bingo
            $generateAndStoreCtns = $this->cardManagement->generateAndStoreCtns($arrCtn, $lotID);

            Log::info('Cards generation result', ['generateAndStoreCtns' => $generateAndStoreCtns]);

            if (!$generateAndStoreCtns) {
                Log::error('Failed to generate and store cards');
                throw new \DomainException('Error al generar cartones');
            }

            // Descuento de saldo según tipo de usuario
            if ($gameMode != 2) {
                // Solo los jugadores internos (creator type = 'player') pagan con wallet
                // Los usuarios externos (con playerLink) y vendedores/comercios no pagan con wallet
                if (($creator['type'] === 'player') && !isset($arrCtn['playerLink'])) {
                    // Solo jugadores internos (players) sin playerLink pagan con wallet
                    // Determinar la columna de saldo en función de la moneda
                    $columnBalance = $currency == 1 ? 'usdt' : 'balance';

                    // Descontar monto del saldo del usuario (solo para jugadores internos)
                    $walletUpdated = Wallet::where('user_id', $playerUser)->decrement($columnBalance, $saleAmount);

                    if ($walletUpdated === 0) {
                        Log::error('Failed to discount balance', ['playerUser' => $playerUser, 'amount' => $saleAmount]);
                        throw new DomainException('Error al descontar saldo del usuario');
                    }
                }
            }

            // Definir variables sobre el juego
            $game = ['name' => 'BINGO', 'game_mode' => $gameMode];

            // Registrar nueva moneda en juego de ser necesario
            if (!CurrencyGameRoom::existsCurrencyGameRoom($currency, $roomId, 'BINGO', $gameMode)) {
                CurrencyGameRoom::createNewCurrencyInRoom($game, $roomId, $currency);
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
}
