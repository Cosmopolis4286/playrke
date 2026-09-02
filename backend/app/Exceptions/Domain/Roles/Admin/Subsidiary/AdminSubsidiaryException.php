<?php

namespace App\Exceptions\Domain\Roles\Admin\Subsidiary;

use App\Exceptions\Domain\DomainException;

/**
 * Excepciones de dominio relacionadas al agregado Subsidiary
 * en el contexto Admin.
 *
 * Estas excepciones representan reglas de negocio violadas
 * o estados inválidos de la sucursal.
 *
 * IMPORTANTE:
 * - El dominio expone ERROR CODES, no mensajes humanos
 */
final class AdminSubsidiaryException extends DomainException
{
    /**
     * Fallo genérico en la creación de la sucursal.
     */
    public static function creationFailed(): self
    {
        return new self('ADMIN_SUBSIDIARY_CREATION_FAILED');
    }

    /**
     * Fallo genérico en la actualización de la sucursal.
     */
    public static function updateFailed(): self
    {
        return new self('ADMIN_SUBSIDIARY_UPDATE_FAILED');
    }

    /**
     * Fallo genérico en la eliminación lógica de la sucursal.
     */
    public static function deleteFailed(): self
    {
        return new self('ADMIN_SUBSIDIARY_DELETE_FAILED');
    }

    /**
     * Fallo al resetear la contraseña de la sucursal.
     */
    public static function passwordResetFailed(): self
    {
        return new self('ADMIN_SUBSIDIARY_PASSWORD_RESET_FAILED');
    }

    /**
     * Solo se puede resetear la contraseña de sucursales activas.
     */
    public static function passwordResetRequiresActiveStatus(): self
    {
        return new self('ADMIN_SUBSIDIARY_PASSWORD_RESET_REQUIRES_ACTIVE_STATUS');
    }

    /**
     * La sucursal ya se encuentra eliminada.
     */
    public static function alreadyDeleted(): self
    {
        return new self('ADMIN_SUBSIDIARY_ALREADY_DELETED');
    }

    /**
     * Intento de cambiar el estado de una sucursal eliminada.
     */
    public static function invalidStatusChange(): self
    {
        return new self('ADMIN_SUBSIDIARY_INVALID_STATUS_CHANGE');
    }

    /**
     * La sucursal no existe o no pertenece al contexto actual.
     */
    public static function notFound(): self
    {
        return new self('ADMIN_SUBSIDIARY_NOT_FOUND');
    }

    /**
     * Intento de eliminación física inválida.
     */
    public static function destroyNotAllowed(): self
    {
        return new self('ADMIN_SUBSIDIARY_DESTROY_NOT_ALLOWED');
    }
}
