<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Reverse the migrations.
     */
    public function down() : void
    {
        Schema::dropIfExists('jackpot_movements');
    }

    /**
     * Run the migrations.
     *
     * Ledger histórico de movimientos de cada jackpot (análogo a
     * `wallet_movements`). Por ahora solo existe el tipo CONTRIBUTION —
     * PAYOUT/RESET quedan reservados para cuando se implemente la entrega
     * del jackpot al ganador (fuera de alcance de esta iteración).
     */
    public function up() : void
    {
        Schema::create('jackpot_movements', function(Blueprint $table) {
            $table->snowflake('id')->primary()->comment('ID del movimiento');

            $table->foreignSnowflake('jackpot_id')
                ->constrained('jackpots')
                ->cascadeOnDelete()
                ->comment('Jackpot al que pertenece este movimiento');

            $table->foreignSnowflake('room_id')
                ->nullable()
                ->constrained('bingo_rooms')
                ->nullOnDelete()
                ->comment('Sala de Bingo que originó el aporte (si aplica)');

            $table->string('type', 30)->default('CONTRIBUTION')
                ->comment('CONTRIBUTION por ahora — PAYOUT/RESET quedan para cuando se implemente la entrega');

            $table->decimal('amount', 15, 2)->comment('Monto del movimiento (siempre positivo)');
            $table->decimal('balance_before', 15, 2)->comment('Saldo antes del movimiento');
            $table->decimal('balance_after', 15, 2)->comment('Saldo después del movimiento');

            $table->string('description', 255)->nullable()->comment('Descripción del movimiento');

            // Nullable + unique: en MySQL múltiples NULL no violan la restricción.
            $table->string('idempotency_key', 100)->nullable()->unique();

            $table->json('metadata')->nullable()->comment('Datos adicionales (ej. moneda de la venta original)');

            $table->timestamps();

            $table->index(['jackpot_id', 'created_at']);
        });
    }
};
