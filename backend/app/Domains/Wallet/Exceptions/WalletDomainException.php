<?php

declare(strict_types=1);

namespace App\Domains\Wallet\Exceptions;

use App\Exceptions\Domain\DomainException;

/**
 * Excepciones de dominio del flujo operacional de Wallet: depósitos,
 * retiros, movimientos custom, límites por KYC, antifraude y reservas
 * (WalletHold).
 *
 * Cubre WalletService, WalletLimitService, WalletFraudService,
 * WalletReservationService y WalletRepository — todas comparten el mismo
 * dominio operacional (billetera de un usuario), a diferencia de
 * WalletUnavailableException (caso aparte: wallet no localizable/inactiva,
 * usada solo por jobs de reembolso, nunca llega a un controlador HTTP).
 *
 * IMPORTANTE:
 * - El dominio expone mensajes humanos directos (no ERROR_CODES) porque
 *   son específicos de este flujo y no se repiten en otro dominio — mismo
 *   criterio que TriviaRoomException.
 */
final class WalletDomainException extends DomainException
{
    /**
     * Intento de aprobar (approveDeposit/approveGeneric/approveWithdrawal)
     * un movimiento que ya no está en STATUS_PENDING — ya fue procesado o
     * rechazado antes (conflicto de estado concurrente).
     */
    public static function approvalRequiresPendingStatus() : self
    {
        return new self('Solo se pueden aprobar movimientos en estado PENDING.', 409);
    }

    /**
     * $remaining es lo que aún puede retirar hoy (0 si ya agotó el tope).
     */
    public static function dailyWithdrawalLimitReached(float $remaining) : self
    {
        return new self("Límite diario de retiro alcanzado. Puedes retirar hasta {$remaining} hoy.", 422);
    }

    public static function depositAboveMaximum(float $maxDeposit) : self
    {
        return new self("El monto máximo por depósito es {$maxDeposit}.", 422);
    }

    // ─────────────────────────────────────────────────────────────────
    // Operación duplicada en curso (WalletService)
    // ─────────────────────────────────────────────────────────────────

    /**
     * requestDeposit(): ya existe un WalletMovement TYPE_DEPOSIT en
     * STATUS_PENDING para esta wallet — evita acumular solicitudes de
     * depósito sin resolver.
     */
    public static function depositAlreadyPending() : self
    {
        return new self('Ya tienes un depósito pendiente de confirmación. Espera a que sea procesado antes de hacer otro.', 409);
    }

    public static function depositBelowMinimum(float $minDeposit) : self
    {
        return new self("El monto mínimo de depósito es {$minDeposit}.", 422);
    }

    // ─────────────────────────────────────────────────────────────────
    // Límites por KYC (WalletLimitService)
    // ─────────────────────────────────────────────────────────────────

    /**
     * assertDepositWithinLimits(): no existe WalletLimit configurado para
     * esta moneda/nivel KYC — se bloquea por precaución en vez de dejar
     * pasar un depósito sin tope conocido.
     */
    public static function depositLimitsNotConfigured() : self
    {
        return new self('No hay límites de depósito configurados para esta moneda. Contacta soporte.', 422);
    }

    // ─────────────────────────────────────────────────────────────────
    // Antifraude (WalletFraudService)
    // ─────────────────────────────────────────────────────────────────

    /**
     * checkDepositVelocity(): el usuario alcanzó MAX_DEPOSITS_PER_DAY.
     */
    public static function depositVelocityExceeded() : self
    {
        return new self('Has alcanzado el límite de depósitos por hoy. Inténtalo mañana.', 429);
    }

    /**
     * checkDuplicateReference(): el número de referencia ya fue usado en
     * un movimiento de esta misma wallet en las últimas 24 h — posible
     * comprobante reutilizado.
     */
    public static function duplicateReference() : self
    {
        return new self('Este número de referencia ya fue utilizado recientemente. Verifica el comprobante.', 409);
    }

    /**
     * checkDoubleSubmit(): mismo monto + misma wallet + mismo tipo de
     * movimiento dentro de la ventana anti double-submit (30 s) — típico
     * de un doble clic o un retry automático del cliente.
     */
    public static function duplicateSubmission() : self
    {
        return new self('Solicitud duplicada. Espera unos segundos antes de reintentar.', 429);
    }

    /**
     * capture()/release(): el WalletHold ya no está HELD (ya fue
     * CAPTURED o RELEASED antes) — conflicto de estado concurrente,
     * típico de un doble intento de resolución sobre el mismo hold.
     */
    public static function holdAlreadyResolved() : self
    {
        return new self('El hold ya fue resuelto (capturado o liberado).', 409);
    }

    // ─────────────────────────────────────────────────────────────────
    // Reservas / WalletHold (WalletReservationService)
    // ─────────────────────────────────────────────────────────────────

    /**
     * capture(): el monto a capturar excede lo que quedó reservado en el
     * hold — nunca se cobra más de lo que el jugador aceptó reservar.
     */
    public static function holdCaptureExceedsReservedAmount() : self
    {
        return new self('No se puede capturar más de lo reservado en el hold.', 422);
    }

    /**
     * hold(): hasSufficientAvailableBalance() (saldo jugable menos holds
     * ya activos) no cubre el monto de la nueva reserva.
     */
    public static function insufficientAvailableBalanceForHold() : self
    {
        return new self('Saldo disponible insuficiente para esta reserva.', 422);
    }

    // ─────────────────────────────────────────────────────────────────
    // Validación de monto (WalletService::assertPositiveAmount())
    // ─────────────────────────────────────────────────────────────────

    /**
     * assertPositiveAmount(): un método de crédito/débito del núcleo
     * (recordPrize, recordRefund, recordCryptoDeposit, etc.) recibió un
     * monto <= 0. Defensa de última línea contra un bug de cálculo en el
     * caller (juego, marketplace, cripto) — ninguno de esos métodos debe
     * confiar ciegamente en que el monto ya viene validado.
     */
    public static function invalidMovementAmount(float $amount) : self
    {
        return new self("El monto del movimiento debe ser mayor a cero (recibido: {$amount}).", 422);
    }

    // ─────────────────────────────────────────────────────────────────
    // Saldo insuficiente (WalletService)
    // ─────────────────────────────────────────────────────────────────

    /**
     * recordCardPurchase(): hasSufficientPlayableBalance() (balance +
     * bonus_balance) no cubre el monto de la compra de cartones.
     */
    public static function insufficientBalanceForCardPurchase() : self
    {
        return new self('Saldo insuficiente para realizar la compra de cartones.', 422);
    }

    /**
     * recordHoldCapture(): al capturar un WalletHold, el saldo jugable
     * actual de la wallet ya no cubre el monto a capturar (pudo gastarse
     * en otra sala/juego entre el hold() y el capture()).
     */
    public static function insufficientBalanceForHoldCapture() : self
    {
        return new self('Saldo insuficiente para capturar la reserva.', 422);
    }

    /**
     * recordTriviaEntry(): saldo jugable insuficiente para cubrir la
     * tarifa de entrada a la sala.
     */
    public static function insufficientBalanceForRoomEntry() : self
    {
        return new self('Saldo insuficiente para unirte a esta sala.', 422);
    }

    /**
     * requestWithdraw()/withdraw() legacy: hasSufficientBalance() (solo
     * balance retirable, no bonus_balance) no cubre el monto solicitado.
     */
    public static function insufficientBalanceForWithdrawal() : self
    {
        return new self('Saldo insuficiente para realizar el retiro.', 422);
    }

    // ─────────────────────────────────────────────────────────────────
    // Repositorio (WalletRepository)
    // ─────────────────────────────────────────────────────────────────

    /**
     * consumeBonusFirst(): bonus_balance + balance combinados no alcanzan
     * el monto solicitado — guard de última línea a nivel de
     * infraestructura, independiente de las validaciones de negocio que
     * ya corrieron en el Service llamador.
     */
    public static function insufficientCombinedBalance() : self
    {
        return new self('Saldo insuficiente (bono + real) para esta operación.', 422);
    }

    public static function monthlyWithdrawalLimitReached(float $remaining) : self
    {
        return new self("Límite mensual de retiro alcanzado. Puedes retirar hasta {$remaining} este mes.", 422);
    }
    // ─────────────────────────────────────────────────────────────────
    // Movimientos: aprobación / rechazo (WalletService)
    // ─────────────────────────────────────────────────────────────────

    /**
     * approveDeposit()/rejectDeposit() invocado sobre un WalletMovement
     * cuyo type no es TYPE_DEPOSIT.
     */
    public static function movementNotDeposit() : self
    {
        return new self('El movimiento no es un depósito.', 422);
    }

    /**
     * approveWithdrawal()/rejectWithdrawal() invocado sobre un
     * WalletMovement cuyo type no es TYPE_WITHDRAWAL.
     */
    public static function movementNotWithdrawal() : self
    {
        return new self('El movimiento no es un retiro.', 422);
    }

    /**
     * findOwnedWalletByCurrency(): el usuario autenticado no tiene una
     * wallet ACTIVE para la moneda solicitada.
     */
    public static function noActiveWalletForCurrency() : self
    {
        return new self('No tienes una billetera activa para esa moneda.', 404);
    }

    /**
     * Intento de rechazar (rejectDeposit/rejectGeneric/rejectWithdrawal)
     * un movimiento que ya no está en STATUS_PENDING.
     */
    public static function rejectionRequiresPendingStatus() : self
    {
        return new self('Solo se pueden rechazar movimientos en estado PENDING.', 409);
    }

    // ─────────────────────────────────────────────────────────────────
    // Wallet: estado / disponibilidad (WalletService)
    // ─────────────────────────────────────────────────────────────────

    /**
     * Operación (deposit()/withdraw() legacy, requestDeposit(),
     * requestWithdraw()) rechazada porque la wallet está en status
     * BLOCKED.
     */
    public static function walletBlocked() : self
    {
        return new self('La billetera está bloqueada.', 403);
    }

    public static function weeklyWithdrawalLimitReached(float $remaining) : self
    {
        return new self("Límite semanal de retiro alcanzado. Puedes retirar hasta {$remaining} esta semana.", 422);
    }

    public static function withdrawalAboveMaximum(float $maxPerTransaction) : self
    {
        return new self("El monto máximo por retiro es {$maxPerTransaction}.", 422);
    }

    /**
     * requestWithdraw(): ya existe un WalletMovement TYPE_WITHDRAWAL en
     * STATUS_PENDING para esta wallet.
     */
    public static function withdrawalAlreadyPending() : self
    {
        return new self('Ya tienes un retiro pendiente de procesamiento. Espera a que sea procesado antes de solicitar otro.', 409);
    }

    public static function withdrawalBelowMinimum(float $minPerTransaction) : self
    {
        return new self("El monto mínimo de retiro es {$minPerTransaction}.", 422);
    }

    /**
     * assertWithinLimits(): no existe WalletLimit configurado para esta
     * moneda/nivel KYC — mismo criterio de bloqueo por precaución que
     * depositLimitsNotConfigured().
     */
    public static function withdrawalLimitsNotConfigured() : self
    {
        return new self('No hay límites de retiro configurados para esta moneda. Contacta soporte.', 422);
    }

    /**
     * checkWithdrawalVelocity(): el usuario alcanzó MAX_WITHDRAWALS_PER_DAY.
     */
    public static function withdrawalVelocityExceeded() : self
    {
        return new self('Has alcanzado el límite de retiros por hoy. Inténtalo mañana.', 429);
    }

    // ─────────────────────────────────────────────────────────────────
    // PIN de retiro (WalletFraudService::checkWithdrawalPinLockout(),
    // WalletService::requestWithdraw())
    // ─────────────────────────────────────────────────────────────────

    /**
     * requestWithdraw(): el usuario nunca fijó un PIN de retiro
     * (User::hasWithdrawalPin() === false) — se le exige fijarlo antes de
     * poder retirar, no se permite retirar "sin PIN" como fallback.
     */
    public static function withdrawalPinNotSet() : self
    {
        return new self('Debes configurar un PIN de retiro antes de solicitar un retiro. Ve a tu perfil para configurarlo.', 422);
    }

    /**
     * requestWithdraw(): el campo withdrawal_pin llegó vacío pese a que el
     * usuario ya tiene uno fijado — distinto de withdrawalPinInvalid()
     * (PIN incorrecto) para no filtrar por el mensaje si el PIN existe.
     */
    public static function withdrawalPinRequired() : self
    {
        return new self('Ingresa tu PIN de retiro para continuar.', 422);
    }

    /**
     * requestWithdraw(): Hash::check() del PIN recibido contra
     * withdrawal_pin_hash falló. $remainingAttempts viene de
     * WalletFraudService::recordWithdrawalPinFailure() — solo se muestra en
     * el mensaje cuando es bajo, para no facilitar calibrar un ataque de
     * fuerza bruta contando intentos totales disponibles.
     */
    public static function withdrawalPinInvalid(int $remainingAttempts) : self
    {
        $suffix = $remainingAttempts <= 2
            ? " Te quedan {$remainingAttempts} intento(s) antes de un bloqueo temporal."
            : '';

        return new self("PIN de retiro incorrecto.{$suffix}", 422);
    }

    /**
     * checkWithdrawalPinLockout(): se agotaron los intentos de PIN
     * permitidos en la ventana de tiempo — bloqueado temporalmente,
     * independiente de si el usuario ahora escribe el PIN correcto.
     */
    public static function withdrawalPinLocked(int $minutes) : self
    {
        return new self("Demasiados intentos de PIN incorrectos. Vuelve a intentar en {$minutes} minuto(s).", 429);
    }
}
