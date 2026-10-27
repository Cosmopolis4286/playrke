<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function down() : void
    {
        Schema::dropIfExists('platform_win_events');
    }

    /**
     * Fuente de verdad única para el feed de ganancias en vivo de toda la
     * plataforma. Cualquier juego (Bingo, Trivia, futuros motores) o premio
     * otorgado manualmente (físico, objeto, servicio) inserta un registro
     * aquí — el ticker y los reportes solo leen de esta tabla, nunca de las
     * tablas internas de cada juego.
     */
    public function up() : void
    {
        Schema::create('platform_win_events', function(Blueprint $table) {
            $table->snowflake('id')->primary()->comment('ID del evento de ganancia');

            $table->foreignSnowflake('user_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete()
                ->comment('Usuario ganador (null si el premio no está ligado a una cuenta de la plataforma)');

            $table->string('winner_name_override', 120)
                ->nullable()
                ->comment('Nombre del ganador cuando no tiene cuenta en la plataforma (premios físicos/externos)');

            // Identifica el origen sin acoplar la tabla a un juego concreto.
            $table->string('game_slug', 50)->comment('Identificador corto del origen: bingo, trivia, physical-raffle, etc.');
            $table->string('game_name', 100)->comment('Nombre legible del origen: Bingo, Trivia, Rifa Presencial, etc.');

            $table->enum('source_type', ['game_engine', 'admin_manual', 'external_api'])
                ->comment('Quién reportó el evento: motor de juego automático, admin desde el panel, o integración externa.');

            $table->foreignSnowflake('reported_by_user_id')
                ->nullable()
                ->constrained('users')
                ->cascadeOnUpdate()
                ->nullOnDelete()
                ->comment('Admin que registró el evento manualmente (solo cuando source_type = admin_manual)');

            // Premio: puede ser dinero, un objeto o un servicio. prize_kind decide
            // qué campos son relevantes — amount/currency quedan null para item/service.
            $table->enum('prize_kind', ['money', 'item', 'service'])
                ->default('money')
                ->comment('Tipo de premio: money = monetario, item = objeto físico, service = servicio');

            $table->string('prize_label', 255)
                ->comment('Descripción mostrada en el ticker: "PlayStation 5", "Cena para 2", "$50 USD", etc.');

            $table->foreignSnowflake('currency_id')
                ->nullable()
                ->constrained('currencies')
                ->cascadeOnUpdate()
                ->nullOnDelete()
                ->comment('Moneda del premio (solo si prize_kind = money)');

            $table->decimal('amount_usd', 14, 2)
                ->nullable()
                ->comment('Monto en USD (solo si prize_kind = money)');

            $table->decimal('amount_local', 14, 4)
                ->nullable()
                ->comment('Monto en moneda local (solo si prize_kind = money)');

            // Referencia opcional al registro de origen (ej. bingo_card_prizes.id)
            // para auditoría/trazabilidad, sin relación FK porque el tipo de
            // origen varía por juego.
            $table->string('source_reference', 100)
                ->nullable()
                ->comment('ID del registro de origen en la tabla del juego (auditoría, sin FK por ser polimórfico)');

            $table->json('metadata')
                ->nullable()
                ->comment('Datos adicionales libres (ej. sala, ronda, comprobante de premio físico)');

            $table->timestamps();

            $table->index(['game_slug', 'created_at'], 'idx_win_events_game_created');
            $table->index('created_at', 'idx_win_events_created');
        });
    }
};
