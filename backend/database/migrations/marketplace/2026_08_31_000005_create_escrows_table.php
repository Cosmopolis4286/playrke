<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Escrow: contenedor único de la transacción P2P completa (dinero + activo).
 * 1:1 con marketplace_orders — un order tiene a lo sumo un escrow activo.
 *
 * seller_amount = amount - fee_amount, calculado y persistido al crear el
 * escrow (no recalculado al liberar) para que una auditoría posterior vea
 * exactamente qué se prometió vs. qué se liberó — ver
 * MARKETPLACE_ARCHITECTURE_AUDIT.md sección 23.
 *
 * Tras esta migración, se agregan las FK físicas diferidas de fases
 * anteriores: player_assets.locked_by_escrow_id y marketplace_orders.escrow_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('escrows', function (Blueprint $table) {
            $table->snowflake('id')->primary();

            $table->foreignSnowflake('order_id')
                ->unique()
                ->constrained('marketplace_orders')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignSnowflake('buyer_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignSnowflake('seller_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignSnowflake('player_asset_id')
                ->constrained('player_assets')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->unsignedInteger('quantity');
            $table->decimal('amount', 15, 2)->comment('Precio total pagado por el comprador');
            $table->decimal('fee_amount', 15, 2)->default(0);
            $table->decimal('seller_amount', 15, 2)->comment('amount - fee_amount, calculado al crear');

            $table->foreignSnowflake('currency_id')
                ->constrained('currencies')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignSnowflake('wallet_hold_id')->nullable()
                ->constrained('wallet_holds')
                ->nullOnDelete();

            $table->enum('status', [
                'CREATED', 'FUNDED', 'ASSET_LOCKED', 'READY_TO_SETTLE',
                'RELEASED', 'CANCELLED', 'REFUNDED', 'DISPUTED', 'EXPIRED',
            ])->default('CREATED');

            $table->timestamp('expires_at');

            $table->string('dispute_reason', 255)->nullable();
            $table->enum('resolution', ['BUYER_WINS', 'SELLER_WINS', 'PARTIAL', 'CANCELLED'])->nullable();
            $table->foreignSnowflake('resolved_by')->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->timestamp('resolved_at')->nullable();

            $table->timestamps();

            $table->index('status');
            $table->index('buyer_id');
            $table->index('seller_id');
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE escrows ADD CONSTRAINT chk_escrows_amount_positive CHECK (amount > 0)');
            DB::statement('ALTER TABLE escrows ADD CONSTRAINT chk_escrows_fee_nonneg CHECK (fee_amount >= 0)');
            DB::statement('ALTER TABLE escrows ADD CONSTRAINT chk_escrows_seller_amount_nonneg CHECK (seller_amount >= 0)');
        }

        // FK física diferida de player_assets.locked_by_escrow_id (creada
        // como columna simple en 2026_08_31_000002 porque escrows no existía
        // todavía) — mismo patrón que wallet_holds.game_session_id.
        Schema::table('player_assets', function (Blueprint $table) {
            $table->foreign('locked_by_escrow_id')
                ->references('id')->on('escrows')
                ->nullOnDelete();
        });

        Schema::table('marketplace_orders', function (Blueprint $table) {
            $table->foreign('escrow_id')
                ->references('id')->on('escrows')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('marketplace_orders', function (Blueprint $table) {
            $table->dropForeign(['escrow_id']);
        });

        Schema::table('player_assets', function (Blueprint $table) {
            $table->dropForeign(['locked_by_escrow_id']);
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE escrows DROP CHECK chk_escrows_amount_positive');
            DB::statement('ALTER TABLE escrows DROP CHECK chk_escrows_fee_nonneg');
            DB::statement('ALTER TABLE escrows DROP CHECK chk_escrows_seller_amount_nonneg');
        }

        Schema::dropIfExists('escrows');
    }
};
