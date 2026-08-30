<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * command_logs ya tiene datos reales (933+ filas) cuando se escribió esto —
 * a diferencia del resto del proyecto (schema consolidado directo en el
 * Schema::create original), esta tabla necesita una migración incremental
 * real porque su migración de creación ya corrió en producción/dev.
 *
 * 'scheduler' = corrido automáticamente por routes/console.php; 'manual' =
 * disparado desde /system/jobs vía "Ejecutar ahora" (ver
 * ScheduledCommandService::runNow()). triggered_by_user_id solo tiene valor
 * cuando triggered_by = 'manual'.
 *
 * El Schema::create original (2024_01_01_000706_create_command_logs_table)
 * también quedó actualizado con estas mismas columnas, para que un entorno
 * nuevo (migrate:fresh) las tenga desde la creación sin depender de esta
 * migración incremental — mismo patrón que el resto del proyecto exige para
 * schema nuevo, esta migración solo cubre el entorno ya existente.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('command_logs', 'triggered_by')) {
            return;
        }

        Schema::table('command_logs', function (Blueprint $table) {
            $table->enum('triggered_by', ['scheduler', 'manual'])->default('scheduler')->after('executed_at');
            $table->foreignSnowflake('triggered_by_user_id')
                ->nullable()
                ->after('triggered_by')
                ->constrained('users')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('command_logs', function (Blueprint $table) {
            $table->dropForeign(['triggered_by_user_id']);
            $table->dropColumn(['triggered_by', 'triggered_by_user_id']);
        });
    }
};
