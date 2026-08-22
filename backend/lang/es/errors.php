<?php

/**
 * Mensajes traducidos para error codes de dominio (DomainException).
 *
 * Handler::resolveMessage() resuelve "errors.{errorCode}" contra este
 * archivo; si una clave no existe aquí, se muestra el error code crudo
 * al usuario (comportamiento previo de todo este árbol de excepciones).
 */
return [
    // Usuarios (admin/user)
    'ADMIN_USER_NOT_FOUND' => 'Usuario no encontrado.',
    'ADMIN_USER_ALREADY_DELETED' => 'El usuario ya fue eliminado.',
    'ADMIN_USER_INVALID_STATUS_CHANGE' => 'No se puede cambiar el estado de este usuario.',
    'ADMIN_USER_PASSWORD_RESET_REQUIRES_ACTIVE_STATUS' => 'Solo se puede resetear la contraseña de usuarios activos.',
    'ADMIN_USER_WALLET_LOAD_REQUIRES_ACTIVE_STATUS' => 'Solo se puede cargar saldo a usuarios activos.',
    'ADMIN_USER_WALLET_LOAD_REQUIRES_ELIGIBLE_ROLE' => 'Solo se puede cargar saldo a usuarios con rol Comercio, Vendedor o Jugador.',
    'ADMIN_USER_INVALID_WALLET_AMOUNT' => 'El monto ingresado no es válido.',
    'ADMIN_USER_INVALID_XP_AMOUNT' => 'El monto de XP ingresado no es válido.',
    'ADMIN_USER_XP_GRANT_REQUIRES_PLAYER_ROLE' => 'Solo se puede otorgar XP a usuarios con rol Jugador.',
    'ADMIN_USER_DELETE_FAILED' => 'No se pudo eliminar el usuario.',
    'ADMIN_USER_CANNOT_MODIFY_SELF' => 'No puedes realizar esta acción sobre tu propio usuario.',
    'ADMIN_USER_CANNOT_MODIFY_SUPERADMIN' => 'No tienes permisos para modificar una cuenta Super Administrador.',
    'ADMIN_USER_CANNOT_MODIFY_OTHER_ADMIN' => 'Solo un Super Administrador puede modificar la cuenta de otro Administrador.',
    'ADMIN_USER_CANNOT_ASSIGN_ADMIN_ROLE' => 'Solo un Super Administrador puede asignar el rol Administrador.',

    // Administradores (admin/administrator)
    'ADMINISTRATOR_NOT_FOUND' => 'Administrador no encontrado.',
    'ADMINISTRATOR_NOT_ADMINISTRATOR' => 'El usuario no es un administrador.',
    'ADMINISTRATOR_ALREADY_DELETED' => 'El administrador ya fue eliminado.',
    'ADMINISTRATOR_INVALID_STATUS_CHANGE' => 'No se puede cambiar el estado de este administrador.',
    'ADMINISTRATOR_CANNOT_DELETE_LAST_SUPERADMIN' => 'No se puede eliminar al último SuperAdmin del sistema.',
    'ADMINISTRATOR_CANNOT_MODIFY_SELF' => 'No puedes realizar esta acción sobre tu propio usuario de administrador.',
    'ADMINISTRATOR_CANNOT_REMOVE_LAST_ACTIVE' => 'No se puede desactivar/eliminar al último administrador activo del sistema.',
    'ADMINISTRATOR_PASSWORD_RESET_REQUIRES_ACTIVE_STATUS' => 'Solo se puede resetear la contraseña de administradores activos.',

    // Comercios (admin/commerce)
    'ADMIN_COMMERCE_NOT_FOUND' => 'Comercio no encontrado.',
    'ADMIN_COMMERCE_ALREADY_DELETED' => 'El comercio ya fue eliminado.',
    'ADMIN_COMMERCE_INVALID_STATUS_CHANGE' => 'No se puede cambiar el estado de este comercio.',
    'ADMIN_COMMERCE_DESTROY_NOT_ALLOWED' => 'No se puede eliminar definitivamente este comercio.',
    'ADMIN_COMMERCE_CREATION_FAILED' => 'No se pudo crear el comercio.',
    'ADMIN_COMMERCE_UPDATE_FAILED' => 'No se pudo actualizar el comercio.',
    'ADMIN_COMMERCE_DELETE_FAILED' => 'No se pudo eliminar el comercio.',
    'ADMIN_COMMERCE_PASSWORD_RESET_FAILED' => 'No se pudo resetear la contraseña del comercio.',
    'ADMIN_COMMERCE_PASSWORD_RESET_REQUIRES_ACTIVE_STATUS' => 'Solo se puede resetear la contraseña de comercios activos.',

    // Clientes (admin/customer)
    'ADMIN_CUSTOMER_NOT_FOUND' => 'Cliente no encontrado.',
    'ADMIN_CUSTOMER_ALREADY_DELETED' => 'El cliente ya fue eliminado.',
    'ADMIN_CUSTOMER_INVALID_STATUS_CHANGE' => 'No se puede cambiar el estado de este cliente.',
    'ADMIN_CUSTOMER_DESTROY_NOT_ALLOWED' => 'No se puede eliminar definitivamente este cliente.',
    'ADMIN_CUSTOMER_CREATION_FAILED' => 'No se pudo crear el cliente.',
    'ADMIN_CUSTOMER_UPDATE_FAILED' => 'No se pudo actualizar el cliente.',
    'ADMIN_CUSTOMER_DELETE_FAILED' => 'No se pudo eliminar el cliente.',
    'ADMIN_CUSTOMER_PASSWORD_RESET_FAILED' => 'No se pudo resetear la contraseña del cliente.',
    'ADMIN_CUSTOMER_PASSWORD_RESET_REQUIRES_ACTIVE_STATUS' => 'Solo se puede resetear la contraseña de clientes activos.',

    // Jugadores (admin/player)
    'ADMIN_PLAYER_NOT_FOUND' => 'Jugador no encontrado.',
    'ADMIN_PLAYER_ALREADY_DELETED' => 'El jugador ya fue eliminado.',
    'ADMIN_PLAYER_INVALID_STATUS_CHANGE' => 'No se puede cambiar el estado de este jugador.',
    'ADMIN_PLAYER_CREATION_FAILED' => 'No se pudo crear el jugador.',
    'ADMIN_PLAYER_UPDATE_FAILED' => 'No se pudo actualizar el jugador.',
    'ADMIN_PLAYER_DELETE_FAILED' => 'No se pudo eliminar el jugador.',
    'ADMIN_PLAYER_PASSWORD_RESET_FAILED' => 'No se pudo resetear la contraseña del jugador.',
    'ADMIN_PLAYER_PASSWORD_RESET_REQUIRES_ACTIVE_STATUS' => 'Solo se puede resetear la contraseña de jugadores activos.',

    // Tesoreros (admin/treasurer)
    'ADMIN_TREASURER_NOT_FOUND' => 'Tesorero no encontrado.',
    'ADMIN_TREASURER_ALREADY_DELETED' => 'El tesorero ya fue eliminado.',
    'ADMIN_TREASURER_INVALID_STATUS_CHANGE' => 'No se puede cambiar el estado de este tesorero.',
    'ADMIN_TREASURER_DESTROY_NOT_ALLOWED' => 'No se puede eliminar definitivamente este tesorero.',
    'ADMIN_TREASURER_CREATION_FAILED' => 'No se pudo crear el tesorero.',
    'ADMIN_TREASURER_UPDATE_FAILED' => 'No se pudo actualizar el tesorero.',
    'ADMIN_TREASURER_DELETE_FAILED' => 'No se pudo eliminar el tesorero.',
    'ADMIN_TREASURER_PASSWORD_RESET_FAILED' => 'No se pudo resetear la contraseña del tesorero.',
    'ADMIN_TREASURER_PASSWORD_RESET_REQUIRES_ACTIVE_STATUS' => 'Solo se puede resetear la contraseña de tesoreros activos.',

    // Vendedores gestionados por Manager (seller/manager)
    'MANAG_SELLER_NOT_FOUND' => 'Vendedor no encontrado.',
    'MANAG_SELLER_ALREADY_DELETED' => 'El vendedor ya fue eliminado.',
    'MANAG_SELLER_INVALID_STATUS_CHANGE' => 'No se puede cambiar el estado de este vendedor.',
    'MANAG_SELLER_DESTROY_NOT_ALLOWED' => 'No se puede eliminar definitivamente este vendedor.',
    'MANAG_SELLER_CREATION_FAILED' => 'No se pudo crear el vendedor.',
    'MANAG_SELLER_UPDATE_FAILED' => 'No se pudo actualizar el vendedor.',
    'MANAG_SELLER_DELETE_FAILED' => 'No se pudo eliminar el vendedor.',
    'MANAG_SELLER_PASSWORD_RESET_FAILED' => 'No se pudo resetear la contraseña del vendedor.',
    'MANAG_SELLER_PASSWORD_RESET_REQUIRES_ACTIVE_STATUS' => 'Solo se puede resetear la contraseña de vendedores activos.',

    // Impersonación (soporte a jugadores)
    'ALREADY_IMPERSONATING' => 'Ya hay una sesión de soporte activa. Debes finalizarla antes de iniciar otra.',
    'IMPERSONATION_TARGET_MUST_BE_PLAYER' => 'Solo se puede impersonar a usuarios con rol Jugador.',
    'NOT_IMPERSONATING' => 'No hay ninguna sesión de soporte activa para finalizar.',
];
