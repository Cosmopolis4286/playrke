<?php

namespace Database\Seeders;

use App\Models\Roles\Commerce;
use App\Models\User;
use App\Models\Country;
use App\Models\Currency;
use App\Models\Shared\UniqueCode;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class CommerceSeeder extends Seeder
{
    public function run(): void
    {
        try {
            // Desactivar restricciones de claves foráneas temporalmente
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            
            // Limpiar tabla commerces
            Commerce::query()->delete();
            
            // Reactivar restricciones
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            // Obtener datos base
            $country = Country::where('iso_code_2', 'VE')->first();
            $currency = Currency::where('iso_code', 'VES')->first();

            if (!$country || !$currency) {
                $this->command->warn('País Venezuela o moneda VES no encontrados. Ejecute CountrySeeder y CurrencySeeder primero.');
                return;
            }

            // Buscar usuario commerce
            $user = User::where('email', 'commerce@playrke.com')->first();

            if (!$user) {
                $this->command->warn('Usuario commerce@playrke.com no encontrado. Ejecute TestUserSeeder primero.');
                return;
            }

            // Crear código único
            $uniqueCode = $this->generateUniqueCode();

            // Crear registro commerce
            $commerce = Commerce::create([
                'name' => 'Comercio Demo',
                'country_id' => $country->id,
                'phone' => '+584123456789',
                'user_id' => $user->id,
                'commission' => 5.00,
                'unique_code_id' => $uniqueCode,
                'status' => 'ACTIVE',
            ]);

            $this->command->info("✅ Comercio creado exitosamente: {$commerce->name}");
            $this->command->info("   → Usuario: {$user->email}");
            $this->command->info("   → Código único: {$uniqueCode}");

        } catch (\Throwable $e) {
            $this->command->error("❌ Error en CommerceSeeder: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Generar código único para comercio
     */
    private function generateUniqueCode(): string
    {
        return 'COM-' . strtoupper(uniqid());
    }
}
