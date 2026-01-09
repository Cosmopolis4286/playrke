<?php
/*
|--------------------------------------------------------------------------
| Botones adicionales según contexto
|--------------------------------------------------------------------------
| Aquí defines botones extras que se agregan al componente BtnGroup
| dependiendo del contexto (seller, commerce, etc).
|
| Cada botón se normaliza en el componente, por lo que si algún campo falta,
| se completará con valores por defecto.
*/
$defaultExtraButtons = [
    [
        'action' => 'changeStatus',
        'color' => 'btn-secondary',
        'disabled' => true,
        'icon' => 'fa-solid fa-repeat',
        'title' => 'Cambiar status',
    ],
    [
        'action' => 'resetPassword',
        'color' => 'bg-navy',
        'disabled' => true,
        'icon' => 'fa-solid fa-lock',
        'title' => 'Generar contraseña',
    ],
];

return [
    'seller' => $defaultExtraButtons,
    'commerce' => $defaultExtraButtons,
];
