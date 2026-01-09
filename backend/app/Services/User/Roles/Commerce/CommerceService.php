<?php

namespace App\Services\User\Roles\Commerce;

use App\DTOs\User\Roles\CommerceDTO;
use App\Models\Users\User;
use App\Models\Users\Roles\Commerce\Commerce;
use App\Models\Users\Shared\UniqueCode;
use App\Repositories\Users\Contracts\UserRepositoryContract;
use App\Repositories\Users\Roles\Commerce\Contracts\CommerceRepositoryContract;
use App\Repositories\Wallets\Contracts\WalletRepositoryContract;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;
use Vinkla\Hashids\Facades\Hashids;

class CommerceService
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

            // 4. Crear wallet principal
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
            'other_currencies' => $commerce->otherCurrencies->pluck('id')->all(),
            'allowed_games'    => $commerce->allowedGames->pluck('id')->all(),
        ];
    }

    /**
     * Actualizar comercio y sus relaciones.
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
            // Sincronizar monedas secundarias
            // -----------------------------
            if ($dto->otherCurrencies !== null) {
                $commerce->otherCurrencies()->sync($dto->otherCurrencies);
            }

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
     * Eliminación física del comercio (hard delete).
     */
    public function delete(Commerce $commerce): void
    {
        DB::transaction(function () use ($commerce) {

            // Cargar relaciones necesarias solo una vez
            $commerce->loadMissing(['otherCurrencies', 'allowedGames']);

            if ($commerce->status === 'DELETED') {
                throw new RuntimeException('El comercio ya está eliminado');
            }

            // Detach relaciones many-to-many
            $commerce->otherCurrencies()->detach();
            $commerce->allowedGames()->detach();

            // ID de usuario
            $userId = $commerce->user_id;

            // Eliminar dependencias
            $this->walletRepository->deleteByUserId($userId);

            // Eliminación física
            $this->commerceRepository->delete($userId);
            $this->userRepository->delete($userId);
        });
    }

    /**
     * Cambiar estado del comercio y su usuario asociado.
     */
    public function changeStatus(Commerce $commerce): void
    {
        $status = $commerce->status === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE';

        $this->assertValidStatus($status);

        DB::transaction(function () use ($commerce, $status) {

            // Evita queries innecesarias
            $commerce->loadMissing('user');

            $commerce->update(['status' => $status]);
            $commerce->user->update(['status' => $status]);
        });
    }

    /**
     * Resetear contraseña del usuario del comercio.
     */
    public function resetPassword(Commerce $commerce): string
    {
        $newPassword = Str::random(12);

        $commerce->user->update([
            'password' => Hash::make($newPassword),
        ]);

        return $newPassword;
    }

    /* =====================================================
     | Helpers internos
     |=====================================================*/

    /**
     * Validar estados permitidos.
     */
    private function assertValidStatus(string $status): void
    {
        if (!in_array($status, ['ACTIVE', 'INACTIVE', 'BLOCKED', 'DELETED'], true)) {
            throw new InvalidArgumentException('Estado inválido');
        }
    }

    /**
     * Crear usuario a partir de DTO.
     */
    private function createUserFromDTO(CommerceDTO $dto, string $plainPassword): User
    {
        return $this->userRepository->create([
            'username'          => $dto->username,
            'email'             => $dto->email,
            'password'          => Hash::make($plainPassword),
            'country_id'        => $dto->country,
            'time_zone_id'      => $dto->timeZone,
            'email_verified_at' => now(),
            'referral_code'     => $this->userRepository->generateUniqueReferralCode(),
            'status'            => 'ACTIVE',
        ]);
    }

    /**
     * Crear código único asociado al usuario.
     */
    private function createUniqueCode(string $userId): UniqueCode
    {
        return UniqueCode::create([
            'uuid_code' => (string) Str::uuid(),
            'rke_code'  => $this->generateUniqueRkeCode(),
            'user_id'   => $userId,
        ]);
    }

    /**
     * Generar RKE único.
     */
    private function generateUniqueRkeCode(): string
    {
        do {
            $code = 'RKE' . strtoupper(Str::random(8));
        } while (UniqueCode::existsRKECode($code));

        return $code;
    }
}
