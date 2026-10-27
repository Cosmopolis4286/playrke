<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Agrega `source` a exchange_rates: distingue si la fila la escribió un
 * usuario real (creación/edición manual desde /exchange-rate, o el botón
 * "Consultar" que sincroniza en lote) o el cronjob automático
 * (HandleExchangeRatesCommand, scheduler horario) — antes no había forma de
 * saberlo aparte de user_id, que en ambos casos apunta a un usuario real
 * (el dueño de la preferencia auto_update_enabled, no un usuario "sistema"
 * dedicado), así que no bastaba para distinguir origen. Ver
 * ExchangeRateSummaryService/ExchangeRateTable, que ahora muestran
 * "Sistema (automático)" vs. "@usuario" en base a esta columna.
 *
 * default 'user' porque toda fila existente antes de esta migración fue
 * creada por acción manual o por el flujo de "Consultar" — el cronjob
 * automático nunca escribió tasas exitosamente hasta el fix de
 * AdminExchangeRateRepository::updateOrCreateExchangeRate() (usaba
 * Auth::id(), null en contexto CLI, contra una columna user_id NOT NULL).
 */
return new class extends Migration
{
    public function down() : void
    {
        Schema::table('exchange_rates', function(Blueprint $table) {
            $table->dropColumn('source');
        });
    }

    public function up() : void
    {
        // hasColumn guard: la migración consolidada create_exchange_rates_table
        // ya incluye 'source' para instalaciones nuevas (migrate:fresh) — esta
        // migración incremental solo aplica a BDs existentes que corren
        // `migrate --force` sin volver a crear la tabla.
        if (Schema::hasColumn('exchange_rates', 'source')) {
            return;
        }

        Schema::table('exchange_rates', function(Blueprint $table) {
            $table->enum('source', ['user', 'system'])
                ->default('user')
                ->after('status')
                ->comment('Origen de la actualización: user (manual/botón Consultar) o system (cronjob automático)');
        });
    }
};
