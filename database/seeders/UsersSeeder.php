<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        // Registra el Usuario Admin
        $userId = User::create([
            'username' => 'admin',
            'user_type' => 2,
            'first_name' => 'Play',
            'last_name' => 'RKE',
            'country' => 3,
            'time_zone' => null,
            'photo' => null,
            'phone' => null,
            'email' => 'admin@playrke.com',
            'email_verified_at' => now(),
            'password' => Hash::make('EHC-4286-RKE'), // Contraseña encriptada
            'remember_token' => null,
            'referral_code' => 'ABC123XYZ456',
            'referred_by' => null,
            'status' => 1
        ])->id;

        // Registra Monedero
        Wallet::create(['coin' => 1, 'user' => $userId]);
    }
}
