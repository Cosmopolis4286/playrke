<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function down() : void
    {
        Schema::dropIfExists('referral_xp_awards');
    }

    /**
     * Registro estructurado de cada XP otorgado por referido verificado —
     * ver AwardReferralXpOnVerifiedListener. No reemplaza el log genérico de
     * spatie/activitylog (UserLevelService::logXpTransaction), lo
     * complementa con columnas tipadas y consultables (referrer_id,
     * referred_user_id, ip) pensadas para que un admin pueda detectar
     * patrones de abuso (muchos referidos desde la misma IP) y revertir un
     * award puntual sin tener que parsear texto libre de un log.
     *
     * La reversión es manual por diseño (decisión explícita del usuario,
     * no automatizada): revoked_at/revoked_by/revoked_reason quedan null
     * hasta que un admin actúe sobre una fila concreta.
     */
    public function up() : void
    {
        Schema::create('referral_xp_awards', function(Blueprint $table) {
            $table->snowflake('id')->primary();

            $table->foreignSnowflake('referrer_id')
                ->constrained('users')
                ->onUpdate('cascade')
                ->onDelete('cascade')
                ->comment('Usuario que recibió el XP por el referido');

            $table->foreignSnowflake('referred_user_id')
                ->constrained('users')
                ->onUpdate('cascade')
                ->onDelete('cascade')
                ->comment('Usuario referido cuya verificación disparó el award');

            $table->unsignedInteger('xp_amount')->comment('Monto de XP otorgado');
            $table->ipAddress('referred_registration_ip')->nullable()->comment('IP de registro del referido, copiada para auditoría aunque users.registration_ip cambie o se borre');

            $table->timestamp('revoked_at')->nullable();
            $table->foreignSnowflake('revoked_by')
                ->nullable()
                ->constrained('users')
                ->onUpdate('cascade')
                ->onDelete('set null')
                ->comment('Admin que revirtió el award');
            $table->string('revoked_reason', 500)->nullable();

            $table->timestamps();

            $table->index(['referrer_id', 'created_at'], 'idx_referrer_created');
            $table->index('referred_registration_ip');
        });
    }
};
