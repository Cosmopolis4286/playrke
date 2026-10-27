<?php

declare(strict_types=1);

namespace App\Services\Games;

/**
 * Contrato que debe implementar cualquier modelo de premio de juego para poder
 * usar GamePrizeService como fuente de verdad de pagos.
 *
 * Implementaciones actuales: BingoCardPrize.
 */
interface GamePrizeContract
{
    // ── Registro del intento de pago ─────────────────────────────────────────

    /**
     * Construye el array de atributos para insertar en la tabla de pagos.
     *
     * @param string|null $movementId ID del WalletMovement, null si el pago falló
     * @param string      $status     'completed' | 'failed'
     * @param string|null $reason     motivo del fallo, null en caso de éxito
     */
    public function buildPaymentAttributes(?string $movementId, string $status, ?string $reason) : array;

    /**
     * Inserta un registro de pago con los atributos dados.
     * Delegado al modelo para respetar la relación concreta (hasMany payments).
     */
    public function createPayment(array $attributes) : void;

    /** ID de la moneda en la que se acredita el premio. */
    public function getCurrencyId() : string;

    /** ID de la sala/partida — aparece en la descripción del movimiento de wallet. */
    public function getGameId() : string;
    // getKey() NO se declara aquí a propósito: todo modelo Eloquent (todas las
    // implementaciones reales de este contrato son Model) ya lo hereda de
    // Illuminate\Database\Eloquent\Model::getKey(), que no tiene tipo de
    // retorno declarado en su firma (solo @return mixed en el PHPDoc).
    // Redeclararlo aquí con ": mixed" rompe la carga de la clase en runtime
    // con un fatal error de "Declaration ... must be compatible" — PHP exige
    // covarianza de tipos de retorno y "sin tipo" no es compatible con
    // "mixed" explícito en esa dirección.

    /** Slug del juego — para logs ('bingo', 'trivia', …). */
    public function getGameSlug() : string;

    /** Monto en moneda local a acreditar. */
    public function getLocalAmount() : float;

    /** ID del usuario ganador. */
    public function getUserId() : string;

    /**
     * ¿Ya existe un pago con payment_status='completed' vinculado a este premio?
     * Usado en el path de reintento para no re-acreditar la wallet.
     */
    public function hasCompletedPayment() : bool;

    public function isClaimed() : bool;

    /**
     * Disputa post-pago (2026-08-31): un premio ya 'claimed' pasa a
     * 'disputed' cuando un admin revierte el pago manualmente (ver
     * GamePrizeService::disputeAndReverse()). Disparador manual únicamente
     * por ahora — no hay señal automática de anti-fraude conectada todavía.
     */
    public function isDisputed() : bool;

    public function isProcessing() : bool;

    // ── FSM de prize_status ──────────────────────────────────────────────────

    public function isWaiting() : bool;

    public function markAsClaimed() : bool;

    public function markAsDisputed() : bool;

    // bool (no void): BingoCardPrize::markAsProcessing()/markAsClaimed() ya
    // devuelven el bool de éxito de Model::save() — declarar void aquí forzaría
    // a la implementación a descartar ese valor o incumplir el contrato con un
    // fatal error de covarianza de tipos de retorno al cargar la clase.
    // GamePrizeService no usa el valor de retorno, así que bool no le cuesta nada.
    public function markAsProcessing() : bool;
}
