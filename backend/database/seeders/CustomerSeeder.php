<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\Currency;
use App\Models\Roles\Customer;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Throwable;

class CustomerSeeder extends Seeder
{
    public function run() : void
    {
        try {
            // Desactivar restricciones de claves foráneas temporalmente
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            // Limpiar tabla customers
            Customer::query()->delete();

            // Reactivar restricciones
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            // Obtener datos base
            $country = Country::where('iso_code_2', 'VE')->first();
            $currency = Currency::where('iso_code', 'VES')->first();

            if (! $country || ! $currency) {
                $this->command->warn('País Venezuela o moneda VES no encontrados. Ejecute CountrySeeder y CurrencySeeder primero.');

                return;
            }

            // Buscar usuario customer
            $user = User::where('email', 'customer@playrke.com')->first();

            if (! $user) {
                $this->command->warn('Usuario customer@playrke.com no encontrado. Ejecute TestUserSeeder primero.');

                return;
            }

            // Crear registro customer (el ID se genera automáticamente con SnowflakeId)
            $customer = Customer::create([
                'name' => 'Cliente Demo',
                'customer_id' => 'CUST-' . strtoupper(uniqid()),
                'country_id' => $country->id,
                'currency_id' => $currency->id,
                'user_id' => $user->id,
                'phone' => '+584123456787',
                'api_route' => 'https://api.customer-demo.com',
                'customer_key' => Str::random(32),
                'logo' => null,
                'status' => 'ACTIVE',
            ]);

            $this->command->info("✅ Cliente creado exitosamente: {$customer->name}");
            $this->command->info("   → Usuario: {$user->email}");
            $this->command->info("   → Customer ID: {$customer->customer_id}");
        } catch (Throwable $e) {
            $this->command->error("❌ Error en CustomerSeeder: {$e->getMessage()}");

            throw $e;
        }
    }
}
