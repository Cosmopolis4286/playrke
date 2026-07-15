<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Endurece la integridad referencial de tablas que registran evidencia
 * financiera real (premios, pagos, comisiones, reembolsos): varias FK
 * usaban ON DELETE CASCADE hacia bingo_rooms/bingo_card_lots/bingo_cards/
 * bingo_card_prizes, por lo que un hard delete del padre (p. ej. una sala
 * vía BingoRoomService::destroy()) borraba en cascada el rastro contable
 * sin dejar evidencia. Se cambian a RESTRICT: bloquear el borrado del
 * padre mientras existan hijos, en vez de destruirlos silenciosamente.
 *
 * No se usa nullOnDelete() como alternativa porque todas las columnas
 * afectadas son NOT NULL (card_lot_id, card_prize_id, card_id, room_id,
 * requester_user_id, creator_user_id, lot_id, user_id) — RESTRICT es la
 * única opción que no requiere además cambiar nullability.
 *
 * Complementa (no reemplaza) el guard de aplicación agregado en
 * BingoRoomCrudService::destroy(), que ya bloquea el hard delete de una
 * sala con jugadores/cartones/bote — esto protege además contra un
 * borrado directo de BD, tinker, o un script que salte esa capa.
 */
return new class extends Migration
{
    /**
     * Tabla => [constraint actual, columna, tabla referenciada].
     *
     * @var array<string, array{0:string,1:string,2:string}>
     */
    private const FOREIGN_KEYS_TO_HARDEN = [
        'bingo_card_prizes' => ['bingo_card_prizes_card_lot_id_foreign', 'card_lot_id', 'bingo_card_lots'],
        'bingo_card_payments' => ['bingo_card_payments_card_lot_id_foreign', 'card_lot_id', 'bingo_card_lots'],
        'bingo_card_prize_payments' => ['bingo_card_prize_payments_card_prize_id_foreign', 'card_prize_id', 'bingo_card_prizes'],
        'bingo_creator_sales' => ['bingo_creator_sales_room_id_foreign', 'room_id', 'bingo_rooms'],
        'bingo_lot_refund_failures' => ['bingo_lot_refund_failures_lot_id_foreign', 'lot_id', 'bingo_card_lots'],
    ];

    public function up(): void
    {
        foreach (self::FOREIGN_KEYS_TO_HARDEN as $table => [$constraint, $column, $referencedTable]) {
            $this->swapCascadeForRestrict($table, $constraint, $column, $referencedTable);
        }

        // bingo_card_refunds: 3 columnas propias a endurecer (card_id, room_id,
        // requester_user_id) — se listan aparte porque son varias en la misma tabla.
        $this->swapCascadeForRestrict('bingo_card_refunds', 'bingo_card_refunds_card_id_foreign', 'card_id', 'bingo_cards');
        $this->swapCascadeForRestrict('bingo_card_refunds', 'bingo_card_refunds_room_id_foreign', 'room_id', 'bingo_rooms');
        $this->swapCascadeForRestrict('bingo_card_refunds', 'bingo_card_refunds_requester_user_id_foreign', 'requester_user_id', 'users');

        $this->swapCascadeForRestrict('bingo_lot_refund_failures', 'bingo_lot_refund_failures_room_id_foreign', 'room_id', 'bingo_rooms');
        $this->swapCascadeForRestrict('bingo_lot_refund_failures', 'bingo_lot_refund_failures_user_id_foreign', 'user_id', 'users');

        $this->swapCascadeForRestrict('bingo_creator_sales', 'bingo_creator_sales_creator_user_id_foreign', 'creator_user_id', 'users');
    }

    public function down(): void
    {
        foreach (self::FOREIGN_KEYS_TO_HARDEN as $table => [$constraint, $column, $referencedTable]) {
            $this->swapRestrictForCascade($table, $constraint, $column, $referencedTable);
        }

        $this->swapRestrictForCascade('bingo_card_refunds', 'bingo_card_refunds_card_id_foreign', 'card_id', 'bingo_cards');
        $this->swapRestrictForCascade('bingo_card_refunds', 'bingo_card_refunds_room_id_foreign', 'room_id', 'bingo_rooms');
        $this->swapRestrictForCascade('bingo_card_refunds', 'bingo_card_refunds_requester_user_id_foreign', 'requester_user_id', 'users');

        $this->swapRestrictForCascade('bingo_lot_refund_failures', 'bingo_lot_refund_failures_room_id_foreign', 'room_id', 'bingo_rooms');
        $this->swapRestrictForCascade('bingo_lot_refund_failures', 'bingo_lot_refund_failures_user_id_foreign', 'user_id', 'users');

        $this->swapRestrictForCascade('bingo_creator_sales', 'bingo_creator_sales_creator_user_id_foreign', 'creator_user_id', 'users');
    }

    /**
     * Recrea una FK existente cambiando su acción a RESTRICT, verificando
     * primero el nombre real del constraint en el esquema (no confiar en
     * que coincida con el de la migración de creación — ya hubo drift real
     * entre migraciones y esquema en este proyecto).
     */
    private function swapCascadeForRestrict(string $table, string $constraint, string $column, string $referencedTable): void
    {
        if (!$this->constraintExists($table, $constraint)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($constraint, $column, $referencedTable) {
            $blueprint->dropForeign($constraint);
            $blueprint->foreign($column, $constraint)
                ->references('id')->on($referencedTable)
                ->restrictOnDelete()
                ->cascadeOnUpdate();
        });
    }

    private function swapRestrictForCascade(string $table, string $constraint, string $column, string $referencedTable): void
    {
        if (!$this->constraintExists($table, $constraint)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($constraint, $column, $referencedTable) {
            $blueprint->dropForeign($constraint);
            $blueprint->foreign($column, $constraint)
                ->references('id')->on($referencedTable)
                ->cascadeOnDelete()
                ->cascadeOnUpdate();
        });
    }

    private function constraintExists(string $table, string $constraint): bool
    {
        return DB::table('information_schema.table_constraints')
            ->where('table_schema', DB::getDatabaseName())
            ->where('table_name', $table)
            ->where('constraint_name', $constraint)
            ->exists();
    }
};
