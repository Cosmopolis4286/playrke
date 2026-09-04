<?php

declare(strict_types=1);

namespace App\Exceptions\Domain\Directory;

use App\Exceptions\Domain\DomainException;

/**
 * Excepciones de dominio del directorio de registros de pago
 * (PagoMovil - Venezuela, PixKey - Brasil, y futuros tipos).
 *
 * DirectoryCrudService y PixKeyCrudService comparten el mismo patrón CRUD
 * (not found / duplicado / cambio de estado inválido / fallo de operación),
 * solo cambia el nombre del tipo de registro en el mensaje — de ahí los
 * factory methods parametrizados por $recordType en vez de uno por tipo.
 *
 * IMPORTANTE:
 * - El dominio expone mensajes humanos directos (no ERROR_CODES) porque
 *   son específicos de este flujo y no se repiten en otro dominio.
 */
final class DirectoryException extends DomainException
{
    /**
     * Ya existe un registro duplicado (mismo documento+banco, o misma
     * clave PIX) para el usuario.
     *
     * @param string $message mensaje completo ya redactado (varía según
     *                        si es creación u actualización, ver callers)
     */
    public static function duplicate(string $message) : self
    {
        return new self($message, 409);
    }

    /**
     * No se puede cambiar el estado de un registro ya eliminado.
     */
    public static function invalidStatusChange() : self
    {
        return new self('No se puede cambiar el estado de un registro eliminado.', 409);
    }

    /**
     * El ID del registro es requerido para poder actualizarlo.
     */
    public static function missingId() : self
    {
        return new self('ID del registro es requerido para actualizar.', 422);
    }

    /**
     * El registro no existe o no pertenece al usuario autenticado.
     *
     * @param string $recordType Nombre humano del tipo de registro
     *                           (ej. "Registro de pago móvil", "Clave PIX").
     */
    public static function notFound(string $recordType) : self
    {
        return new self("{$recordType} no encontrado.", 404);
    }

    /**
     * Fallo inesperado durante la operación (create/update/delete/toggle) —
     * la causa real ya fue logueada por el caller antes de lanzar esto.
     *
     * @param string $message mensaje humano describiendo la operación fallida
     */
    public static function operationFailed(string $message) : self
    {
        return new self($message, 500);
    }
}
