<?php

namespace App\Services\Games\Bingo\Card\Generate;

use App\DTOs\Games\Bingo\Card\Generate\{BingoCardGenerateContexDTO, BingoCardGenerateDTO};
use App\Models\Games\Bingo\Card\CardPrice\BingoCardPrice;
use App\Models\Games\Bingo\Room\BingoRoom;
use App\Models\Roles\Commerce\CommerceExchangeRate;
use App\Models\Roles\Commerce;
use App\Models\Roles\Seller;
use App\Models\Currency;
use App\Models\ExternalPlayers;
use App\Models\Games\Bingo\Creator\BingoCreator;
use App\Models\Games\Bingo\Room\BingoRoomConfig;
use App\Traits\Games\Bingo\BingoTrait;
use DomainException;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
        private readonly \App\Services\Security\UserContextService $userContext,
        private readonly \App\Services\Roles\RoleUserService $roleUserService,
        private readonly \App\Services\Roles\RoleEntityResolver $roleEntityResolver,
        private readonly \App\Contracts\CountryRepositoryInterface $countries,
        private readonly \App\Contracts\CurrencyRepositoryInterface $currencies,
        private readonly \App\Contracts\Roles\Commerce\CommerceRepositoryInterface $commerceRepository,
        private readonly \App\Contracts\Roles\Customer\CustomerRepositoryInterface $customerRepository,
        private readonly \App\Contracts\Roles\Seller\SellerRepositoryInterface $sellerRepository,
        private readonly \App\Contracts\Games\Common\GameMode\GameModeRepositoryInterface $gameModes,
        private readonly \App\Contracts\Games\Common\Room\RoomTypeRepositoryInterface $roomTypes,
        private readonly \App\Contracts\Games\Common\PrizePool\PrizePoolTypeRepositoryInterface $prizePoolTypes,
    ) {}

    /**
     * Resolver contexto la vista de generar cartones de bingo.
     *
     * @param int $userId ID del usuario
     * @return BingoCardGenerateContexDTO
     *
     * @throws DomainException Si no se puede resolver el contexto
     */
    public function viewContext(int $userId): BingoCardGenerateContexDTO
    {
        return new BingoCardGenerateContexDTO(
            gameModes: $this->gameModes->getActiveGameModes(),
            roomTypes: $this->roomTypes->getActiveTypes(),
            prizePoolTypes: $this->prizePoolTypes->getActiveTypes(),
        );
    }

    /**
     * Obtiene lista de salas de bingo filtradas.
     *
     * Reglas:
     * - Solo salas activas
     * - Del creador o públicas
     * - Filtrado progresivo por date, roomType y gameMode
     *
     * Estrategia:
     * - date obligatorio
     * - roomType opcional (valida existencia)
     * - gameMode opcional (si viene, trae data completa)
     *
     * @param array{
     *     date?: string|null,
     *     roomType?: string|null,
     *     gameMode?: string|null
     * } $filters
     *
     * @return array{
     *     exists: bool,
     *     rooms: array<int, array<string, mixed>>
     * }
     */
    public function fetchRoom(array $filters): array
    {
        $date          = $filters['date']          ?? null;
        $roomType      = $filters['roomType']      ?? null;
        $gameMode      = $filters['gameMode']      ?? null;
        $prizePoolType = $filters['prizePoolType'] ?? null;

        if (!$date) {
            return $this->emptyResponse();
        }

        $creatorUserId = $this->resolveCreatorOrManagerUserId();

        $query = BingoRoom::query()
            ->active()
            ->forDate($date)
            ->accessibleBy($creatorUserId)
            ->filterRoomType($roomType)
            ->filterGameMode($gameMode)
            ->filterPrizePoolType($prizePoolType);

        // Si no hay gameMode → solo validar existencia
        if (!$gameMode) {
            return [
                'exists' => $query->exists(),
                'rooms'  => []
            ];
        }

        $rooms = $query
            ->select([
                'bingo_rooms.id',
                'bingo_rooms.uuid_code',
                'bingo_rooms.prize_pool',
                'bingo_rooms.total_players',
                'bingo_rooms.creator_user_id',
            ])
            ->join('bingo_rooms_config', 'bingo_rooms_config.room_id', '=', 'bingo_rooms.id')
            ->with([
                'config:id,room_id,room_type_id,game_mode_id,card_price_usd,date,time'
            ])
            ->orderBy('bingo_rooms_config.time')
            ->get()
            ->map(fn($room) => [
                'id'              => $room->hashed_id,
                'uuid_code'       => $room->uuid_code,
                'prize_pool'      => $room->prize_pool,
                'total_players'   => $room->total_players,
                'creator_user_id' => $room->creator_user_id,
                'config'          => $room->config,
            ])
            ->toArray();

        return [
            'exists' => !empty($rooms),
            'rooms'  => $rooms
        ];
    }

    /**
     * Obtener lista de paises disponibles para el vendedor.
     * - En base a las monedas permitidas de su manager_user_id, si aplica
     * - En base a las monedas permitidas de la sala de bingo
     *
     * @param BingoRoom $roomId
     * @param string $userId
     * @return JsonResponse
     */
    public function getAvailableCountries(string $roomId, string $userId): array
    {
        // Obtener rol del usuario
        $role = $this->roleUserService->getActiveUserRolePrefix($userId);

        // Resolver la entidad asociada al rol (commerce, customer, etc.)
        $entityId = $this->roleEntityResolver->resolveEntityId($userId, $role);

        // Obtenemos las monedas disponibles para el vendedor (generador de los cartones)
   const function getEntityAvaliableCurrencies($userId,$role) = match ($role) {
            'commerce' => $this->commerceCurrencyRepository->getActiveCurrencies($userId),
            'customer' => $this->customerCurrencyRepository->getActiveCurrencies($userId),
            'seller' => $this->sellerCurrencyRepository->getActiveCurrencies($userId),
            default => throw new DomainException('No existe el rol de usuario: ' . $context),
        };

        $entityAvaliableCurrencies= getentityAvaliableCurrencies($userId,$role)

        if ($role === 'seller') {
            // Resolver el manager del vendedor (manager_user_id)
                    $managerUserId = $this->resolveCreatorOrManagerUserId();

            // Obtener rol del usuario manager del vendedor
            $managerRole = $this->roleUserService->getActiveUserRolePrefix($managerUserId);

            // Resolver la entidad asociada al rol del manager del vendedor (commerce, customer, etc.)
            $managerEntityId = $this->roleEntityResolver->resolveEntityId($managerUserId, $managerRole);

            // Obtenemos las monedas disponibles para el manager del vendedor (generador de los cartones)
            $managerSellerCurrencies = $this->$entityAvaliableCurrencies($managerUserId,$managerRole)
        }

        // Obtenemos las monedas permitidas para la sala de juego (allowed_currencies es tipo JSON)
        $allowedCurrenciesRoom = BingoRoomConfig::where('room', $roomId)->value('allowed_currencies');

        $currenciesAllowedInTheRoom = array_values(array_intersect(
            array_map('strval', $entityAvaliableCurrencies),
          if($role ==='seller')  array_map('strval', $managerSellerCurrencies),
            array_map('strval', $allowedCurrenciesRoom)
        ));

        $countriesIdsAvailable = $this->currencies->getCountryIdsByCurrencies($currenciesAllowedInTheRoom);

        $countries = $this->countries->getByIds($countriesIdsAvailable, ['id', 'name', 'iso_code_2']);

        return ['countries' => $countries];
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
        // $existingRoomUser = ExternalPlayers::select('id', 'link')
        //     ->where('game', 1)
        //     ->where('room', $room->id)
        //     ->where('phone', $dto->playerPhone)
        //     ->value('room_user');

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

        // if ($existingRoomUser) {
        //     $arrCtn['roomUser'] = $existingRoomUser;
        // }

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

    /**
     * Resuelve el creator_user_id/manager_user_id según rol activo.
     */
    private function resolveCreatorOrManagerUserId(): int
    {
        $userId = $this->userContext->resolveUserId();
        $role   = $this->roleUserService->getActiveUserRolePrefix($userId);

        return match ($role) {
            'commerce', 'customer' => $userId,
            'seller' => $this->sellerRepository
                ->getValueByUserId($userId, 'manager_user_id'),
            default => throw new DomainException('Rol inválido'),
        };
    }

    /**
     * Respuesta vacía estándar.
     */
    private function emptyResponse(): array
    {
        return [
            'exists' => false,
            'rooms'  => []
        ];
    }
}
