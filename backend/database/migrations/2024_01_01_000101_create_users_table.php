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
            $table->string('bio', 500)->nullable();
            $table->date('birth_date')->nullable()->comment('Fecha de nacimiento del usuario');
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

            $table->boolean('is_system_account')
                ->default(false)
                ->comment('Cuenta interna del sistema — no puede iniciar sesión ni ser modificada por admins');

            $table->boolean('is_demo_user')->default(false)->comment('Indica si es un usuario de demostración');

            $table->string('demo_password')->nullable()->comment('Contraseña para usuario de demostración');

            $table->timestamp('last_login_at')->nullable()->comment('Última fecha de inicio de sesión');
            $table->ipAddress('last_login_ip')->nullable()->comment('Última IP de inicio de sesión');

            $table->unsignedInteger('login_streak_days')->default(0);
            $table->unsignedInteger('best_login_streak_days')->default(0);

            // Configuraciones del usuario
            $table->boolean('is_email_notifications_enabled')->default(true)->comment('Notificaciones por email habilitadas');
            $table->boolean('is_two_factor_enabled')->default(false)->comment('Autenticación de dos factores habilitada');
            $table->json('user_preferences')->nullable()->comment('Preferencias del usuario');

            // Permisos específicos del usuario (sobreescribe permisos de rol)
            $table->json('specific_permissions')->nullable()->comment('Permisos específicos del usuario');

            // Términos de Servicio (TOS)
            // Versión de TOS que aceptó (NULL = nunca aceptó)
            $table->string('tos_accepted_version', 20)->nullable();
            // Cuándo aceptó la versión actual
            $table->timestamp('tos_accepted_at')->nullable();
            // IP desde la que aceptó (evidencia legal)
            $table->string('tos_accepted_ip', 45)->nullable();

            // KYC (Know Your Customer)
            // kyc_level  0 = NONE (sin verificación), 1 = BASIC (email + teléfono verificados), 2 = FULL (documento de identidad aprobado)
            $table->unsignedTinyInteger('kyc_level')->default(0);
            // kyc_status solo aplica cuando hay una verificación en curso: NONE | PENDING | VERIFIED | REJECTED
            $table->string('kyc_status', 20)->default('NONE');
            $table->timestamp('kyc_verified_at')->nullable();
            $table->string('kyc_rejection_reason', 500)->nullable();

            // Timestamps y soft deletes
            $table->timestamps();
            $table->softDeletes();

            // Índices optimizados
            $table->index(['country_id', 'status'], 'idx_country_status');
            $table->index(['email', 'status'], 'idx_email_status');
            $table->index(['referral_code'], 'idx_referral_code');
            $table->index(['last_login_at'], 'idx_last_login');
            $table->index(['created_at', 'status'], 'idx_created_status');
            $table->index('tos_accepted_version');
            $table->index('kyc_level');
            $table->index('kyc_status');
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
