<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Agrega los campos de integración que CustomerGame/CustomerGameRepository ya
 * esperaban (customer_game_id, product, description) pero que nunca se
 * migraron — customers_games se creó solo como pivot simple (customer_id,
 * game_id, status). Sin estos campos, SeamlessWallet::transferMoney() no
 * puede armar el payload "Game"/"Product" que el proveedor externo requiere
 * en TransferMoney (ver validate:transactions → HandleTransactions).
 *
 * customer_game_id: identificador del juego en el sistema del proveedor
 * externo (distinto del game_id interno) — único por proveedor, no global,
 * porque cada customer define sus propios códigos.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customers_games', function (Blueprint $table) {
            if (!Schema::hasColumn('customers_games', 'customer_game_id')) {
                $table->string('customer_game_id', 100)->nullable()->after('game_id');
            }
            if (!Schema::hasColumn('customers_games', 'product')) {
                $table->string('product', 100)->nullable()->after('customer_game_id');
            }
            if (!Schema::hasColumn('customers_games', 'description')) {
                $table->string('description', 255)->nullable()->after('product');
            }
        });

        Schema::table('customers_games', function (Blueprint $table) {
            $table->unique(['customer_id', 'customer_game_id'], 'uk_customer_customer_game_id');
        });
    }

    public function down(): void
    {
        Schema::table('customers_games', function (Blueprint $table) {
            $table->dropUnique('uk_customer_customer_game_id');
            $table->dropColumn(['customer_game_id', 'product', 'description']);
        });
    }
};
