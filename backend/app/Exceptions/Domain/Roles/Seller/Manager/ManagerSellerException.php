<?php

namespace App\Exceptions\Domain\Roles\Seller\Manager;

use App\Exceptions\Domain\DomainException;

/**
 * Excepciones de dominio relacionadas al agregado Seller Manager
 * en el contexto Admin.
 *
 * Estas excepciones representan reglas de negocio violadas
 * o estados inválidos del vendedor.
 *
 * IMPORTANTE:
 * - El dominio expone ERROR CODES, no mensajes humanos
 */
final class ManagerSellerException extends DomainException
{
    /**
     * El vendedor ya se encuentra eliminado.
     */
    public static function alreadyDeleted() : self
    {
        return new self('MANAG_SELLER_ALREADY_DELETED', 409);
    }

    /**
     * Fallo genérico en la creación del vendedor.
     */
    public static function creationFailed() : self
    {
        return new self('MANAG_SELLER_CREATION_FAILED', 422);
    }

    /**
     * Fallo genérico en la eliminación lógica del vendedor.
     */
    public static function deleteFailed() : self
    {
        return new self('MANAG_SELLER_DELETE_FAILED', 422);
    }

    /**
     * Intento de eliminación física inválida.
     */
    public static function destroyNotAllowed() : self
    {
        return new self('MANAG_SELLER_DESTROY_NOT_ALLOWED', 409);
    }

    /**
     * El manager (propio o elegido) no es una Comercializadora ni una Agencia
     * con Comercializadora — ej. un 'customer', o un vendedor huérfano legado
     * cuyo manager_user_id apunta a un customer (ver commissions:audit-hierarchy).
     */
    public static function invalidManager() : self
    {
        return new self('MANAG_SELLER_INVALID_MANAGER', 422);
    }

    /**
     * Intento de cambiar el estado de un vendedor eliminado.
     */
    public static function invalidStatusChange() : self
    {
        return new self('MANAG_SELLER_INVALID_STATUS_CHANGE', 409);
    }

    /**
     * El vendedor no existe o no pertenece al contexto actual.
     */
    public static function notFound() : self
    {
        return new self('MANAG_SELLER_NOT_FOUND', 404);
    }

    /**
     * Fallo al resetear la contraseña del vendedor.
     */
    public static function passwordResetFailed() : self
    {
        return new self('MANAG_SELLER_PASSWORD_RESET_FAILED', 422);
    }

    /**
     * Solo se puede resetear la contraseña de vendedores activos.
     */
    public static function passwordResetRequiresActiveStatus() : self
    {
        return new self('MANAG_SELLER_PASSWORD_RESET_REQUIRES_ACTIVE_STATUS', 422);
    }

    /**
     * Fallo genérico en la actualización del vendedor.
     */
    public static function updateFailed() : self
    {
        return new self('MANAG_SELLER_UPDATE_FAILED', 422);
    }
}
