<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/*
 * Tabla de intentos de pago de premios de trivia — equivalente a
 * bingo_card_prize_payments, pero en pgsql_trivia. Necesaria para que
 * TriviaParticipant implemente GamePrizeContract (hasCompletedPayment(),
 * createPayment()) y así GamePrizeService pueda pagar premios de Trivia
 * con el mismo FSM que usa Bingo.
 *
 * Sin FKs a users/currencies: esas tablas viven en MySQL, no en pgsql_trivia
 * — un FK cross-motor no es posible. user_id/currency_id quedan como columnas
 * simples, igual que en trivia_participants.
 *
 * TriviaPrizePayment extiende BaseModel, que usa SoftDeletes globalmente
 * (ver app/Models/BaseModel.php) — softDeletes() nace directo en el create.
 */
return new class extends Migration
{
    public function down() : void
    {
        Schema::connection('pgsql_trivia')->dropIfExists('trivia_prize_payments');
    }

    public function up() : void
    {
        if (Schema::connection('pgsql_trivia')->hasTable('trivia_prize_payments')) {
            return;
        }

        Schema::connection('pgsql_trivia')->create('trivia_prize_payments', function(Blueprint $table) {
            $table->string('id', 20)->primary();

            $table->string('participant_id', 20);
            $table->foreign('participant_id')->references('id')->on('trivia_participants')->onDelete('cascade');

            $table->string('room_id', 20);
            $table->foreign('room_id')->references('id')->on('trivia_rooms')->onDelete('cascade');

            $table->string('user_id', 20);
            $table->string('currency_id');

            $table->decimal('payment_amount', 20, 8);

            $table->string('payment_status', 20)->default('pending'); // pending, completed, failed, cancelled
            $table->timestamp('processed_at')->nullable();
            $table->string('transaction_reference', 100)->nullable();
            $table->text('payment_notes')->nullable();

            $table->timestamps();
            $table->softDeletes();

            $table->index(['participant_id', 'payment_status']);
            $table->index(['room_id', 'user_id']);
            $table->index('transaction_reference');
        });
    }
};
