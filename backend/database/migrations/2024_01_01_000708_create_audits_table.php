<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('audits', function (Blueprint $table) {

            $table->id();

            // Actor humano (nullable para seeders / jobs / system)
            $table->foreignSnowflake('user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->cascadeOnUpdate();

            // Origen de la acción
            $table->string('actor_type', 20)
                ->default('system')
                ->index();
            // system | user | job | command | webhook

            /**
             * Entidad auditada (polimórfica).
             *
             * - auditable_type : clase FQCN del modelo
             * - auditable_id   : ID simple o identidad compuesta (JSON)
             *
             * NO se usa morphs() para soportar pivots sin PK.
             */
            $table->string('auditable_type')
                ->comment('Clase FQCN del modelo auditado');

            $table->json('auditable_id')
                ->nullable()
                ->comment('ID simple o identidad compuesta del modelo');

            // Índice para consultas polimórficas
            $table->index(['auditable_type']);

            // Evento auditado
            $table->string('event', 30)
                ->index();
            // created | updated | deleted | restored | force_deleted

            // Cambios
            $table->json('old_values')->nullable();
            $table->json('new_values')->nullable();

            // Contexto
            $table->string('url')->nullable();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamps();

            // Índice compuesto útil (NO duplicado)
            $table->index(['actor_type', 'event']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('audits');
    }
};
