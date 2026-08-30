<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('command_logs', function (Blueprint $table) {
            // PK con Snowflake
            $table->snowflake('id')->primary();
            $table->string('command'); // Nombre del comando ejecutado
            $table->text('output')->nullable(); // Resultado del comando
            $table->enum('status', ['success', 'error'])->default('success');
            $table->timestamp('executed_at'); // Fecha y hora de ejecución
            // 'scheduler' = corrido automáticamente por routes/console.php;
            // 'manual' = disparado desde /system/jobs vía "Ejecutar ahora"
            // (ver ScheduledCommandService::runNow()). triggered_by_user_id
            // solo tiene valor cuando triggered_by = 'manual'.
            $table->enum('triggered_by', ['scheduler', 'manual'])->default('scheduler');
            $table->foreignSnowflake('triggered_by_user_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('command_logs');
    }
};
