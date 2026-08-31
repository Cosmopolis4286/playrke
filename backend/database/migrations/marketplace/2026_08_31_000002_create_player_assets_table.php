<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Instancia de un activo poseída por un jugador. Un AssetDefinition
 * stackable=true tiene a lo sumo una fila por (owner_id, asset_definition_id)
 * — unidades nuevas incrementan quantity en vez de crear filas duplicadas;
 * stackable=false crea una fila por unidad (quantity siempre 1, no
 * fungible).
 *
 * locked_by_escrow_id NO tiene FK física hacia escrows todavía: la tabla
 * escrows se crea en la migración de la Fase 3 (Escrow). Se agrega la FK
 * física en esa migración vía Schema::table(), mismo patrón ya usado por
 * wallet_holds.game_session_id (2026_08_24_130001_add_game_session_...).
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('player_assets', function (Blueprint $table) {
            $table->snowflake('id')->primary();

            $table->foreignSnowflake('asset_definition_id')
                ->constrained('asset_definitions')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignSnowflake('owner_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->unsignedInteger('quantity')->default(1);

            $table->enum('status', ['AVAILABLE', 'LOCKED', 'CONSUMED', 'TRANSFERRED', 'EXPIRED'])
                ->default('AVAILABLE');

            $table->unsignedBigInteger('locked_by_escrow_id')->nullable()->index()
                ->comment('Referencia blanda a escrows.id (tabla creada en fase posterior) — sin FK física todavía');

            $table->string('acquired_via', 40)
                ->comment('MARKETPLACE_PURCHASE | GAME_PRIZE | ADMIN_GRANT | ...');
            $table->string('acquired_reference', 64)->nullable()
                ->comment('ID de la orden/premio/acción que originó esta instancia');

            $table->json('metadata')->nullable()
                ->comment('Estado propio de la instancia, ej. shield_charge_remaining');

            $table->timestamp('expires_at')->nullable();

            $table->timestamps();

            $table->index(['owner_id', 'status']);
            $table->index('status');
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE player_assets ADD CONSTRAINT chk_player_assets_quantity_nonneg CHECK (quantity >= 0)');
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE player_assets DROP CHECK chk_player_assets_quantity_nonneg');
        }

        Schema::dropIfExists('player_assets');
    }
};
