<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogo de tipos de activo digital, admin-gestionable — mismo rol que
 * movement_types.php respecto a wallet_movements: aquí se define QUÉ es un
 * activo (HUNTER_RADAR, BINGO_BOOSTER_X2...), nunca una instancia poseída
 * por un jugador (eso es player_assets).
 *
 * tradable/transferable/consumable/marketplace_enabled son 4 flags
 * independientes: un activo puede ser consumible sin ser comerciable, o
 * transferible entre jugadores sin pasar por el marketplace (regalo directo,
 * fuera de alcance de esta fase pero el modelo no lo impide a futuro).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('asset_definitions', function (Blueprint $table) {
            $table->snowflake('id')->primary();

            $table->string('code', 60)->unique()
                ->comment('Código único estable, ej: HUNTER_RADAR');

            $table->string('name', 120);
            $table->string('description', 255)->nullable();

            $table->enum('category', ['BOOSTER', 'CONSUMABLE', 'COSMETIC', 'EQUIPMENT', 'RESOURCE', 'TICKET'])
                ->comment('Clasificación funcional del activo');

            $table->string('rarity', 20)->default('COMMON');

            $table->boolean('stackable')->default(true)
                ->comment('true: instancias del mismo owner se acumulan en quantity. false: cada unidad es una fila independiente (no fungible)');

            $table->boolean('tradable')->default(false)
                ->comment('Puede listarse en el marketplace');
            $table->boolean('transferable')->default(false)
                ->comment('Puede cambiar de owner (marketplace u otro mecanismo futuro)');
            $table->boolean('consumable')->default(false)
                ->comment('Puede consumirse (reduce quantity o pasa a CONSUMED)');
            $table->boolean('marketplace_enabled')->default(false)
                ->comment('Flag operativo separado de tradable: permite desactivar la venta de un activo tradable puntualmente sin perder el flag de diseño');

            $table->string('game_group', 40)->nullable()
                ->comment('BINGO | TRIVIA | CARGAS | null = genérico. Mismo patrón que movement_types.game_group');

            $table->json('metadata')->nullable()
                ->comment('Atributos propios de la definición (ej. radar_range, shield_durability)');

            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE');
            $table->integer('sort_order')->default(0);

            $table->timestamps();

            $table->index(['category', 'status']);
            $table->index('game_group');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('asset_definitions');
    }
};
