<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Auditoría de la cadena Commerce → Subsidiary → Seller confirmó una
     * cascada asimétrica: `subsidiaries.commerce_id → commerces.id` y
     * `sellers.subsidiary_id → subsidiaries.id` usan `nullOnDelete` (el
     * hijo sobrevive desvinculado si se hard-elimina el padre), pero
     * `sellers.manager_user_id → users.id` usaba `cascadeOnDelete` — un
     * hard delete del User de un Commerce/Subsidiary borraba el Seller
     * completo en cascada, sin soft-delete ni aviso, rompiendo el patrón
     * "desvincular, no destruir" que el resto de la jerarquía sí respeta
     * (confirmado: ningún flujo de la app espera o depende de un borrado en
     * cascada de la jerarquía completa — AdminCommerceCrudService::destroy()
     * nunca menciona ni toca Sellers, es un efecto secundario no intencional
     * de la FK).
     *
     * manager_user_id pasa a nullable: un Seller sin manager_user_id
     * (huérfano tras el hard-delete de su Commerce) queda visible para que
     * un admin lo reasigne, en vez de desaparecer.
     */
    public function up(): void
    {
        Schema::table('sellers', function (Blueprint $table) {
            $table->dropForeign(['manager_user_id']);
        });

        Schema::table('sellers', function (Blueprint $table) {
            $table->foreignSnowflake('manager_user_id')
                ->nullable()
                ->change();
        });

        Schema::table('sellers', function (Blueprint $table) {
            $table->foreign('manager_user_id')
                ->references('id')->on('users')
                ->cascadeOnUpdate()
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('sellers', function (Blueprint $table) {
            $table->dropForeign(['manager_user_id']);
        });

        Schema::table('sellers', function (Blueprint $table) {
            $table->foreignSnowflake('manager_user_id')
                ->nullable(false)
                ->change();
        });

        Schema::table('sellers', function (Blueprint $table) {
            $table->foreign('manager_user_id')
                ->references('id')->on('users')
                ->cascadeOnDelete();
        });
    }
};
