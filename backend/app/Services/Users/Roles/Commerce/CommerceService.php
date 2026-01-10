<?php

namespace App\Services\Users\Roles\Commerce;

use App\DTOs\User\Roles\CommerceDTO;
use App\Http\Resources\Users\Roles\CommerceResource;
use App\Models\Users\Roles\Commerce\Commerce;
use App\Models\Users\Roles\Commerce\CommerceCurrency;
use App\Models\Users\Roles\Commerce\CommerceGame;
use App\Repositories\Users\Contracts\UserRepositoryContract;
use App\Repositories\Users\Roles\Commerce\Contracts\CommerceCurrencyRepositoryContract;
use App\Repositories\Users\Roles\Commerce\Contracts\CommerceGameRepositoryContract;
use App\Repositories\Users\Roles\Commerce\Contracts\CommerceRepositoryContract;
use App\Repositories\Wallets\Contracts\WalletRepositoryContract;
use App\Services\BaseService;
use App\Services\Users\UserService;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CommerceService extends BaseService
{
    public function __construct(
        protected UserRepositoryContract $userRepository,
        protected CommerceRepositoryContract $commerceRepository,
        protected CommerceCurrencyRepositoryContract $currencyRepository,
        protected CommerceGameRepositoryContract $gameRepository,
        protected WalletRepositoryContract $walletRepository,
        protected UserService $userService,
    ) {}

    /**
     * Obtener todos los comercios.
     */
    public function getAll()
    {
        return $this->commerceRepository->all();
    }

    /**
     * Crear un comercio completo (User + Commerce + Wallet + Codes).
     *
     * @param CommerceDTO $dto
     * @return array{commerce:\App\Models\Users\Roles\Commerce\Commerce,password:string}
     */
    public function create(CommerceDTO $dto): array
    {
        return DB::transaction(function () use ($dto) {

            $plainPassword = bin2hex(random_bytes(4));

            // 1. Crear usuario
            $user = $this->userService->createUserFromDTO($dto, $plainPassword);
            $user->assignRole('commerce');

            // 2. Crear código único
            $uniqueCode = $this->userService->createUniqueCode($user->id);

            // 3. Crear comercio
            $commerce = $this->commerceRepository->create([
                'name'           => $dto->name,
                'phone'          => $dto->phone,
                'currency_id'    => $dto->currency,
                'country_id'     => $dto->country,
                'status'         => 'ACTIVE',
                'user_id'        => $user->id,
                'unique_code_id' => $uniqueCode->id,
            ]);

            // 4. Registrar monedas secundarias
            $this->updateSecondaryCurrenciesStatus($commerce, $dto->otherCurrencies);

            // 5. Registrar juegos permitidos
            $this->updateAllowedGamesStatus($commerce, $dto->allowedGames);

            // 6. Crear wallet principal
            $this->walletRepository->create([
                'user_id'     => $user->id,
                'currency_id' => $dto->currency,
            ]);

            return [
                'commerce' => $commerce,
                'password' => $plainPassword,
            ];
        });
    }

    /**
     * Obtener un comercio con sus relaciones para visualización.
     * @param Commerce $commerce
     * @return CommerceResource
     */
    public function getByCommerce(Commerce $commerce): CommerceResource
    {
        $commerce->load([
            'country:id,name',
            'user:id,username,email,time_zone_id',
            'user.timeZone:id,name',
            'currency:id,symbol,description',
            'otherCurrenciesActive:id,symbol,description',
            'allowedGamesActive:id,name',
        ]);

        return new CommerceResource($commerce);
    }

    /**
     * Actualizar comercio y sus relaciones.
     *
     * @param Commerce $commerce
     * @param CommerceDTO $dto
     * @return void
     */
    public function update(Commerce $commerce, CommerceDTO $dto): void
    {
        DB::transaction(function () use ($commerce, $dto) {

            // -----------------------------
            // Actualizar datos del comercio
            // -----------------------------
            $this->commerceRepository->update(
                $commerce->id,
                array_filter([
                    'name'        => $dto->name,
                    'phone'       => $dto->phone,
                    'currency_id' => $dto->currency,
                    'country_id'  => $dto->country,
                ])
            );

            // -----------------------------
            // Sincronizar monedas secundarias con manejo de estado
            // -----------------------------
            $this->updateSecondaryCurrenciesStatus($commerce, $dto->otherCurrencies);

            // -----------------------------
            // Sincronizar juegos permitidos
            // -----------------------------
            $this->updateAllowedGamesStatus($commerce, $dto->allowedGames);

            // -----------------------------
            // Actualizar usuario asociado
            // -----------------------------
            if ($dto->email !== null || $dto->username !== null) {
                $commerce->user()->update(array_filter([
                    'email'    => $dto->email,
                    'username' => $dto->username,
                ]));
            }
        });
    }

    /**
     * Actualiza el estado 'status' de monedas secundarias asociadas a un comercio.
     *
     * @param Commerce $commerce
     * @param array|null $activeCurrencyIds IDs de monedas que deben quedar activas (si es null o vacío, se inactivan todas las activas)
     * @return void
     */
    protected function updateSecondaryCurrenciesStatus(
        Commerce $commerce,
        ?array $activeCurrencyIds
    ): void {
        DB::transaction(function () use ($commerce, $activeCurrencyIds) {

            // Normalizar input
            $activeCurrencyIds = array_values(array_unique(
                array_map('strval', $activeCurrencyIds ?? [])
            ));

            /**
             * 1. Inactivar monedas ACTIVAS que no deben estar activas
             */
            CommerceCurrency::where('commerce_id', $commerce->id)
                ->where('status', 'ACTIVE')
                ->when(
                    !empty($activeCurrencyIds),
                    fn($q) => $q->whereNotIn('currency_id', $activeCurrencyIds)
                )
                ->update([
                    'status' => 'INACTIVE',
                    'updated_at' => now(),
                ]);

            /**
             * 2. Activar monedas EXISTENTES
             */
            if (!empty($activeCurrencyIds)) {
                CommerceCurrency::where('commerce_id', $commerce->id)
                    ->whereIn('currency_id', $activeCurrencyIds)
                    ->update([
                        'status' => 'ACTIVE',
                        'updated_at' => now(),
                    ]);
            }

            /**
             * 3. Insertar monedas que NO existen
             */
            if (!empty($activeCurrencyIds)) {

                $existingIds = CommerceCurrency::where('commerce_id', $commerce->id)
                    ->whereIn('currency_id', $activeCurrencyIds)
                    ->pluck('currency_id')
                    ->all();

                $missingIds = array_diff($activeCurrencyIds, $existingIds);

                if (!empty($missingIds)) {
                    $now = now();

                    CommerceCurrency::insert(
                        array_map(fn($currencyId) => [
                            'commerce_id' => $commerce->id,
                            'currency_id' => $currencyId,
                            'status' => 'ACTIVE',
                            'level' => 1,
                            'created_at' => $now,
                            'updated_at' => $now,
                        ], $missingIds)
                    );
                }
            }
        });
    }

    /**
     * Actualiza el estado 'status' de juegos asociados a un comercio.
     *
     * @param Commerce $commerce
     * @param array|null $activeGameIds IDs de juegos que deben quedar activos
     */
    protected function updateAllowedGamesStatus(
        Commerce $commerce,
        ?array $activeGameIds
    ): void {
        DB::transaction(function () use ($commerce, $activeGameIds) {

            // Normalizar input
            $activeGameIds = array_values(array_unique(
                array_map('strval', $activeGameIds ?? [])
            ));

            /**
             * 1. Inactivar juegos ACTIVOS que no deben estar activos
             */
            CommerceGame::where('commerce_id', $commerce->id)
                ->where('status', 'ACTIVE')
                ->when(
                    !empty($activeGameIds),
                    fn($q) => $q->whereNotIn('game_id', $activeGameIds)
                )
                ->update([
                    'status' => 'INACTIVE',
                    'updated_at' => now(),
                ]);

            /**
             * 2. Activar juegos EXISTENTES
             */
            if (!empty($activeGameIds)) {
                CommerceGame::where('commerce_id', $commerce->id)
                    ->whereIn('game_id', $activeGameIds)
                    ->update([
                        'status' => 'ACTIVE',
                        'updated_at' => now(),
                    ]);
            }

            /**
             * 3. Insertar juegos que NO existen
             */
            if (!empty($activeGameIds)) {

                $existingIds = CommerceGame::where('commerce_id', $commerce->id)
                    ->whereIn('game_id', $activeGameIds)
                    ->pluck('game_id')
                    ->all();

                $missingIds = array_diff($activeGameIds, $existingIds);

                if (!empty($missingIds)) {
                    $now = now();

                    CommerceGame::insert(
                        array_map(fn($gameId) => [
                            'commerce_id' => $commerce->id,
                            'game_id' => $gameId,
                            'status' => 'ACTIVE',
                            'created_at' => $now,
                            'updated_at' => $now,
                        ], $missingIds)
                    );
                }
            }
        });
    }

    /**
     * Eliminación física del comercio (hard delete).
     * @param Commerce $commerce
     * @return void
     */
    public function hardDelete(Commerce $commerce): void
    {
        DB::transaction(function () use ($commerce) {

            $commerce->loadMissing(['otherCurrencies', 'allowedGames', 'user']);

            if ($commerce->status === 'DELETED') {
                throw new RuntimeException('El comercio ya está eliminado');
            }

            // Detach relaciones many-to-many
            $commerce->otherCurrencies()->detach();
            $commerce->allowedGames()->detach();

            // Eliminar wallets del usuario
            $this->walletRepository->deleteByUserId($commerce->user_id);

            // Hard delete del comercio (eliminación física)
            $commerce->forceDelete();

            // Verificar que el usuario no tenga otros comercios activos (sin contar los eliminados físicamente)
            $activeCommercesCount = \App\Models\Users\Roles\Commerce\Commerce::where('user_id', $commerce->user_id)
                ->where('id', '<>', $commerce->id)
                ->where('status', '!=', 'DELETED')
                ->count();

            if ($activeCommercesCount === 0) {
                // Hard delete forzado del usuario
                $user = $commerce->user;
                if ($user) {
                    $user->forceDelete();
                }
            }
        });
    }
}
