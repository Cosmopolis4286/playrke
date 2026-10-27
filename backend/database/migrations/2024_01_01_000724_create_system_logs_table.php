<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function down() : void
    {
        Schema::dropIfExists('system_logs');
    }

    public function up() : void
    {
        Schema::create('system_logs', function(Blueprint $table) {
            // PK con Snowflake — mismo patrón que command_logs, tabla de
            // sistema de alto volumen poblada automáticamente por el canal
            // de log 'database' (ver App\Logging\DatabaseLogHandler).
            $table->snowflake('id')->primary();

            $table->string('level', 10)->index();
            // error | warn — solo estos dos niveles se persisten (ver
            // DatabaseLogHandler), info/debug quedan solo en archivo/consola.

            $table->text('message');
            $table->string('context')->nullable();
            $table->text('stack')->nullable();
            $table->json('meta')->nullable();

            /*
             * Agrupación de errores repetidos (ej. un job en loop generando
             * el mismo error cientos de veces) — sin esto, /logs se llena de
             * filas idénticas en vez de mostrar "este error pasó N veces".
             * fingerprint = hash(message + primera línea propia del stack).
             */
            $table->string('fingerprint', 64)->nullable()->index();
            $table->unsignedInteger('occurrences')->default(1);
            $table->timestamp('last_seen_at')->nullable();

            /*
             * Marcado manual de revisión — distingue "ya lo vi, es conocido"
             * de "nuevo, sin revisar" cuando hay más de un admin mirando logs.
             */
            $table->timestamp('reviewed_at')->nullable();
            $table->foreignSnowflake('reviewed_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            /*
             * "Conocido, no volver a alertar" — a diferencia de reviewed_at
             * (por fila, no sobrevive a una nueva ocurrencia del mismo
             * fingerprint), ignored_at aplica al fingerprint completo:
             * mientras esté ignorado, nuevas ocurrencias no cuentan para
             * newLogsCount ni para las stats de "sin revisar".
             */
            $table->timestamp('ignored_at')->nullable();
            $table->foreignSnowflake('ignored_by_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            // Reparte la carga cuando hay varios admins mirando /logs.
            $table->foreignSnowflake('assigned_to_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            // Evita reenviar la alerta de Slack/email en cada ocurrencia de
            // un fingerprint que ya cruzó el umbral una vez.
            $table->timestamp('alert_sent_at')->nullable();

            $table->timestamp('created_at')->useCurrent();

            $table->index('created_at');
            $table->index('ignored_at');
            $table->index('assigned_to_user_id');
        });
    }
};
