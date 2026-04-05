<?php

namespace App\Exceptions\Domain\Roles\Admin\Administrator;

use App\Exceptions\DomainException;

final class AdministratorException extends DomainException
{
    public static function notAdministrator(): self
    {
        return new self('El usuario no es un administrador');
    }

    public static function invalidStatusChange(): self
    {
        return new self('No se puede cambiar el estado de este administrador');
    }

    public static function alreadyDeleted(): self
    {
        return new self('El administrador ya está eliminado');
    }

    public static function cannotDeleteLastSuperAdmin(): self
    {
        return new self('No se puede eliminar al último SuperAdmin del sistema');
    }

    public static function notFound(): self
    {
        return new self('Administrador no encontrado');
    }

    public static function cannotModifySelf(): self
    {
        return new self('No puedes modificar tu propio usuario de administrador');
    }
}
