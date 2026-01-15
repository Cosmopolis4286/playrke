<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Country;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class TestUserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Crea usuarios de prueba para desarrollo y testing
     */
    public function run(): void
    {
        // Verificar/crear país por defecto
        $country = Country::firstOrCreate(
            ['iso_code_2' => 'CO'],
            [
                'name' => 'Colombia',
                'iso_code_3' => 'COL',
                'phone_code' => '57',
            ]
        );

        // ====================================
        // USUARIO PLAYER DE PRUEBA
        // ====================================
        $player = User::where('email', 'player@test.com')->first();

        if (!$player) {
            $player = User::create([
                'username' => 'jugador1',
                'email' => 'player@test.com',
                'first_name' => 'Juan',
                'last_name' => 'Pérez',
                'password' => Hash::make('password123'),
                'country_id' => $country->id,
                'referral_code' => 'PLAYER01',
                'email_verified_at' => now(),
            ]);
        }

        // Asignar rol de player si no lo tiene
        if (!$player->hasRole('Jugador')) {
            $player->assignRole('Jugador');
        }

        $this->command->info("✅ Usuario Player creado:");
        $this->command->info("   Email: player@test.com");
        $this->command->info("   Password: password123");
        $this->command->info("   Username: jugador1");

        // ====================================
        // USUARIO ADMIN DE PRUEBA
        // ====================================
        $admin = User::where('email', 'admin@test.com')->first();

        if (!$admin) {
            $admin = User::create([
                'username' => 'admin1',
                'email' => 'admin@test.com',
                'first_name' => 'Admin',
                'last_name' => 'Sistema',
                'password' => Hash::make('admin123'),
                'country_id' => $country->id,
                'referral_code' => 'ADMIN01',
                'email_verified_at' => now(),
            ]);
        }

        // Asignar rol de admin si no lo tiene
        if (!$admin->hasRole('Admin')) {
            $admin->assignRole('Admin');
        }

        $this->command->info("\n✅ Usuario Admin creado:");
        $this->command->info("   Email: admin@test.com");
        $this->command->info("   Password: admin123");
        $this->command->info("   Username: admin1");

        // ====================================
        // USUARIO VIP DE PRUEBA
        // ====================================
        $vip = User::where('email', 'vip@test.com')->first();

        if (!$vip) {
            $vip = User::create([
                'username' => 'vipplayer',
                'email' => 'vip@test.com',
                'first_name' => 'VIP',
                'last_name' => 'Player',
                'password' => Hash::make('vip123'),
                'country_id' => $country->id,
                'referral_code' => 'VIPPLAY01',
                'email_verified_at' => now(),
            ]);
        }

        // Asignar rol de player
        if (!$vip->hasRole('Jugador')) {
            $vip->assignRole('Jugador');
        }

        $this->command->info("\n✅ Usuario VIP creado:");
        $this->command->info("   Email: vip@test.com");
        $this->command->info("   Password: vip123");
        $this->command->info("   Username: vipplayer");

        $this->command->info("\n🎉 Usuarios de prueba creados exitosamente!");
    }
}
