<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Wallets\Wallet;
use App\Repositories\Coins\CoinRepository;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        // Crear el usuario Admin
        $user = User::create([
            'username' => 'admin',
            'first_name' => 'Play',
            'last_name' => 'RKE',
            'country' => 3,
            'time_zone' => null,
            'photo' => null,
            'phone' => null,
            'email' => 'admin@playrke.com',
            'email_verified_at' => now(),
            'password' => Hash::make('EHC-4286-RKE'),
            'remember_token' => null,
            'referral_code' => 'ABC123XYZ456',
            'referred_by' => null,
            'status' => 1,
        ]);

        // Asignar rol Admin usando Spatie
        $user->assignRole('admin'); // Puedes usar el nombre del rol o la instancia Role

        // Obtener $coinId
        $coinId = CoinRepository::getValueByISOCode('VES', 'id');

        // Crear monedero asociado
        Wallet::create(['coin' => $coinId, 'user_id' => $user->id]);
    }
}
