<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Entradas de Vite
    |--------------------------------------------------------------------------
    |
    | Define los archivos de entrada que Vite debe compilar. Estos archivos serán
    | automáticamente enlazados en tus vistas con la función `@vite`.
    |
    */

    'entrypoints' => [
        'resources/js/app.ts',
        'resources/css/app.css',
    ],

    /*
    |--------------------------------------------------------------------------
    | Desarrollo en servidores
    |--------------------------------------------------------------------------
    |
    | Define las opciones de configuración del servidor de desarrollo de Vite.
    |
    */

    'server' => [
        'host' => '127.0.0.1',
        'port' => 3000,
    ],

];
