<?php

namespace App\Services\Commerce;

use App\Models\Users;
use App\Models\Wallet;
use App\Models\PhoneNumbers;
use App\Models\Commerce;
use App\Models\CommerceCountriesCurrencies;
use App\Models\UniqueCode;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Carbon\Carbon;

/**
 * Servicio para manejar la creación de Comercializadoras (Commerce).
 */
class CommerceService
{
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
            $password = $this->generatePassword();

            // 👤 Crear usuario
            $user = $this->createUser($data, $password);

            // 🏢 Crear comercio
            $commerce = $this->createCommerceRecord($data, $user->id);

            // 💰 Wallet
            $this->createWallet($user->id, $data['currency']);

            // 📞 Teléfono
            $this->createPhoneNumber($user->id, $data['country'], $data['phone']);

            // 🌍 Moneda principal
            $this->createCommerceCountriesCurrencies($commerce, $user->id);

            // 🔑 Registrar Code UUID
            $this->createUUID($commerce->uuid_code, $user->id, $user->user_type);

            return ['commerce_id' => $commerce, 'password' => $password];
        });
    }

    private function generatePassword(): string
    {
        return bin2hex(random_bytes(4));
    }

    private function createUser(array $data, string $password): User
    {
        return User::create([
            'username'          => $data['username'],
            'user_type'         => $data['user_type'],
            'email'             => $data['email'],
            'password'          => Hash::make($password),
            'country'           => $data['country'],
            'time_zone'         => $data['time_zone'],
            'email_verified_at' => Carbon::now(),
            'referral_code'     => User::getUniqueReferralCode(),
        ]);
    }

    private function createCommerceRecord(array $data, int $userId): Commerce
    {
        return Commerce::create([
            'name'      => $data['name'],
            'phone'     => $data['phone'],
            'currency'      => $data['currency'],
            'country'   => $data['country'],
            'status'    => 1,
            'user'      => $userId,
            'rke_code'  => $data['rke_code'],
            'uuid_code' => $this->generateUUID(),
        ]);
    }

    private function createWallet(int $userId, string $currency): void
    {
        Wallet::create(['currency' => $currency, 'user' => $userId]);
    }

    private function createPhoneNumber(int $userId, string $country, string $phone): void
    {
        PhoneNumbers::create([
            'user'         => $userId,
            'country'      => $country,
            'phone_number' => $phone
        ]);
    }

    private function createCommerceCountriesCurrencies(Commerce $commerce, int $userId): void
    {
        CommerceCountriesCurrencies::create([
            'commerce_id'     => $commerce->id,
            'country'      => $commerce->country,
            'currency'         => $commerce->currency,
            'level'        => '1',
            'creator_user_id' => $userId,
            'status'       => 1
        ]);
    }

    private function createUUID(string $uuid, int $userId, int $userRole): void
    {
        UniqueCode::create([
            'uuid_code'  => $uuid,
            'user'       => $userId,
            'user_type'  => $userRole,
        ]);
    }

    private function generateUUID(): string
    {
        return (string) Str::uuid();
    }
}
