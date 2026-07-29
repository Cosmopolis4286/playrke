<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Bitácora de eventos de dominio sobre salas de bingo
     * (creación sin horario, activación manual, etc.).
     *
     * Independiente de la tabla genérica `audits` (que registra
     * cambios de atributos vía el trait Auditable): aquí se
     * registran eventos de negocio con su propio contexto.
     */
    public function up(): void
    {
        Schema::create('bingo_room_audits', function (Blueprint $table) {
            $table->snowflake('id')->primary()->comment('ID único del registro');

            $table->foreignSnowflake('room_id')
                ->constrained('bingo_rooms')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Sala de bingo auditada');

            $table->foreignSnowflake('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate()
                ->comment('Usuario que ejecutó la acción (null = sistema/job)');

            $table->string('event', 40)
                ->comment('created_unscheduled | manually_activated | ...');

            $table->string('actor_role', 30)
                ->nullable()
                ->comment('Rol bajo el cual se ejecutó la acción: creator | admin | superadmin | system');

            $table->text('description')->nullable();
            $table->json('metadata')->nullable();

            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamps();

            $table->index('event', 'idx_bingo_room_audits_event');
            $table->index(['room_id', 'event'], 'idx_bingo_room_audits_room_event');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bingo_room_audits');
    }
};
