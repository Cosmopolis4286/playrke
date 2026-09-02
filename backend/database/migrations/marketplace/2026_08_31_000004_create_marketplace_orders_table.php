<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Orden de compra sobre un listing. escrow_id queda sin FK física hasta la
 * migración de la Fase 3 (Escrow), mismo patrón que player_assets.locked_by_escrow_id.
 *
 * Fase 2 (esta migración): solo reserva el listing (ACTIVE -> RESERVED) y
 * crea la orden en PENDING — no mueve dinero ni bloquea el asset todavía.
 * Eso es responsabilidad de EscrowService (Fase 3); OrderService::buy() de
 * esta fase deja la orden lista para que la Fase 3 la tome.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_orders', function (Blueprint $table) {
            $table->snowflake('id')->primary();

            $table->foreignSnowflake('listing_id')
                ->constrained('marketplace_listings')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignSnowflake('buyer_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->unsignedInteger('quantity');
            $table->decimal('price', 15, 2)
                ->comment('Copiado del listing al crear la orden — no cambia si el listing se edita después');
            $table->decimal('fee_amount', 15, 2)->default(0);

            $table->unsignedBigInteger('escrow_id')->nullable()->index()
                ->comment('Referencia blanda a escrows.id (tabla creada en fase posterior) — sin FK física todavía');

            $table->enum('status', ['PENDING', 'ESCROW', 'COMPLETED', 'CANCELLED', 'FAILED'])
                ->default('PENDING');

            $table->timestamps();

            $table->index('buyer_id');
            $table->index('status');
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE marketplace_orders ADD CONSTRAINT chk_marketplace_orders_price_nonneg CHECK (price >= 0)');
            DB::statement('ALTER TABLE marketplace_orders ADD CONSTRAINT chk_marketplace_orders_fee_nonneg CHECK (fee_amount >= 0)');
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE marketplace_orders DROP CHECK chk_marketplace_orders_price_nonneg');
            DB::statement('ALTER TABLE marketplace_orders DROP CHECK chk_marketplace_orders_fee_nonneg');
        }

        Schema::dropIfExists('marketplace_orders');
    }
};
