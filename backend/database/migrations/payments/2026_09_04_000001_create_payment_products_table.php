<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Catálogo de packs/consumibles comprables con USDT-TRC20. Ver
 * PAYMENTS_USDT_TRC20_DESIGN.md sección 4 — reutiliza AssetDefinition
 * (Marketplace\Inventory) como "qué se entrega", esta tabla solo fija el
 * precio en USDT y la cantidad por compra.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_products', function (Blueprint $table) {
            $table->snowflake('id')->primary();

            $table->string('code', 60)->unique();
            $table->string('name', 120);

            $table->foreignSnowflake('asset_definition_id')
                ->constrained('asset_definitions')
                ->restrictOnDelete();

            $table->unsignedInteger('asset_quantity');

            $table->decimal('price_usdt', 20, 8);

            $table->enum('status', ['ACTIVE', 'INACTIVE'])->default('ACTIVE');

            $table->timestamps();

            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_products');
    }
};
