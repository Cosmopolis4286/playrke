<?php

return [
    /*
     * Nivel KYC mínimo para solicitar un retiro (pago móvil, PIX, Binance o
     * cripto). Por defecto 1 (cualquier verificación aprobada): un usuario
     * sin verificar puede depositar y jugar, pero no sacar dinero — corta el
     * vaciado de cuentas robadas y el patrón depositar→retirar sin identidad.
     * 0 = sin requisito.
     */
    'withdrawal_min_kyc_level' => (int) env('WALLET_WITHDRAWAL_MIN_KYC_LEVEL', 1),
];
