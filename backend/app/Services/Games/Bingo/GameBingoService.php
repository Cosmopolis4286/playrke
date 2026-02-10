<?php

namespace App\Services\Games\Bingo;

use App\DTOs\Roles\Admin\Commerce\AdminCommerceDTO;
use App\Http\Resources\Roles\Admin\Commerce\AdminCommerceResource;
use App\Models\Roles\Commerce;
use App\Services\User\UserPasswordService;
use App\Exceptions\Domain\Roles\Admin\Commerce\AdminCommerceException;

/**
 * Servicio de aplicación para exponer
 * los casos de uso del dominio AdminCommerce.
 *
 * Responsabilidades:
 * - Orquestar casos de uso
 * - Delegar operaciones a servicios de dominio
 * - Mantener controllers desacoplados
 *
 * NO contiene lógica de dominio ni infraestructura.
 */
final class GameBingoService
{
    public function __construct(
        private readonly \App\Services\Games\Bingo\GameRooms\BingoGameRoomsService $gameRoom,
    ) {}

    private function getGameRoomsInfo($creator, $currency, $roomType)
    {
        //app(HandleBingoRoomsService::class)->checkRoomClosure();

        $view = $roomType === 'private-rooms' ? 'private' : 'public';

        switch ($creator['type']) {
            case 'admin': // Admin
                break;
            case 'commerce': // Comercio
                $creatorId = decrypt($creator['id']);

                $isLogged = true;
                $currencySym = Currency::getValue($currency, 'symbol');
                $exchangeRate = CommerceExchangeRate::getCommerceCurrencyValue($creatorId, $currency);

                $currencies = Currency::getCurrenciesById(
                    [...CommerceExchangeRate::getCurrenciesByCommerce($creatorId), 1],
                    ['id', 'iso_code', 'name']
                );

                $routeWithCurrency = [
                    'route' => 'bingo.game-rooms-summary',
                    'parameters' => [
                        'roomType' => $roomType,
                        'creatorType' => 'commerce',
                        'creatorId' => encrypt($creator['id'])
                    ]
                ];

                $gameRoomRoute =  route('bingo.gameRoomsCommerce', ['roomType' => $view, 'currency' => encrypt($currency)]);

                $moreViewData = [
                    'uuidCode' => Commerce::getValueById($creatorId, 'uuid_code'),
                    'countries' => Country::getAllAvailableCountries()
                ];

                break;
            case 'customer': // Cliente

                $creatorId = decrypt($creator['id']);
                $isLogged = $returnBtn = false;

                $customerExchangeRate = CustomerExchangeRate::getById($currency);
                $exchangeRate = $customerExchangeRate->exchange_rate;
                $currencySym = $customerExchangeRate->currency;
                $currencies = CustomerExchangeRate::getCurrenciesIDsByCustomer($creatorId);

                $customer = Customer::getById($creatorId, ['customer_id', 'token']);

                $routeWithCurrency = [
                    'route' => 'bingo.game-rooms-summary',
                    'parameters' => [
                        'roomType' => $roomType,
                        'creatorType' => 'customer',
                        'creatorId' => encrypt($creator['id'])
                    ]
                ];

                $gameRoomRoute =  route(
                    'bingo.game-rooms-customer',
                    [
                        'customer' => $customer->customer_id,
                        'user' => 'available-rooms',
                        'currency' => encrypt($currency)
                    ]
                );

                $gameId = Game::getValueByName('BINGO');

                $customerGameID = CustomerGame::getByGameID($creatorId, $gameId) ?? null;

                $moreViewData = [
                    'customer' => true,
                    'userToken' => $customer->token,
                    'customerGameID' => $customerGameID
                ];

                break;

            default:
                return false;
                break;
        }

        $creatorUser = $creator['userId'];

        $rooms = $this->getInfoRooms($creatorUser, $roomType, $currency);

        $bingoPatterns = BingoPatterns::getAllByUser($creatorUser, ['id', 'name'], 'ACTIVE');

        $bingoPatterns = BingoPatterns::where(function ($query) use ($creatorUser) {
            $query->where('user', $creatorUser)
                ->orWhereNull('user');
        })->where('status', '<>', 'DELETED')->get();

        $timeZoneID = User::getValueById($creatorUser, 'time_zone');
        $timeZone = TimeZone::getById($timeZoneID, ['name', 'utc']);

        $viewData = [
            'bladeLayout' => $isLogged ? 'layouts.auth-layout' : 'layouts.guest-layout',
            'title' => 'Salas de Bingo',
            'subtitle' => $view === 'private' ? 'Privadas' : 'Publicas',
            'view' => strtoupper($view),
            'name' => 'bingo',
            'returnBtn' => $returnBtn ?? true,
            'routeWithCurrency' => $routeWithCurrency,
            'routeRKE' => $this->routeService->getBaseUrl(),
            'creator' => $creator,
            'currencies' => $currencies,
            'currency' => $currency,
            'currencySymbol' => $currencySym,
            'exchangeRate' => $exchangeRate ?? 1,
            'roomType' => $view === 'private' ? 'PV' : 'PB',
            'bingoPatterns' => $bingoPatterns,
            'timeZone' => $timeZone,
            'rooms' => $rooms,
            'gameRoomRoute' => $gameRoomRoute,
            'activeRooms' => $rooms->whereIn('status', [2, 3])->count(),
            'typesPrizePool' => PrizePoolType::all(),
            'gameModes' => GameModes::all()
        ];

        if (isset($moreViewData)) {
            $viewData = array_merge($viewData, $moreViewData);
        }

        return ['route' => 'modules.game.bingo.summary.index', 'data' => $viewData];
    }

    public function gameRoomsSummary()
    {
        $this->gameRoom->context('summary');
    }

    /**
     * Obtener todos los comercios.
     *
     * @return array
     */
    public function listFiltered(): array
    {
        return $this->contex->resolveCommerces()['commerces']['rows'] ?? [];
    }

    /**
     * Crear un comercio.
     *
     * @param AdminCommerceDTO $dto DTO con los datos del comercio
     * @return string Contraseña generada para el comercio
     *
     * @throws AdminCommerceException Si falla la creación
     */
    public function create(AdminCommerceDTO $dto): string
    {
        return $this->crud->create($dto);
    }

    /**
     * Obtener un comercio para visualización.
     *
     * @param Commerce $commerce Model
     * @return AdminCommerceResource Recurso para visualización
     *
     * @throws AdminCommerceException Si el comercio no es válido o no existe
     */
    public function show(Commerce $commerce): AdminCommerceResource
    {
        return $this->crud->show($commerce);
    }

    /**
     * Actualizar un comercio.
     *
     * @param Commerce $commerce Model
     * @param AdminCommerceDTO $dto DTO con datos actualizados
     *
     * @throws AdminCommerceException Si falla la actualización
     */
    public function update(Commerce $commerce, AdminCommerceDTO $dto): void
    {
        $this->crud->update($commerce, $dto);
    }

    /**
     * Eliminar un comercio (soft delete).
     *
     * @param Commerce $commerce Model
     *
     * @throws AdminCommerceException Si falla la eliminación
     */
    public function delete(Commerce $commerce): void
    {
        $this->crud->delete($commerce);
    }

    /**
     * Alternar estado (ACTIVE / INACTIVE) del comercio.
     *
     * @param Commerce $commerce Model
     *
     * @throws AdminCommerceException Si falla el cambio de estado
     */
    public function toggleStatus(Commerce $commerce): void
    {
        $this->crud->toggleStatus($commerce);
    }

    /**
     * Resetear la contraseña del comercio.
     *
     * @param Commerce $commerce Model
     * @return string Nueva contraseña generada
     *
     * @throws AdminCommerceException Si el comercio no está activo o falla el reseteo
     */
    public function resetPassword(Commerce $commerce): string
    {
        if ($commerce->status !== 'ACTIVE') {
            throw AdminCommerceException::passwordResetRequiresActiveStatus();
        }

        return $this->userPasswordService->resetPassword($commerce);
    }

    /**
     * Eliminar un comercio permanentemente (hard delete).
     *
     * @param Commerce $commerce Model
     *
     * @throws AdminCommerceException Si falla la eliminación definitiva
     */
    public function destroy(Commerce $commerce): void
    {
        $this->crud->destroy($commerce);
    }
}
