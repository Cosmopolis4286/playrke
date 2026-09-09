<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Guard de idempotencia: consulta la regla ON DELETE real de la FK
     * manager_user_id → users.id. No hay columna nueva que verificar con
     * hasColumn() (la columna ya existía) — sin este guard, un
     * migrate:fresh que corre en paralelo con otra sesión que ya deja la
     * FK en su estado final pero sin registrar la migración en
     * `migrations` revienta con "Duplicate foreign key constraint name" al
     * intentar recrearla, y aborta toda la corrida de tests antes de
     * llegar al seeder (mismo problema ya visto con receipt_hash).
     */
    private function currentDeleteRule(): ?string
    {
        // information_schema.referential_constraints devuelve sus columnas
        // en MAYÚSCULAS en MySQL/MariaDB (CONSTRAINT_NAME, DELETE_RULE) —
        // confirmado contra la BD real: ->value('delete_rule') en minúscula
        // no encuentra la clave y siempre devuelve null, haciendo que este
        // guard nunca detecte el estado ya migrado. DB::selectOne() con SQL
        // crudo evita depender del casing que el query builder asume.
        $row = DB::selectOne(
            "select delete_rule as DELETE_RULE from information_schema.referential_constraints
             where constraint_schema = database() and table_name = ? and constraint_name = ?",
            ['sellers', 'sellers_manager_user_id_foreign']
        );

        return $row?->DELETE_RULE;
    }

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
        // Ya en el estado final (SET NULL) — no repetir drop+recreate.
        if ($this->currentDeleteRule() === 'SET NULL') {
            return;
        }

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
        // Ya revertido (CASCADE) — no repetir drop+recreate.
        if ($this->currentDeleteRule() === 'CASCADE') {
            return;
        }

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
