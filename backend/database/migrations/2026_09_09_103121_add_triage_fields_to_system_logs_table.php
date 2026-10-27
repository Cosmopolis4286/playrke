<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Revertir la migración.
     */
    public function down() : void
    {
        if (! Schema::hasColumn('system_logs', 'ignored_at')) {
            return;
        }

        Schema::table('system_logs', function(Blueprint $table) {
            $table->dropIndex(['ignored_at']);
            $table->dropIndex(['assigned_to_user_id']);
            $table->dropConstrainedForeignId('ignored_by_user_id');
            $table->dropConstrainedForeignId('assigned_to_user_id');
            $table->dropColumn(['ignored_at', 'alert_sent_at']);
        });
    }

    /**
     * Ejecutar la migración: agregar campos de triage a system_logs.
     *
     * - ignored_at/ignored_by_user_id: "conocido, no volver a alertar" —
     *   distinto de reviewed_at (que es por fila y no sobrevive a una nueva
     *   ocurrencia del mismo fingerprint). Ignorar aplica al fingerprint
     *   completo: mientras esté ignorado, nuevas ocurrencias del mismo error
     *   no cuentan para newLogsCount ni para las stats de "sin revisar".
     * - assigned_to_user_id: reparte la carga cuando hay varios admins
     *   mirando /logs — permite filtrar "asignados a mí".
     * - alert_sent_at: evita reenviar la alerta de Slack/email en cada
     *   ocurrencia de un fingerprint que ya cruzó el umbral una vez.
     */
    public function up() : void
    {
        // Guard de idempotencia — sin esto, un migrate:fresh que corre en
        // paralelo con otra sesión que ya deja estas columnas físicamente
        // creadas pero sin registrar en `migrations` revienta con "Column
        // already exists" y aborta toda la corrida de tests antes de
        // llegar al seeder (mismo problema ya visto con receipt_hash).
        if (Schema::hasColumn('system_logs', 'ignored_at')) {
            return;
        }

        Schema::table('system_logs', function(Blueprint $table) {
            $table->timestamp('ignored_at')->nullable()->after('reviewed_by_user_id');
            $table->foreignSnowflake('ignored_by_user_id')
                ->nullable()
                ->after('ignored_at')
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->foreignSnowflake('assigned_to_user_id')
                ->nullable()
                ->after('ignored_by_user_id')
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            $table->timestamp('alert_sent_at')->nullable()->after('assigned_to_user_id');

            $table->index('ignored_at');
            $table->index('assigned_to_user_id');
        });
    }
};
