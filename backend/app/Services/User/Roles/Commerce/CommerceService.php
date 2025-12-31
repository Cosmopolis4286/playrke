<?php

namespace App\Services\User\Roles\Commerce;

use App\Models\Users\User;
use App\Models\Wallets\Wallet;
use App\Models\Users\Roles\Commerce\Commerce;
use App\Models\Users\Shared\UniqueCode;
use App\Repositories\Users\Contracts\UserRepositoryContract;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * Servicio para manejar la creación de Comercializadoras (Commerce).
 */
class CommerceService
{
    public function __construct(
        protected UserRepositoryContract $userRepository
    ) {}

    /**
     * Crear un nuevo comercio junto con usuario, wallet y configuraciones iniciales.
     *
     * @param array $data Datos validados del request
     * @return array ['commerce_id' => Commerce, 'password' => string]
     * @throws \Throwable
     */
    public function create(array $data): array
    {
        return DB::transaction(function () use ($data) {
            $password = $this->change-passwordord();

            // 👤 Crear usuario
            $user = $this->createUser($data, $password);

            // 🎭 Asignar rol de comercio
            $user->assignRole('commerce');

            // 🔑 Crear código único
            $uniqueCode = $this->createUniqueCode($user->id);

            // 🏢 Crear comercio
            $commerce = $this->createCommerceRecord($data, $user->id, $uniqueCode->id);

            // 💰 Wallet
            $this->createWallet($user->id, $data['currency']);

            return ['commerce_id' => $commerce, 'password' => $password];
        });
    }

    private function change-passwordord(): string
    {
        return bin2hex(random_bytes(4));
    }

    private function createUser(array $data, string $password): User
    {
        return User::create([
            'username'          => $data['username'],
            'email'             => $data['email'],
            'password'          => Hash::make($password),
            'country_id'        => $data['country'],
            'time_zone_id'      => $data['time_zone'],
            'email_verified_at' => Carbon::now(),
            'referral_code'     => $this->userRepository->generateUniqueReferralCode(),
            'status'            => 'ACTIVE',
        ]);
    }

    private function createUniqueCode(string $userId): UniqueCode
    {
        return UniqueCode::create([
            'uuid_code'  => (string) Str::uuid(),
            'rke_code'   => $this->generateUniqueRKECode(),
            'user_id'    => $userId,
        ]);
    }

    private function generateUniqueRKECode(): string
    {
        do {
            $rkeCode = 'RKE' . strtoupper(Str::random(8));
        } while (UniqueCode::existsRKECode($rkeCode));

        return $rkeCode;
    }

    private function createCommerceRecord(array $data, string $userId, string $uniqueCodeId): Commerce
    {
        return Commerce::create([
            'name'           => $data['name'],
            'phone'          => $data['phone'],
            'currency_id'    => $data['currency'],
            'country_id'     => $data['country'],
            'status'         => 'ACTIVE',
            'user_id'        => $userId,
            'unique_code_id' => $uniqueCodeId,
        ]);
    }

    private function createWallet(string $userId, string $currencyId): void
    {
        Wallet::create([
            'currency_id' => $currencyId,
            'user_id'     => $userId
        ]);
    }

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
}
