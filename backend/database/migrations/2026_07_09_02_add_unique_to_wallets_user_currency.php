<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Añade un índice UNIQUE(user_id, currency_id) a la tabla wallets.
 *
 * Sin este índice, dos hilos concurrentes que entren a recordAdminDeposit()
 * simultáneamente para el mismo usuario pueden ambos leer locked=null,
 * crear dos wallets y causar un doble-credit silencioso.
 * Con el UNIQUE, el segundo INSERT falla con una ConstraintException
 * atrapable en lugar de crear un duplicado.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasIndex('wallets', 'wallets_user_id_currency_id_unique')) {
            Schema::table('wallets', function (Blueprint $table) {
                $table->unique(['user_id', 'currency_id']);
            });
        }
    }

    public function down(): void
    {
        Schema::table('wallets', function (Blueprint $table) {
            $table->dropUnique(['user_id', 'currency_id']);
        });
    }
};
