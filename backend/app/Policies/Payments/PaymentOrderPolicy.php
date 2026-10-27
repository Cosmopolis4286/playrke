<?php

namespace App\Policies\Payments;

use App\Domains\Payments\Models\PaymentOrder;
use App\Models\User;
use App\Policies\Roles\BaseRolePolicy;

/**
 * Policy de autorización para el panel admin de órdenes de pago USDT-TRC20.
 * Solo superadmin/admin (mismo criterio que EscrowPolicy).
 *
 * reevaluate() es un método propio (no update() heredado de BaseRolePolicy):
 * forzar una reevaluación contra la blockchain no es una mutación genérica
 * del modelo — solo tiene sentido mientras la orden no sea terminal
 * (PaymentOrder::isTerminal()), reevaluar una orden ya CREDITED/EXPIRED/etc.
 * no hace nada útil.
 */
class PaymentOrderPolicy extends BaseRolePolicy
{
    public function reevaluate(User $user, PaymentOrder $order) : bool
    {
        return $this->hasAccess($user) && ! $order->isTerminal();
    }

    protected function requiredRole() : string|array
    {
        return ['superadmin', 'admin'];
    }
}
