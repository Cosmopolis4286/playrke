<?php

namespace App\Exceptions\Domain\Roles\Admin\Commerce;

use App\Exceptions\Domain\DomainException;

/**
 * Excepciones de dominio relacionadas al agregado Commerce
 * en el contexto Admin.
 *
 * Estas excepciones representan reglas de negocio violadas
 * o estados inválidos del comercio.
 *
 * IMPORTANTE:
 * - El dominio expone ERROR CODES, no mensajes humanos
 */
final class AdminCommerceException extends DomainException
{
    /**
     * Fallo genérico en la creación del comercio.
     */
    public static function creationFailed(): self
    {
        return new self('ADMIN_COMMERCE_CREATION_FAILED');
    }

    /**
     * Fallo genérico en la actualización del comercio.
     */
    public static function updateFailed(): self
    {
        return new self('ADMIN_COMMERCE_UPDATE_FAILED');
    }

    /**
     * Fallo genérico en la eliminación lógica del comercio.
     */
    public static function deleteFailed(): self
    {
        return new self('ADMIN_COMMERCE_DELETE_FAILED');
    }

    /**
     * Fallo al resetear la contraseña del comercio.
     */
    public static function passwordResetFailed(): self
    {
        return new self('ADMIN_COMMERCE_PASSWORD_RESET_FAILED');
    }

    /**
     * El comercio ya se encuentra eliminado.
     */
    public static function alreadyDeleted(): self
    {
        return new self('ADMIN_COMMERCE_ALREADY_DELETED');
    }

    /**
     * Intento de cambiar el estado de un comercio eliminado.
     */
    public static function invalidStatusChange(): self
    {
        return new self('ADMIN_COMMERCE_INVALID_STATUS_CHANGE');
    }

    /**
     * El comercio no existe o no pertenece al contexto actual.
     */
    public static function notFound(): self
    {
        return new self('ADMIN_COMMERCE_NOT_FOUND');
    }

    /**
     * Intento de eliminación física inválida.
     */
    public static function destroyNotAllowed(): self
    {
        return new self('ADMIN_COMMERCE_DESTROY_NOT_ALLOWED');
    }
}
