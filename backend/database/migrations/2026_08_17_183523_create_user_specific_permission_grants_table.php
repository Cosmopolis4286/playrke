<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * Reemplaza users.specific_permissions (JSON plano de strings) por una
     * tabla consultable en SQL: soporta expiración por permiso (expires_at
     * nullable = permanente), quién lo otorgó y por qué, y revocación
     * explícita sin perder el historial (revoked_at, en vez de un DELETE).
     */
    public function up(): void
    {
        Schema::create('user_specific_permission_grants', function (Blueprint $table) {
            $table->snowflake('id')->primary()->comment('ID del otorgamiento');

            $table->foreignSnowflake('user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('Usuario que recibe el permiso');

            $table->string('permission_name', 150)
                ->comment('Nombre del permiso (Permission.name) — string, no FK, igual que el JSON que reemplaza');

            $table->foreignSnowflake('granted_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Superadmin que otorgó el permiso (null si lo asignó un proceso automático, ej. activación de juegos)');

            $table->string('reason', 255)->nullable()->comment('Motivo declarado al otorgar');

            $table->timestamp('expires_at')->nullable()->comment('Fecha de expiración — null = permanente');
            $table->timestamp('revoked_at')->nullable()->comment('Fecha de revocación manual o por expiración automática');
            $table->string('revoked_reason', 255)->nullable()->comment('EXPIRED (job automático) | motivo manual al revocar desde el modal');

            $table->timestamp('expiring_notified_at')->nullable()->comment('Cuándo se envió el aviso previo de expiración (evita reenviar cada corrida del job)');

            $table->timestamps();

            // Un usuario no puede tener dos grants activos del mismo permiso.
            // Nombre de índice explícito: el autogenerado por Laravel excede
            // el límite de 64 caracteres de MySQL para este set de columnas.
            $table->unique(['user_id', 'permission_name', 'revoked_at'], 'uspg_user_permission_revoked_unique');
            $table->index(['expires_at', 'revoked_at'], 'uspg_expires_revoked_index');
            $table->index('user_id', 'uspg_user_id_index');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_specific_permission_grants');
    }
};
