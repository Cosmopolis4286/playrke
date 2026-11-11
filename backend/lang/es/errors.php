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
    'ADMIN_COMMERCE_IS_DEFAULT' => 'Este comercio está marcado como principal. Reasigna la marca a otro comercio antes de bloquearlo o eliminarlo.',
    'ADMIN_COMMERCE_NOT_DEFAULT' => 'Este comercio ya no está marcado como principal — vuelve a cargar la página.',

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
    'COMMISSION_ABOVE_PARENT' => 'La comisión (:rate%) no puede superar la de su nivel superior (:max%).',
    'COMMISSION_CHILDREN_ABOVE_PARENT' => 'Sus vendedores tienen comisión de hasta :rate%, mayor que la de la comercializadora (:max%). Baja primero la comisión de esos vendedores.',
    'MANAG_SELLER_INVALID_MANAGER' => 'El vendedor debe reportar a una comercializadora o agencia válida. Selecciona un manager.',
    'COMMISSION_BELOW_CHILDREN' => 'La comisión (:rate%) no puede ser menor que la de su red (:min%). Baja primero la comisión de sus agencias/vendedores.',
    'MANAG_SELLER_UPDATE_FAILED' => 'No se pudo actualizar el vendedor.',
    'MANAG_SELLER_DELETE_FAILED' => 'No se pudo eliminar el vendedor.',
    'MANAG_SELLER_PASSWORD_RESET_FAILED' => 'No se pudo resetear la contraseña del vendedor.',
    'MANAG_SELLER_PASSWORD_RESET_REQUIRES_ACTIVE_STATUS' => 'Solo se puede resetear la contraseña de vendedores activos.',

    // Impersonación (soporte a jugadores)
    'ALREADY_IMPERSONATING' => 'Ya hay una sesión de soporte activa. Debes finalizarla antes de iniciar otra.',
    'IMPERSONATION_TARGET_ROLE_NOT_ALLOWED' => 'Este rol de usuario no se puede impersonar.',
    'NOT_IMPERSONATING' => 'No hay ninguna sesión de soporte activa para finalizar.',

    // Sucursales (admin/subsidiary)
    'ADMIN_SUBSIDIARY_NOT_FOUND' => 'Sucursal no encontrada.',
    'ADMIN_SUBSIDIARY_ALREADY_DELETED' => 'La sucursal ya fue eliminada.',
    'ADMIN_SUBSIDIARY_INVALID_STATUS_CHANGE' => 'No se puede cambiar el estado de esta sucursal.',
    'ADMIN_SUBSIDIARY_DESTROY_NOT_ALLOWED' => 'No se puede eliminar definitivamente esta sucursal.',
    'ADMIN_SUBSIDIARY_CREATION_FAILED' => 'No se pudo crear la sucursal.',
    'ADMIN_SUBSIDIARY_UPDATE_FAILED' => 'No se pudo actualizar la sucursal.',
    'ADMIN_SUBSIDIARY_DELETE_FAILED' => 'No se pudo eliminar la sucursal.',
    'ADMIN_SUBSIDIARY_PASSWORD_RESET_FAILED' => 'No se pudo resetear la contraseña de la sucursal.',
    'ADMIN_SUBSIDIARY_PASSWORD_RESET_REQUIRES_ACTIVE_STATUS' => 'Solo se puede resetear la contraseña de sucursales activas.',

    // Salas de Bingo (games/bingo/room)
    'BINGO_ROOM_NOT_FOUND' => 'La sala de bingo no existe.',
    'BINGO_ROOM_ALREADY_DELETED' => 'La sala de bingo ya fue eliminada.',
    'BINGO_ROOM_INVALID_STATUS_CHANGE' => 'No se puede cambiar el estado de esta sala.',
    'BINGO_ROOM_DESTROY_NOT_ALLOWED' => 'No puedes eliminar físicamente una sala con historial de jugadores, ventas o premios.',
    'BINGO_ROOM_CREATION_FAILED' => 'No se pudo crear la sala de bingo.',
    'BINGO_ROOM_UPDATE_FAILED' => 'No se pudo actualizar la sala de bingo.',
    'BINGO_ROOM_DELETE_FAILED' => 'No se pudo eliminar la sala de bingo.',
    'BINGO_ROOM_PRIVATE_NOT_VERIFIED' => 'Debes verificar el código de acceso antes de comprar en esta sala privada.',
    'BINGO_ROOM_INVALID_ROOM_TYPE' => 'El tipo de sala seleccionado no es válido.',
    'BINGO_ROOM_INVALID_GAME_MODE' => 'El modo de juego seleccionado no es válido.',
    'BINGO_ROOM_INVALID_PRIZE_POOL_TYPE' => 'El tipo de bote seleccionado no es válido.',
    'BINGO_ROOM_EXPIRED_STATUS_LOCKED' => 'No puedes cambiar el estado de una sala cuya fecha/hora ya expiró.',
    'BINGO_ROOM_HAS_PLAYERS' => 'No puedes eliminar una sala que ya tiene jugadores registrados.',
    'BINGO_ROOM_ACTIVATION_NOT_ALLOWED' => 'Solo las salas sin horario programado pueden activarse manualmente.',
    'BINGO_ROOM_ACTIVATION_FORBIDDEN' => 'No tienes permiso para activar esta sala.',
    'BINGO_ROOM_ACTIVATION_INVALID_STATE' => 'Solo se pueden activar manualmente salas en estado ACTIVE cuyo sorteo no haya iniciado.',
    'BINGO_ROOM_SCHEDULE_START_INVALID_DATE' => 'La fecha y hora de inicio deben ser al menos 10 minutos posteriores al momento actual.',
    'BINGO_ROOM_MIN_CARDS_NOT_REACHED' => 'La sala requiere al menos :required cartones vendidos para iniciar (actualmente tiene :current).',
    'BINGO_ROOM_MIN_PLAYERS_NOT_REACHED' => 'La sala requiere al menos :required jugadores para iniciar (actualmente tiene :current).',
    'BINGO_ROOM_FULL' => 'La sala ha alcanzado el límite máximo de jugadores.',
    'BINGO_ROOM_CARD_LIMIT_PARTIAL' => 'Esta sala solo admite :remaining cartón(es) más (solicitaste :requested).',
    'BINGO_ROOM_CARD_LIMIT_EXHAUSTED' => 'Esta sala ya alcanzó su tope de cartones disponibles.',
    'BINGO_ROOM_CURRENCY_REMOVAL_WITH_SALES_NOT_ALLOWED' => 'No puedes quitar :currencies: ya tiene cartones vendidos en esta sala.',
    'BINGO_ROOM_NOT_ACCESSIBLE' => 'La sala no existe o no tenés acceso a ella.',
    'BINGO_ROOM_INVITATIONS_ONLY_VIP' => 'La lista de invitados solo aplica a salas VIP.',
    'BINGO_ROOM_INVITED_PLAYER_NOT_FOUND' => 'No existe un jugador registrado con ese usuario o email.',
    'BINGO_ROOM_INVITATION_ALREADY_EXISTS' => 'Ese jugador o teléfono ya está en la lista de invitados.',
    'BINGO_ROOM_INVITATION_INVALID_PHONE' => 'El teléfono no es válido: ingresa el número completo.',
    'BINGO_ROOM_NOT_EDITABLE_STATUS' => 'Solo se puede editar una sala cuyo sorteo no ha iniciado ni finalizado.',
    'BINGO_ROOM_FIELDS_LOCKED_BY_SALES' => 'Esta sala ya tiene cartones vendidos: no se puede cambiar :fields.',

    // Patrones de Bingo (games/bingo/pattern)
    'BINGO_PATTERN_DUPLICATE_NAME' => 'Ya existe un patrón con ese nombre.',

    // Cartones de Bingo (games/bingo/card)
    'BINGO_CARD_SELLER_NOT_FOUND' => 'No se encontró el vendedor.',
    'BINGO_CARD_COMMERCE_NOT_FOUND' => 'No se encontró el comercio.',
    'BINGO_CARD_HIERARCHY_NOT_ACTIVE' => 'Tu cuenta, agencia o comercio no está activo. Contacta a soporte para más información.',
    'BINGO_CARD_ROOM_NOT_AVAILABLE' => 'Esta sala no está disponible para comprar cartones.',
    'BINGO_CARD_VIP_BUYER_NOT_INVITED' => 'Esta sala es VIP y el teléfono del comprador no está en su lista de invitados. Pide al creador de la sala que lo agregue.',
    'BINGO_CARD_SALES_CLOSED' => 'Las ventas de esta sala ya cerraron: se cierran :minutes minutos antes del inicio para que el comercio alcance a aprobar el comprobante. Elige otra sala.',
    'BINGO_CARD_SALES_CLOSED_ROOM_STARTED' => 'La sala ya inició: la venta de cartones está cerrada. Elige otra sala.',
    'BINGO_ROOM_VOID_DRAW_NOT_STARTED' => 'La sala no tiene un sorteo iniciado: usa «Cancelar sala».',
    'BINGO_ROOM_VOID_DRAW_HAS_PRIZES' => 'La sala ya tiene premios registrados: resuélvelos en «Premios por resolver» antes de anular el sorteo.',
    'RECEIVABLE_SETTLEMENT_AMOUNT_MISMATCH' => 'El total pendiente cambió (ahora :current, se mostró :expected): se anuló o registró una venta en el medio. Recarga el panel y confirma el nuevo monto.',
    'BINGO_PAYMENT_LATE_APPROVAL_CONFIRM' => 'El sorteo de esta sala ya inició: si apruebas el pago, los cartones entran con las bolas ya cantadas. Confirma que el comprador lo acepta.',
    'EXCHANGE_RATE_DEVIATION_EXCEEDED' => 'Tu tasa de :currency se aleja más del :max_pct% permitido respecto a la oficial (:official). Usa un valor entre :min y :max.',
    'BINGO_CARD_CURRENCY_NOT_AVAILABLE' => 'La moneda seleccionada no está disponible para esta sala.',
    'BINGO_CARD_GENERATION_FAILED' => 'No se pudieron generar los cartones.',
    'BINGO_CARD_PRICE_CHANGED' => 'El precio del cartón cambió (antes :expected, ahora :current). Revisa el nuevo monto antes de cobrar.',
    'BINGO_CARD_DUPLICATE_RECEIPT' => 'Este número de comprobante ya fue usado en otra venta. Cada pago solo puede respaldar una venta.',
    'BINGO_CARD_PAYMENT_RECORD_FAILED' => 'No se pudo registrar el pago de la venta. No se generó ningún cartón; inténtalo de nuevo.',
    'BINGO_CARD_UNAUTHORIZED_ROLE' => 'No tienes permiso para comprar cartones con este rol.',
    'BINGO_CARD_UNSUPPORTED_CURRENCY_ROLE' => 'Tu rol no admite operar con esta moneda.',
    'BINGO_CARD_UNSUPPORTED_COMMERCE_ROLE' => 'Tu rol no admite esta operación de comercio.',
    'BINGO_CARD_UNSUPPORTED_COMMISSION_ROLE' => 'Tu rol no admite el cálculo de comisión solicitado.',
    'BINGO_CARD_INVALID_MANAGER_ROLE' => 'El rol del gestor no es válido para esta operación.',
    'BINGO_CARD_INVALID_EXCHANGE_RATE' => 'La tasa de cambio no es válida.',
    'BINGO_CARD_UNAUTHORIZED' => 'No tienes permiso para realizar esta acción.',
    'BINGO_CARD_CUSTOM_NOT_ALLOWED' => 'Esta sala no permite cartones personalizados.',
    'BINGO_CARD_CUSTOM_EXCEEDS_COUNT' => 'La cantidad de cartones personalizados excede la cantidad total solicitada.',
    'BINGO_CARD_CUSTOM_EXCEED_EVERY_X_LIMIT' => 'Con esta cantidad de cartones solo puedes personalizar :max.',
    'BINGO_CARD_CUSTOM_MATRIX_INVALID' => 'Cartón #:position: :error',
    'BINGO_CARD_INSUFFICIENT_BALANCE' => 'Saldo insuficiente en la billetera. Saldo actual: :balance, Monto requerido: :required',
    'BINGO_CARD_NO_ACTIVE_WALLET' => 'No tienes una billetera activa para esta compra.',

    // Wallet (genérico, App\Domains\Wallet\Exceptions)
    'WALLET_UNAVAILABLE' => 'No se pudo acceder a la billetera en este momento. Intenta más tarde.',
];
