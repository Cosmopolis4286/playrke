<?php

declare(strict_types=1);

namespace App\Policies\Games\Trivia;

use App\Policies\Roles\BaseRolePolicy;

/**
 * Policy de autorización para disputar/revertir un premio de Trivia ya
 * pagado (TriviaPrizeDisputeController::store() — ver docblock del
 * controller: disparador manual, solo admin/superadmin, sin señal
 * automática de anti-fraude conectada todavía). Gestión hoy solo cubierta
 * por el middleware `role:admin,superadmin` de ruta, sin ningún authorize()
 * explícito en el controller — esta Policy es la capa de defensa en
 * profundidad que faltaba.
 *
 * Registrada contra TriviaParticipant::class (el modelo sobre el que se
 * ejerce la acción) — no sobrescribe ningún método de BaseRolePolicy, así
 * que no hay riesgo de LSP: create() aquí actúa como "puede abrir una
 * disputa", sin necesitar segundo parámetro tipado.
 */
class TriviaPrizeDisputePolicy extends BaseRolePolicy
{
    protected function requiredRole() : string|array
    {
        return ['superadmin', 'admin'];
    }
}
