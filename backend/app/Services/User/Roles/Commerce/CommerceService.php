<?php

namespace App\Services\User\Roles\Commerce;

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
     */
    public function create(array $data): array
    {
        return DB::transaction(function () use ($data) {

            $plainPassword = $this->generatePassword();

            $user = $this->createUser($data, $plainPassword);
            $user->assignRole('commerce');

            $uniqueCode = $this->createUniqueCode($user->id);

            $commerce = $this->commerceRepository->create([
                'name'           => $data['name'],
                'phone'          => $data['phone'],
                'currency_id'    => $data['currency'],
                'country_id'     => $data['country'],
                'status'         => 'ACTIVE',
                'user_id'        => $user->id,
                'unique_code_id' => $uniqueCode->id,
            ]);

            $this->walletRepository->create([
                'user_id'     => $user->id,
                'currency_id' => $data['currency'],
            ]);

            return [
                'commerce' => $commerce,
                'password' => $plainPassword,
            ];
        });
    }

    /**
     * Obtener comercio con relaciones necesarias para edición / detalle.
     */
    /**
     * Obtener un comercio por ID con sus relaciones
     *
     * @param string $id
     * @return array
     */
    public function getById(string $id): array
    {
        $commerce = Commerce::select([
            'id',
            'name',
            'commission',
            'phone',
            'country_id',
            'currency_id',
            'user_id',
            'unique_code_id',
            'status',
            'created_at',
            'updated_at'
        ])->with([
            'country:id,name',
            'user:id,username,email,time_zone_id',
            'user.timeZone:id,name',
            'currency:id,symbol,description',
            'otherCurrencies:id,symbol,description',
            'allowedGames:id,name'
        ])->findOrFail($id);

        return [
            'commerce' => $commerce,
            'user' => $commerce->user,
            'other_currencies' => $commerce->otherCurrencies->pluck('id')->toArray(),
            'allowed_games' => $commerce->allowedGames->pluck('id')->toArray(),
        ];
    }


    /**
     * Actualizar comercio y relaciones.
     */
    public function update(string $id, array $data): void
    {
        DB::transaction(function () use ($id, $data) {

            $commerce = $this->commerceRepository->findOrFail($id);

            $this->commerceRepository->update($id, array_filter([
                'name'        => $data['name']        ?? null,
                'phone'       => $data['phone']       ?? null,
                'currency_id' => $data['currency']    ?? null,
                'country_id'  => $data['country']     ?? null,
            ]));

            if (isset($data['other_currencies'])) {
                $commerce->otherCurrencies()->sync($data['other_currencies']);
            }

            if (isset($data['allowed_games'])) {
                $commerce->allowedGames()->sync($data['allowed_games']);
            }

            if (isset($data['email']) || isset($data['username'])) {
                $commerce->user()->update(array_filter([
                    'email'    => $data['email']    ?? null,
                    'username' => $data['username'] ?? null,
                ]));
            }
        });
    }

    /**
     * Eliminación física del comercio (hard delete).
     */
    public function delete(string $id): void
    {
        DB::transaction(function () use ($id) {

            $commerce = $this->commerceRepository
                ->findOrFail($id, ['user', 'otherCurrencies', 'allowedGames']);

            if ($commerce->status === 'DELETED') {
                throw new RuntimeException('El comercio ya está eliminado');
            }

            $commerce->otherCurrencies()->detach();
            $commerce->allowedGames()->detach();

            $this->walletRepository->deleteByUserId($commerce->user_id);
            $this->commerceRepository->delete($commerce->id);
            $this->userRepository->delete($commerce->user_id);
        });
    }

    /**
     * Cambiar estado del comercio y su usuario asociado.
     */
    public function changeStatus(string $hashedId, string $status): void
    {
        $commerce = $this->getCommerceByHash($hashedId);

        $this->assertValidStatus($status);

        DB::transaction(function () use ($commerce, $status) {
            $commerce->update(['status' => $status]);
            $commerce->user->update(['status' => $status]);
        });
    }

    /**
     * Resetear contraseña del usuario del comercio.
     */
    public function resetPassword(string $hashedId): string
    {
        $commerce = $this->getCommerceByHash($hashedId);

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
     * Obtener Commerce desde HashId con su usuario.
     */
    private function getCommerceByHash(string $hashedId): Commerce
    {
        $decoded = Hashids::connection('main')->decode($hashedId);

        if (empty($decoded)) {
            throw new InvalidArgumentException('ID inválido');
        }

        return Commerce::with('user')->findOrFail((string) $decoded[0]);
    }

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
     * Generar contraseña inicial.
     */
    private function generatePassword(): string
    {
        return bin2hex(random_bytes(4));
    }

    /**
     * Crear usuario base para el comercio.
     */
    private function createUser(array $data, string $plainPassword): User
    {
        return $this->userRepository->create([
            'username'          => $data['username'],
            'email'             => $data['email'],
            'password'          => Hash::make($plainPassword),
            'country_id'        => $data['country'],
            'time_zone_id'      => $data['time_zone'],
            'email_verified_at' => Carbon::now(),
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
