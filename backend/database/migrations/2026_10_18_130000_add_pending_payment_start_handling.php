<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Pagos de cartón que siguen PENDIENTES cuando arranca el sorteo:
 *  - commerces.pending_payment_policy: regla de cada comercializadora para los
 *    pagos de su red (reject_refund | keep_review, default keep_review = el
 *    comportamiento de hasta ahora);
 *  - bingo_card_payments.start_alert_sent_at / pending_at_start_at: aviso
 *    previo al revisor (una vez) y marca de "seguía pendiente al iniciar".
 * Incremental para BDs existentes; definiciones base en sus create_*.
 */
return new class extends Migration
{
    public function down() : void
    {
        if (Schema::hasColumn('commerces', 'pending_payment_policy')) {
            Schema::table('commerces', fn (Blueprint $table) => $table->dropColumn('pending_payment_policy'));
        }

        Schema::table('bingo_card_payments', function(Blueprint $table) {
            foreach (['pending_at_start_at', 'start_alert_sent_at'] as $column) {
                if (Schema::hasColumn('bingo_card_payments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    public function up() : void
    {
        if (! Schema::hasColumn('commerces', 'pending_payment_policy')) {
            Schema::table('commerces', function(Blueprint $table) {
                $table->string('pending_payment_policy', 20)->default('keep_review')->after('unique_code_id')
                    ->comment('Pago pendiente al iniciar el sorteo: reject_refund | keep_review');
            });
        }

        Schema::table('bingo_card_payments', function(Blueprint $table) {
            if (! Schema::hasColumn('bingo_card_payments', 'start_alert_sent_at')) {
                $table->timestamp('start_alert_sent_at')->nullable()->after('processed_at')
                    ->comment('Se avisó al revisor que la sala está por iniciar');
            }
            if (! Schema::hasColumn('bingo_card_payments', 'pending_at_start_at')) {
                $table->timestamp('pending_at_start_at')->nullable()->after('start_alert_sent_at')
                    ->comment('Seguía pendiente cuando inició el sorteo');
            }
        });
    }
};
