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
            $table->snowflake('id')->primary();;
            $table->string('command'); // Nombre del comando ejecutado
            $table->text('output')->nullable(); // Resultado del comando
            $table->enum('status', ['success', 'error'])->default('success');
            $table->timestamp('executed_at'); // Fecha y hora de ejecución
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
