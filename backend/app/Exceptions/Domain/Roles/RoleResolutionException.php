<?php

declare(strict_types=1);

namespace App\Exceptions\Domain\Roles;

use App\Exceptions\Domain\DomainException;

/**
 * Excepciones de dominio transversales a la resolución de rol/contexto de
 * rol del usuario autenticado — no ligadas a un sub-rol específico
 * (Admin/Commerce/Seller/etc.), a diferencia de AdministratorException,
 * AdminCommerceException, etc.
 *
 * Cubre RoleUserService, RoleEntityResolver, ContextResolverService y
 * DashboardSellerContextService — todos giran en torno al mismo concepto:
 * el rol activo del usuario no se pudo resolver, o no es uno de los
 * soportados por el caller.
 *
 * IMPORTANTE:
 * - El dominio expone mensajes humanos directos (no ERROR_CODES) porque
 *   son específicos de este flujo y no se repiten en otro dominio.
 */
final class RoleResolutionException extends DomainException
{
    /**
     * ContextResolverService::resolve() / DashboardSellerContextService::resolve():
     * el $context solicitado no está entre los casos soportados por el match.
     * Mismo mensaje en ambos callers — no es un typo duplicado, es el mismo
     * concepto ("tipo de contexto de rol inválido") en dos servicios de
     * dashboard distintos.
     */
    public static function contextNotFound() : self
    {
        return new self('Tipo Rol inválido, no existe el contexto', 422);
    }

    /**
     * ExchangeRateCrudService::resolveContext(): el rol no es admin/
     * superadmin (que no requieren entidad asociada) y RoleEntityResolver
     * no encontró una entidad (commerce/customer/etc.) ligada a este rol.
     */
    public static function entityNotFoundForRole(string $role) : self
    {
        return new self("No se encontró entidad asociada al rol '{$role}'", 422);
    }

    /**
     * RoleUserService::getActiveRolePrefix(): el usuario no tiene un rol
     * activo válido, o el rol resuelto no define un prefijo.
     */
    public static function invalidOrUnidentified() : self
    {
        return new self('Rol inválido o no identificado.', 422);
    }

    /**
     * ExchangeRateCrudService::resolveContext(): RoleUserService no pudo
     * resolver ningún rol activo para el usuario — sin rol, no hay forma
     * de determinar qué repositorio de tasas de cambio usar.
     */
    public static function unresolvedRoleForUser(int $userId) : self
    {
        return new self("No se pudo resolver el rol del usuario {$userId}", 422);
    }

    /**
     * RoleEntityResolver: el rol del usuario no está entre los casos
     * soportados por el match — incluye el valor real recibido para
     * facilitar el diagnóstico.
     */
    public static function unsupportedRole(string $role) : self
    {
        return new self("Rol inválido: {$role}", 422);
    }

    /**
     * AdminProfileAccessService::resolve(): el actor pidió ver/gestionar la
     * ficha propia (Seller/Commerce/Subsidiary) de otro usuario
     * (target_user_id) sin tener rol admin/superadmin. Nunca se ignora en
     * silencio (fallback a la propia ficha) para que un intento de acceso
     * indebido sea visible como error, no como un comportamiento inesperado.
     */
    public static function targetUserNotAllowed() : self
    {
        return new self('No tienes permiso para ver la ficha de otro usuario.', 403);
    }
}
