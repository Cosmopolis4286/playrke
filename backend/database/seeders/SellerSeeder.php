<?php

namespace Database\Seeders;

use App\Models\Country;
use App\Models\Roles\Commerce;
use App\Models\Roles\Seller;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Throwable;

class SellerSeeder extends Seeder
{
    public function run() : void
    {
        try {
            // Desactivar restricciones de claves foráneas temporalmente
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            // Limpiar tabla sellers
            Seller::query()->delete();

            // Reactivar restricciones
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            // Obtener datos base
            $country = Country::where('iso_code_2', 'VE')->first();

            if (! $country) {
                $this->command->warn('País Venezuela no encontrado. Ejecute CountrySeeder primero.');

                return;
            }

            // Buscar usuario seller
            $sellerUser = User::where('email', 'seller@playrke.com')->first();

            if (! $sellerUser) {
                $this->command->warn('Usuario seller@playrke.com no encontrado. Ejecute TestUserSeeder primero.');

                return;
            }

            // Buscar commerce para asignar como manager
            $commerce = Commerce::first();

            if (! $commerce) {
                $this->command->warn('Comercio no encontrado. Ejecute CommerceSeeder primero.');

                return;
            }

            // Crear registro seller (el ID se genera automáticamente con SnowflakeId)
            $seller = Seller::create([
                'name' => 'Vendedor Demo',
                'phone' => '+584123456788',
                'commission' => 3.00,
                'user_id' => $sellerUser->id,
                'country_id' => $country->id,
                'manager_user_id' => $commerce->user_id, // El usuario del commerce es el manager
                'status' => 'ACTIVE',
            ]);

            $this->command->info("✅ Vendedor creado exitosamente: {$seller->name}");
            $this->command->info("   → Usuario: {$sellerUser->email}");
            $this->command->info("   → Manager: {$commerce->name}");
        } catch (Throwable $e) {
            $this->command->error("❌ Error en SellerSeeder: {$e->getMessage()}");

            throw $e;
        }
    }
}
