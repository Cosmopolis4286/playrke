<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function down() : void
    {
        Schema::dropIfExists('directory_audits');
    }

    /**
     * Bitácora de eventos sobre registros de directorio de pago
     * (PagoMovil Venezuela, PixKey Brasil) — quién creó/editó/desactivó/
     * eliminó una cuenta bancaria destino de pagos y cuándo. Sin esto no
     * había forma de auditar cambios sobre un dato sensible para disputas
     * de retiro (mismo criterio que bingo_room_audits para el dominio Bingo).
     *
     * `record_type` distingue PagoMovil de PixKey ya que son dos modelos/
     * tablas distintas sin FK única polimórfica nativa — se guarda el FQCN
     * corto ('pago_movil' | 'pix_key') en vez de una morph real para no
     * acoplar esta tabla a los nombres de clase concretos.
     */
    public function up() : void
    {
        Schema::create('directory_audits', function(Blueprint $table) {
            $table->snowflake('id')->primary()->comment('ID único del registro de auditoría');

            $table->string('record_type', 20)
                ->comment('pago_movil | pix_key');

            $table->string('record_id', 32)
                ->comment('ID (snowflake) del registro de directorio auditado — sin FK, el registro puede ya no existir tras un hard delete');

            $table->foreignSnowflake('owner_user_id')
                ->constrained('users')
                ->cascadeOnDelete()
                ->comment('Usuario dueño del registro de directorio auditado');

            $table->foreignSnowflake('actor_user_id')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete()
                ->comment('Usuario que ejecutó la acción (null = sistema/job)');

            $table->string('event', 30)
                ->comment('created | updated | status_changed | deleted');

            $table->string('actor_role', 30)
                ->nullable()
                ->comment('Rol bajo el cual se ejecutó la acción');

            // true cuando actor_user_id != owner_user_id (ej. admin editando
            // el directorio de un jugador) — permite filtrar/alertar sobre
            // estas acciones sin tener que comparar las dos columnas cada vez.
            $table->boolean('is_impersonation')
                ->default(false)
                ->comment('true si el actor gestionó el directorio de otro usuario (ej. admin)');

            $table->json('changes')->nullable()->comment('Campos modificados: {field: [old, new]}');

            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();

            $table->timestamps();

            $table->index(['record_type', 'record_id'], 'idx_directory_audits_record');
            $table->index('owner_user_id', 'idx_directory_audits_owner');
            $table->index('is_impersonation', 'idx_directory_audits_impersonation');
        });
    }
};
