<?php

use App\Models\Module;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

/*
 * Lanzamiento v1: Trivia y Marketplace arrancan apagados para jugadores
 * (EnsureModuleActive bloquea sus rutas y el menú los oculta). Admin y
 * superadmin siguen entrando para configurarlos y probarlos. Se reactivan
 * desde el panel de módulos sin desplegar.
 */
return new class extends Migration
{
    private const MODULES = [
        'games_trivia' => 'Trivia',
        'marketplace' => 'Marketplace',
    ];

    public function down() : void
    {
        DB::table('modules')->whereIn('key', array_keys(self::MODULES))->update(['is_active' => true, 'updated_at' => now()]);
        Cache::forget(Module::CACHE_KEY);
    }

    public function up() : void
    {
        // Decisión de producto, no de esquema: la suite prueba los módulos
        // encendidos (EnsureModuleActiveTest prueba el bloqueo por separado).
        if (app()->environment('testing')) {
            return;
        }

        foreach (self::MODULES as $key => $label) {
            $exists = DB::table('modules')->where('key', $key)->exists();

            if ($exists) {
                DB::table('modules')->where('key', $key)->update(['is_active' => false, 'updated_at' => now()]);
            } else {
                DB::table('modules')->insert([
                    'id' => Module::generateSnowflakeId(),
                    'key' => $key,
                    'label' => $label,
                    'is_active' => false,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        }

        Cache::forget(Module::CACHE_KEY);
    }
};
