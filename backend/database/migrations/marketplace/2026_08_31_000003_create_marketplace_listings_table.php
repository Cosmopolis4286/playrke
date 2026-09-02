<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Publicación de venta de un PlayerAsset. El precio se copia a
 * marketplace_orders.price al crear la orden — editar un listing ACTIVE no
 * afecta órdenes ya creadas contra él.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_listings', function (Blueprint $table) {
            $table->snowflake('id')->primary();

            $table->foreignSnowflake('seller_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->foreignSnowflake('player_asset_id')
                ->constrained('player_assets')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->unsignedInteger('quantity');
            $table->decimal('price', 15, 2);

            $table->foreignSnowflake('currency_id')
                ->constrained('currencies')
                ->cascadeOnUpdate()
                ->restrictOnDelete();

            $table->enum('status', ['DRAFT', 'ACTIVE', 'RESERVED', 'SOLD', 'CANCELLED', 'EXPIRED', 'SUSPENDED'])
                ->default('DRAFT');

            $table->timestamp('expires_at')->nullable();

            $table->timestamps();

            $table->index(['status', 'currency_id']);
            $table->index('seller_id');
            $table->index('player_asset_id');
        });

        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE marketplace_listings ADD CONSTRAINT chk_marketplace_listings_price_positive CHECK (price > 0)');
            DB::statement('ALTER TABLE marketplace_listings ADD CONSTRAINT chk_marketplace_listings_quantity_positive CHECK (quantity > 0)');
        }
    }

    public function down(): void
    {
        if (DB::connection()->getDriverName() === 'mysql') {
            DB::statement('ALTER TABLE marketplace_listings DROP CHECK chk_marketplace_listings_price_positive');
            DB::statement('ALTER TABLE marketplace_listings DROP CHECK chk_marketplace_listings_quantity_positive');
        }

        Schema::dropIfExists('marketplace_listings');
    }
};
