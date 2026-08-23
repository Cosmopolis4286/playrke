<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Hace la frecuencia de cada comando editable desde el panel Jobs/Scheduler
 * en vez de fija en routes/console.php (->everyMinute(), ->hourly(), etc.).
 * routes/console.php pasa a leer cron_expression vía ScheduledCommandGate
 * (mismo cache de 60s que ya usa is_enabled) y aplicarla con ->cron($expr).
 *
 * min_interval_seconds es el piso de seguridad por comando (evita que la UI
 * permita, ej., "* * * * *" en un comando pensado para correr como mucho
 * cada 5 minutos) — se valida en ScheduledCommandUpdateRequest, no en BD.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('scheduled_commands', function (Blueprint $table) {
            $table->string('cron_expression', 100)->nullable()->after('description')
                ->comment('Expresión cron estándar de 5 campos (min hora dia mes dow). NULL = usa el default de código.');
            $table->unsignedInteger('min_interval_seconds')->nullable()->after('cron_expression')
                ->comment('Piso de seguridad: intervalo mínimo permitido entre ejecuciones para este comando, validado al editar cron_expression.');

            $table->foreignSnowflake('frequency_updated_by')
                ->nullable()
                ->after('min_interval_seconds')
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Superadmin que editó por última vez cron_expression.');
            $table->timestamp('frequency_updated_at')->nullable()->after('frequency_updated_by');
        });
    }

    public function down(): void
    {
        Schema::table('scheduled_commands', function (Blueprint $table) {
            $table->dropConstrainedForeignId('frequency_updated_by');
            $table->dropColumn(['cron_expression', 'min_interval_seconds', 'frequency_updated_at']);
        });
    }
};
