<?php

namespace App\Exceptions\Domain\Roles\Admin\Treasurer;

use App\Exceptions\Domain\DomainException;

/**
 * Excepciones de dominio relacionadas al agregado Treasurer
 * en el contexto Admin.
 *
 * Estas excepciones representan reglas de negocio violadas
 * o estados inválidos del tesorero.
 *
 * IMPORTANTE:
 * - El dominio expone ERROR CODES, no mensajes humanos
 */
final class AdminTreasurerException extends DomainException
{
    /**
     * Fallo genérico en la creación del tesorero.
     */
    public static function creationFailed(): self
    {
        return new self('ADMIN_TREASURER_CREATION_FAILED');
    }

    /**
     * Fallo genérico en la actualización del tesorero.
     */
    public static function updateFailed(): self
    {
        return new self('ADMIN_TREASURER_UPDATE_FAILED');
    }

    /**
     * Fallo genérico en la eliminación lógica del tesorero.
     */
    public static function deleteFailed(): self
    {
        return new self('ADMIN_TREASURER_DELETE_FAILED');
    }

    /**
     * Fallo al resetear la contraseña del tesorero.
     */
    public static function passwordResetFailed(): self
    {
        return new self('ADMIN_TREASURER_PASSWORD_RESET_FAILED');
    }

    /**
     * Solo se puede resetear la contraseña de tesoreros activos.
     */
    public static function passwordResetRequiresActiveStatus(): self
    {
        return new self('ADMIN_TREASURER_PASSWORD_RESET_REQUIRES_ACTIVE_STATUS');
    }

    /**
     * El tesorero ya se encuentra eliminado.
     */
    public static function alreadyDeleted(): self
    {
        return new self('ADMIN_TREASURER_ALREADY_DELETED');
    }

    /**
     * Intento de cambiar el estado de un tesorero eliminado.
     */
    public static function invalidStatusChange(): self
    {
        return new self('ADMIN_TREASURER_INVALID_STATUS_CHANGE');
    }

    /**
     * El tesorero no existe o no pertenece al contexto actual.
     */
    public static function notFound(): self
    {
        return new self('ADMIN_TREASURER_NOT_FOUND');
    }

    /**
     * Intento de eliminación física inválida.
     */
    public static function destroyNotAllowed(): self
    {
        return new self('ADMIN_TREASURER_DESTROY_NOT_ALLOWED');
    }
}
