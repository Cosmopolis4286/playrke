<?php

namespace App\Services\Games\Bingo\Card;

use App\DTOs\Games\Bingo\Card\BingoCardGenerateDTO;
use App\Models\Games\Bingo\Card\CardPrice\BingoCardPrice;
use App\Models\Games\Bingo\Room\BingoRoom;
use App\Models\Roles\Commerce\CommerceExchangeRate;
use App\Models\Roles\Commerce;
use App\Models\Roles\Seller;
use App\Models\Currency;
use App\Models\ExternalPlayers;
use App\Models\Games\Bingo\Creator\BingoCreator;
use App\Models\Games\Common\Utils\GameStatus;
use App\Traits\Games\Bingo\BingoTrait;
use DomainException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Collection;

/**
 * Servicio para la generación de cartones de Bingo.
 *
 * Encapsula el flujo "seller" del legacy BingoController::generateCard()
 * adaptado a la arquitectura de servicios del proyecto.
 */
final class BingoCardGenerateService
{
    use BingoTrait;

    public function __construct(
        private readonly \App\Services\Roles\RoleUserService $roleUserService,
        private readonly \App\Contracts\Roles\Commerce\CommerceRepositoryInterface $commerceRepository,
        private readonly \App\Contracts\Roles\Customer\CustomerRepositoryInterface $customerRepository,
        private readonly \App\Contracts\Roles\Seller\SellerRepositoryInterface $sellerRepository,
    ) {}

    /**
     * Contexto para la vista de generación de cartones.
     *
     * Devuelve las salas activas del commerce que gestiona al seller,
     * filtradas por modo OFFLINE (venta manual), y la comisión del seller.
     *
     * @param int $userId
     *
     * @return array{rooms: Collection, commission: float}
     * @throws DomainException
     */
    public function viewContext(int $userId): array
    {
        // Obtiene el prefijo del rol activo del usuario.
        $role = $this->roleUserService->getActiveUserRolePrefix($userId);

        $user = match ($role) {
            'commerce' => $this->commerceRepository->getByUserId($userId, ['commission']),
            'customer' => $this->commerceRepository->getByUserId($userId, ['commission']),
            'seller' => $this->sellerRepository->getByUserId($userId, ['commission', 'manager_user_id']),
            default => throw new DomainException("Rol inválido: {$role}"),
        };

        if (!$user) {
            throw new DomainException("Usuario '$role' no encontrado.");
        }

        // Definir ID del usuario creador/manejador de la sala
        $creatorUser = $role === 'seller' ? $user->manager_user_id : $userId;

        $statusId = GameStatus::where('code', 'ACTIVE')->value('id');

        // Salas activas del usuario creador/manejador, modo OFFLINE (vendedor genera cartones presencialmente)
        $rooms = BingoRoom::with([
            'config:id,room_id,game_mode_id,card_price_usd',
            'config.gameMode:id,code',
        ])
            ->where('creator_user_id', $creatorUser)
            ->where('status_id', $statusId)   // ACTIVE
            ->whereHas('config.gameMode', fn($q) => $q->where('code', 'OFFLINE'))
            ->orderBy('created_at', 'desc')
            ->get(['id', 'uuid_code', 'prize_pool', 'creator_user_id']);

        return [
            'rooms' => $rooms,
            'commission' => (float) $user->commission,
        ];
    }

    /**
     * Genera cartones de bingo para un jugador, operado por un vendedor.
     *
     * @param BingoCardGenerateDTO $dto
     * @return array{type: string, message: string, playerLink: string}
     * @throws DomainException
     */
    public function generate(BingoCardGenerateDTO $dto): array
    {
        // 1. Obtener seller autenticado
        // manager_user_id = user_id del commerce que gestiona este seller
        $seller = Seller::select(['id', 'commission', 'manager_user_id'])
            ->where('user_id', Auth::id())
            ->first();

        if (!$seller) {
            throw new DomainException('Vendedor no encontrado.');
        }

        $creatorUser = $seller->manager_user_id;
        $commerce    = Commerce::where('user_id', $creatorUser)->value('id');

        if (!$commerce) {
            throw new DomainException('Comercio asociado no encontrado.');
        }

        // 2. Validar sala
        $room = BingoRoom::select('id', 'status_id', 'prize_pool', 'creator_user_id')
            ->where('id', $dto->roomId)
            ->first();

        if (!$room || $room->status_id > 2) {
            throw new DomainException('Sala no encontrada o no disponible.');
        }

        // 3. Obtener moneda por país
        $currency = Currency::getValueByCountry($dto->countryId, 'id');

        if (!$currency) {
            throw new DomainException('Moneda no disponible para el país seleccionado.');
        }

        // 4. Calcular tasa de cambio
        $exchangeRate = round(
            CommerceExchangeRate::getCommerceCurrencyValue($commerce, $currency),
            2
        );

        // 5. Calcular precio del cartón
        $creator = [
            'type'   => 'commerce_id',
            'id'     => $commerce,
            'userId' => $creatorUser,
        ];

        $bingoCreatorId = BingoCreator::getValueByCreatorRoom($creator, $room->id, 'id');
        $customPrice    = BingoCardPrice::getValueCtnPrice($room->id, $currency, $bingoCreatorId);
        $roomCtnPrice   = $room->config?->card_price_usd ?? $room->config?->card_price ?? 0;

        $cardPrice = $customPrice
            ? $this->calculateCtnPrice($customPrice, true)
            : $this->calculateCtnPrice($roomCtnPrice, false, $exchangeRate);

        // 6. Buscar jugador existente en la sala
        $existingRoomUser = ExternalPlayers::select('id', 'link')
            ->where('game', 1)
            ->where('room', $room->id)
            ->where('phone', $dto->playerPhone)
            ->value('room_user');

        $arrInitials = $this->initialCharacters([$dto->playerName, $dto->playerLastName]);

        $arrCtn = [
            'room'          => $room->id,
            'creator'       => $creator,
            'seller'        => $seller->id,
            'commerce'      => $commerce,
            'currency'      => $currency,
            'numOfCtns'     => $dto->numCtns,
            'cardPrice'     => $cardPrice,
            'country'       => $dto->countryId,
            'playerPhone'   => $dto->playerPhone,
            'playerName'    => $dto->playerName,
            'playerLastName' => $dto->playerLastName,
            'initials'      => $arrInitials[0] . $arrInitials[1],
            'exchangeRate'  => $exchangeRate,
        ];

        if ($existingRoomUser) {
            $arrCtn['roomUser'] = $existingRoomUser;
        }

        // 7. Ejecutar transacción atómica
        return DB::transaction(function () use ($arrCtn, $room) {
            $cartonStore = $this->cartonStore($arrCtn);

            if (isset($cartonStore['Error'])) {
                throw new DomainException($cartonStore['Error']);
            }

            if (!isset($arrCtn['roomUser'])) {
                $arrCtn['roomUser'] = $cartonStore;
            }

            $arrCtn = $this->handleExternalPlayers($arrCtn);

            $playerLink = $this->getGameLinkByPlayer($arrCtn['playerLink']);

            return [
                'type'       => 'success',
                'message'    => 'Cartones generados correctamente.',
                'playerLink' => $playerLink,
            ];
        });
    }
}
