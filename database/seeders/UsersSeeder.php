<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsersSeeder extends Seeder
{
    public function run(): void
    {
        // Registra el Usuario Admin
        $userId = DB::table('users')->insertGetId([
            'username' => 'admin',
            'user_type' => 2,
            'first_name' => 'Play',
            'last_name' => 'RKE',
            'country' => 234,
            'time_zone' => null,
            'photo' => null,
            'phone' => null,
            'email' => 'admin@playrke.com',
            'email_verified_at' => now(),
            'password' => Hash::make('12345678-RKE'), // Contraseña encriptada
            'remember_token' => null,
            'referral_code' => 'ABC123XYZ456',
            'referred_by' => null,
            'status' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Registra Monedero
        DB::table('wallets')->insert([
            'coin' => 2,
            'balance' => 0,
            'usdt' => 0,
            'user' => $userId,
        ]);
    }
}
