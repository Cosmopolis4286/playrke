<?php

use BradieTilley\Snowflake\Snowflake;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecutar la migración: crear tabla 'platform_config' (singleton de
     * configuración global de la plataforma, con columnas tipadas y FKs
     * reales) y sembrar su única fila.
     *
     * Distinta de 'settings' (tabla existente, key-value genérica de
     * parámetros sueltos sin FK tipada) — platform_config es para
     * configuración estructurada que necesita apuntar de forma confiable a
     * otros registros reales (ver PlatformConfig::defaultCommerce()).
     */
    public function up(): void
    {
        Schema::create('platform_config', function (Blueprint $table) {
            $table->snowflake('id')->primary();

            // Comercio marcado como principal/vinculado a la app — sirve de
            // default para salas sin comercio asociado (BingoRoom/TriviaRoom
            // no tienen commerce_id hoy; este campo prepara el terreno para
            // cuando se conecten). nullOnDelete() como defensa en profundidad
            // además de la validación de aplicación en AdminCommerceCrudService
            // (que ya impide borrar/bloquear el comercio marcado como default).
            $table->foreignSnowflake('default_commerce_id')
                ->nullable()
                ->constrained('commerces')
                ->nullOnDelete()
                ->comment('Comercio principal/vinculado a la app');

            $table->timestamps();
        });

        // Fila singleton: el modelo PlatformConfig siempre asume que existe
        // exactamente 1 fila, sin lógica de creación diferida en runtime.
        DB::table('platform_config')->insert([
            'id'         => (new Snowflake())->id(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Revertir la migración.
     */
    public function down(): void
    {
        Schema::dropIfExists('platform_config');
    }
};
