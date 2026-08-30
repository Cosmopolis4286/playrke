<?php

return [

    'csp' => [
        /**
         * Mientras esté en false, la CSP viaja como
         * Content-Security-Policy-Report-Only: el navegador reporta
         * violaciones (ver consola / report-uri más abajo) pero no bloquea
         * nada. Cambiar a true solo después de confirmar que no hay
         * violaciones inesperadas en producción durante unos días.
         */
        'enforce' => env('CSP_ENFORCE', false),
    ],

];
