<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reverse the migrations.
     */
    public function down() : void
    {
        Schema::dropIfExists('scheduled_commands');
    }

    /**
     * Run the migrations.
     *
     * Un registro por cada Schedule::command() de routes/console.php.
     * is_enabled gobierna únicamente si el scheduler automático corre el
     * comando (ver App\Support\Scheduling\ScheduledCommandGate, consultado
     * desde un ->when() en console.php) — no bloquea invocación manual por
     * consola/tinker, ni afecta la cadencia (que sigue definida en código).
     */
    public function up() : void
    {
        Schema::create('scheduled_commands', function(Blueprint $table) {
            $table->snowflake('id')->primary();

            $table->string('command')->unique()->comment('Signature exacto del comando artisan (ej. check:expired-bingo-rooms)');
            $table->string('label')->nullable()->comment('Nombre amigable para la UI');
            $table->text('description')->nullable()->comment('Qué hace y por qué existe');

            $table->boolean('is_enabled')->default(true)->comment('Si el scheduler automático debe ejecutarlo');
            $table->timestamp('disabled_at')->nullable();

            $table->foreignSnowflake('disabled_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Superadmin que desactivó el comando');

            $table->timestamps();
        });
    }
};
