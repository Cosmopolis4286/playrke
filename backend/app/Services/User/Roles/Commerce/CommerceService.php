<?php

namespace App\Services\User\Roles\Commerce;

use App\DTOs\User\Roles\CommerceDTO;
use App\Models\Users\Roles\Commerce\Commerce;
use App\Repositories\Users\Contracts\UserRepositoryContract;
use App\Repositories\Users\Roles\Commerce\Contracts\CommerceRepositoryContract;
use App\Repositories\Wallets\Contracts\WalletRepositoryContract;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CommerceService extends \App\Services\BaseService
{
    public function __construct(
        protected UserRepositoryContract $userRepository,
        protected CommerceRepositoryContract $commerceRepository,
        protected WalletRepositoryContract $walletRepository,
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
            $user = $this->createUserFromDTO($dto, $plainPassword);
            $user->assignRole('commerce');

            // 2. Crear código único
            $uniqueCode = $this->createUniqueCode($user->id);

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

            // 5. Crear wallet principal
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
     * Obtener un comercio con sus relaciones.
     */
    public function getByCommerce(Commerce $commerce): array
    {
        $commerce->load([
            'country:id,name',
            'user:id,username,email,time_zone_id',
            'user.timeZone:id,name',
            'currency:id,symbol,description',
            'otherCurrencies:id,symbol,description',
            'allowedGames:id,name',
        ]);

        return [
            'commerce'         => $commerce,
            'user'             => $commerce->user,
            'other_currencies' => $commerce->otherCurrencies->where('status', 'ACTIVE')->pluck('id')->all(),
            'allowed_games'    => $commerce->allowedGames->where('status', 'ACTIVE')->pluck('id')->all(),
        ];
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
            if ($dto->allowedGames !== null) {
                $commerce->allowedGames()->sync($dto->allowedGames);
            }

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
    protected function updateSecondaryCurrenciesStatus(Commerce $commerce, ?array $activeCurrencyIds): void
    {
        // Obtener todas las monedas relacionadas con su estado actual en pivot
        $relatedCurrencies = $commerce->otherCurrencies()->withPivot('status')->get();

        if (empty($activeCurrencyIds)) {
            // Si vienen vacías o nulas, inactivar todas las monedas que estén activas y no bloqueadas
            $currencyIdsToInactivate = $relatedCurrencies
                ->filter(fn($c) => $c->pivot->status === 'ACTIVE')
                ->pluck('id')
                ->toArray();
        } else {
            // Si hay monedas activas explícitas, inactivar las que están activas pero no están en esa lista
            $currencyIdsToInactivate = $relatedCurrencies
                ->filter(fn($c) => $c->pivot->status === 'ACTIVE' && !in_array($c->id, $activeCurrencyIds))
                ->pluck('id')
                ->toArray();
        }

        if (!empty($currencyIdsToInactivate)) {
            // Actualizar directamente con query para optimizar
            DB::table('commerces_currencies')
                ->where('commerce_id', $commerce->id)
                ->whereIn('currency_id', $currencyIdsToInactivate)
                ->update(['status' => 'INACTIVE']);
        }

        // Activar o crear las monedas que deben estar activas
        if (!empty($activeCurrencyIds)) {
            $syncData = [];
            foreach ($activeCurrencyIds as $currencyId) {
                $syncData[$currencyId] = ['status' => 'ACTIVE', 'level' => 1];
            }
            $commerce->otherCurrencies()->syncWithoutDetaching($syncData);
        }
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
