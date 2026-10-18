<?php

declare(strict_types=1);

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Lista de invitados de las salas VIP. Una VIP queda cerrada para todos salvo
 * su creador, admin y los invitados:
 *  - user_id: jugador registrado (entra y compra con su billetera).
 *  - phone/phone_key: comprador externo; los vendedores de la red solo le
 *    pueden vender si su teléfono está en la lista. phone_key = últimos 10
 *    dígitos (los teléfonos se guardan con formatos distintos: "+58 0414-…"
 *    vs "+58414…"), ver BingoRoomInvitation::phoneKey().
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('bingo_room_invitations')) {
            return;
        }

        Schema::create('bingo_room_invitations', function (Blueprint $table) {
            $table->snowflake('id')->primary();
            $table->foreignSnowflake('room_id')->constrained('bingo_rooms')->cascadeOnDelete()->cascadeOnUpdate();
            $table->foreignSnowflake('user_id')->nullable()->constrained('users')->cascadeOnDelete()->cascadeOnUpdate()
                ->comment('Jugador registrado invitado');
            $table->string('phone', 30)->nullable()->comment('Teléfono del comprador externo invitado, tal como se cargó');
            $table->string('phone_key', 15)->nullable()->comment('Últimos 10 dígitos del teléfono, para comparar');
            $table->string('label', 100)->nullable()->comment('Nombre de referencia del invitado externo');
            $table->foreignSnowflake('invited_by_user_id')->nullable()->constrained('users')->nullOnDelete()->cascadeOnUpdate();
            $table->timestamps();

            // MySQL admite varios NULL en un UNIQUE: cada fila es o usuario o teléfono.
            $table->unique(['room_id', 'user_id'], 'uq_bingo_room_invitations_room_user');
            $table->unique(['room_id', 'phone_key'], 'uq_bingo_room_invitations_room_phone');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('bingo_room_invitations');
    }
};
