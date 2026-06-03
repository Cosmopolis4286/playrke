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
        if (Schema::hasTable('notifications')) {
            return;
        }

        Schema::create('notifications', function (Blueprint $table) {
            // PK con Snowflake
            $table->snowflake('id')->primary()->comment('ID único de la notificación');
            
            // Relaciones
            $table->foreignSnowflake('user_id')
                ->constrained('users')
                ->cascadeOnUpdate()
                ->cascadeOnDelete()
                ->comment('Id del usuario que recibió la notificación');            
            
            // Campos de notificación
            $table->string('type', 50)->comment('Tipo de notificación (WALLET_UPDATED, GAME_ALERT, etc.)');
            $table->string('title')->comment('Título de la notificación');
            $table->text('message')->comment('Mensaje de la notificación');
            $table->json('data')->nullable()->comment('Datos adicionales en formato JSON');
            
            // Estado
            $table->boolean('is_read')->default(false)->comment('Si la notificación ha sido leída');
            $table->timestamp('read_at')->nullable()->comment('Fecha cuando fue marcada como leída');
            
            // Timestamps
            $table->timestamps();
            
            // Índices
            $table->index('user_id');
            $table->index('type');
            $table->index('is_read');
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('notifications');
    }
};
