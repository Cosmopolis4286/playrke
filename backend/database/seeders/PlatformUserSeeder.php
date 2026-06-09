<?php

namespace Database\Seeders;

use App\Models\Currency;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Crea el usuario sistema de la plataforma (playrke_platform) y
 * una wallet activa por cada moneda disponible.
 *
 * Idempotente: se puede ejecutar varias veces sin duplicados.
 */
class PlatformUserSeeder extends Seeder
{
    public const PLATFORM_EMAIL    = 'platform@playrke.system';
    public const PLATFORM_USERNAME = 'playrke_platform';

    public function run(): void
    {
        $user = User::firstOrCreate(
            ['email' => self::PLATFORM_EMAIL],
            [
                'username'              => self::PLATFORM_USERNAME,
                'first_name'            => 'Playrke',
                'last_name'             => 'Platform',
                'password'              => Hash::make(\Str::random(64)),
                'status'                => 'ACTIVE',
                'email_verified_at'     => now(),
                'is_system_account'     => true,
            ]
        );

        // Asegurar que is_system_account esté marcado aunque el usuario ya existiera
        if (! $user->is_system_account) {
            $user->update(['is_system_account' => true]);
        }

        // Revocar cualquier rol que hubiera sido asignado previamente
        $user->syncRoles([]);

        // Crear una wallet por cada moneda activa
        $currencies = Currency::where('status', true)->get();

        foreach ($currencies as $currency) {
            Wallet::firstOrCreate(
                [
                    'user_id'     => $user->id,
                    'currency_id' => $currency->id,
                ],
                [
                    'balance' => 0.00,
                    'status'  => 'ACTIVE',
                ]
            );
        }

        $this->command->info("Usuario plataforma listo: {$user->email} (ID: {$user->id})");
        $this->command->info("Wallets creadas: {$currencies->count()} monedas.");
    }
}
