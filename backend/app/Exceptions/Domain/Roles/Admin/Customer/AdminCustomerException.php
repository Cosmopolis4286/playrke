<?php

namespace App\Exceptions\Domain\Roles\Admin\Customer;

use App\Exceptions\Domain\DomainException;

/**
 * Excepciones de dominio relacionadas al agregado Customer
 * en el contexto Admin.
 *
 * Estas excepciones representan reglas de negocio violadas
 * o estados inválidos del cliente.
 *
 * IMPORTANTE:
 * - El dominio expone ERROR CODES, no mensajes humanos
 */
final class AdminCustomerException extends DomainException
{
    /**
     * Fallo genérico en la creación del cliente.
     */
    public static function creationFailed(): self
    {
        return new self('ADMIN_CUSTOMER_CREATION_FAILED', 422);
    }

    /**
     * Fallo genérico en la actualización del cliente.
     */
    public static function updateFailed(): self
    {
        return new self('ADMIN_CUSTOMER_UPDATE_FAILED', 422);
    }

    /**
     * Fallo genérico en la eliminación lógica del cliente.
     */
    public static function deleteFailed(): self
    {
        return new self('ADMIN_CUSTOMER_DELETE_FAILED', 422);
    }

    /**
     * Fallo al resetear la contraseña del cliente.
     */
    public static function passwordResetFailed(): self
    {
        return new self('ADMIN_CUSTOMER_PASSWORD_RESET_FAILED', 422);
    }

    /**
     * Solo se puede resetear la contraseña de clientes activos.
     */
    public static function passwordResetRequiresActiveStatus(): self
    {
        return new self('ADMIN_CUSTOMER_PASSWORD_RESET_REQUIRES_ACTIVE_STATUS', 422);
    }

    /**
     * El cliente ya se encuentra eliminado.
     */
    public static function alreadyDeleted(): self
    {
        return new self('ADMIN_CUSTOMER_ALREADY_DELETED', 409);
    }

    /**
     * Intento de cambiar el estado de un cliente eliminado.
     */
    public static function invalidStatusChange(): self
    {
        return new self('ADMIN_CUSTOMER_INVALID_STATUS_CHANGE', 409);
    }

    /**
     * El cliente no existe o no pertenece al contexto actual.
     */
    public static function notFound(): self
    {
        return new self('ADMIN_CUSTOMER_NOT_FOUND', 404);
    }

    /**
     * Intento de eliminación física inválida.
     */
    public static function destroyNotAllowed(): self
    {
        return new self('ADMIN_CUSTOMER_DESTROY_NOT_ALLOWED', 409);
    }
}
