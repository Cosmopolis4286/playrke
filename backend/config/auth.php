<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Valores por defecto de autenticación
    |--------------------------------------------------------------------------
    |
    | Aquí se define el guard y broker de contraseñas por defecto. Puedes
    | modificarlos según tus necesidades, pero estos valores funcionan
    | para la mayoría de aplicaciones.
    |
    */
    'defaults' => [
        'guard' => env('AUTH_GUARD', 'web'),       // Guard por defecto
        'passwords' => env('AUTH_PASSWORD_BROKER', 'users'), // Broker por defecto
    ],

    /*
    |--------------------------------------------------------------------------
    | Guards de autenticación
    |--------------------------------------------------------------------------
    |
    | Un "guard" define cómo los usuarios se autentican para cada petición.
    | Por defecto tenemos 'web' para sesiones y 'api' para tokens (JWT o Sanctum).
    |
    */
    'guards' => [
        'web' => [
            'driver' => 'session',   // Usa sesiones para autenticación
            'provider' => 'users',   // Proveedor de usuarios asociado
            'hash' => false,         // Opcional: true si deseas hash adicional en sesiones
        ],

        'api' => [
            'driver' => 'token',     // Autenticación vía token simple
            'provider' => 'users',
            'hash' => true,          // Hashear el token para mayor seguridad
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Proveedores de usuarios
    |--------------------------------------------------------------------------
    |
    | Cada guard necesita un proveedor que defina cómo obtener los usuarios
    | desde la base de datos o almacenamiento. Eloquent es el más común.
    |
    */
    'providers' => [
        'users' => [
            'driver' => 'eloquent',       // Modelo Eloquent para usuarios
            'model' => env('AUTH_MODEL', App\Models\User::class),
        ],

        // Alternativa: si usaras base de datos directamente
        // 'users' => [
        //     'driver' => 'database',
        //     'table' => 'users',
        // ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Restablecimiento de contraseñas
    |--------------------------------------------------------------------------
    |
    | Configura la tabla y el tiempo de expiración de los tokens para reset.
    | Se recomienda mantener un tiempo corto para mayor seguridad.
    |
    */
    'passwords' => [
        'users' => [
            'provider' => 'users',
            'table' => env('AUTH_PASSWORD_RESET_TOKEN_TABLE', 'password_reset_tokens'),
            'expire' => 60,      // Minutos de validez del token
            'throttle' => 60,    // Tiempo mínimo entre solicitudes
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Tiempo de expiración de confirmación de contraseña
    |--------------------------------------------------------------------------
    |
    | Define cuántos segundos dura la ventana de confirmación antes de pedir
    | nuevamente la contraseña. Por defecto 3 horas.
    |
    */
    'password_timeout' => env('AUTH_PASSWORD_TIMEOUT', 10800),

    /*
    |--------------------------------------------------------------------------
    | Configuración opcional adicional
    |--------------------------------------------------------------------------
    |
    | Aquí puedes agregar medidas de seguridad extra:
    | - Verificación de email obligatoria
    | - Regeneración de sesión al loguear
    | - Protección CSRF en API si usas Sanctum
    |
    */
];
