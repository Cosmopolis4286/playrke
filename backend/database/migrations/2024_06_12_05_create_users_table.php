<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Ejecutar la migración: crear tabla de usuarios optimizada
     */
    public function up(): void
    {
        if (Schema::hasTable('users')) {
            return;
        }

        Schema::create('users', function (Blueprint $table) {
            // Clave primaria Snowflake
            $table->snowflake('id')->primary()->comment('ID único del usuario');

            // Información básica del usuario
            $table->string('username', 30)->unique()->comment('Nombre de usuario único');
            $table->string('first_name', 100)->nullable()->comment('Primer nombre');
            $table->string('last_name', 100)->nullable()->comment('Apellido');

            // Relaciones con restricciones mejoradas
            $table->foreignSnowflake('country_id')
                ->constrained('countries')
                ->onUpdate('cascade')
                ->onDelete('restrict')
                ->comment('País del usuario');

            $table->foreignSnowflake('time_zone_id')
                ->nullable()
                ->constrained('time_zones')
                ->onUpdate('cascade')
                ->onDelete('set null')
                ->comment('Zona horaria del usuario');

            // Información de contacto y perfil
            $table->string('profile_photo_url', 500)->nullable()->comment('URL de la foto de perfil');
            $table->string('phone_number', 25)->nullable()->comment('Número de teléfono');
            $table->string('email', 320)->unique()->comment('Correo electrónico único');
            $table->timestamp('email_verified_at')->nullable()->comment('Fecha de verificación del email');

            // Seguridad y autenticación
            $table->string('password', 255)->comment('Contraseña hasheada');
            $table->rememberToken();
            $table->string('referral_code', 20)->unique()->comment('Código de referido único');

            // Auto-referencia para sistema de referidos
            $table->foreignSnowflake('referred_by_user_id')
                ->nullable()
                ->constrained('users')
                ->onUpdate('cascade')
                ->onDelete('set null')
                ->comment('Usuario que refirió a este usuario');

            // Estado y control
            $table->enum('status', ['ACTIVE', 'INACTIVE', 'SUSPENDED', 'DELETED', 'PENDING_VERIFICATION'])
                ->default('PENDING_VERIFICATION')
                ->index()
                ->comment('Estado del usuario');

            $table->boolean('is_demo_user')->default(false)->comment('Indica si es un usuario de demostración');

            $table->string('demo_password')->nullable()->comment('Contraseña para usuario de demostración');

            $table->timestamp('last_login_at')->nullable()->comment('Última fecha de inicio de sesión');
            $table->ipAddress('last_login_ip')->nullable()->comment('Última IP de inicio de sesión');

            // Configuraciones del usuario
            $table->boolean('is_email_notifications_enabled')->default(true)->comment('Notificaciones por email habilitadas');
            $table->boolean('is_two_factor_enabled')->default(false)->comment('Autenticación de dos factores habilitada');
            $table->json('user_preferences')->nullable()->comment('Preferencias del usuario');

            // Timestamps y soft deletes
            $table->timestamps();
            $table->softDeletes();

            // Índices optimizados
            $table->index(['country_id', 'status'], 'idx_country_status');
            $table->index(['email', 'status'], 'idx_email_status');
            $table->index(['referral_code'], 'idx_referral_code');
            $table->index(['last_login_at'], 'idx_last_login');
            $table->index(['created_at', 'status'], 'idx_created_status');
        });
    }

    /**
     * Revertir la migración: eliminar tabla de usuarios
     */
    public function down(): void
    {
        Schema::dropIfExists('users');
    }
};
