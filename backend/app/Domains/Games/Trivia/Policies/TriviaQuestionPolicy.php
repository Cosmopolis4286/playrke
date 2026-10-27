<?php

declare(strict_types=1);

namespace App\Domains\Games\Trivia\Policies;

use App\Models\User;
use App\Policies\Roles\BaseRolePolicy;

/**
 * Policy de autorización para el banco global de preguntas de trivia
 * (/admin/trivia-questions/*, TriviaQuestionController) — CRUD administrativo
 * puro, sin ownership que verificar (banco global, no pertenece a una sala
 * específica). Gestión hoy solo cubierta por el middleware `role:admin,
 * superadmin` de rutas, sin ningún authorize() explícito en el controller —
 * esta Policy es la capa de defensa en profundidad que faltaba.
 *
 * No sobrescribe ningún método de BaseRolePolicy: sus genéricos
 * (viewAny/view/create/update/delete) ya están tipados con Model genérico,
 * sin riesgo de LSP, y no hay lógica de ownership que agregar aquí.
 */
class TriviaQuestionPolicy extends BaseRolePolicy
{
    /** Togglear is_active — mismo criterio de rol que el resto, sin estado que bloquee la acción. */
    public function toggleActive(User $user) : bool
    {
        return $this->hasAccess($user);
    }

    protected function requiredRole() : string|array
    {
        return ['superadmin', 'admin'];
    }
}
