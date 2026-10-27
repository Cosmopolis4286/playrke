<?php

declare(strict_types=1);

namespace App\Services\Directory;

use App\Models\Directories\DirectoryAudit;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Request;

/**
 * Registra eventos de dominio sobre registros de directorio de pago
 * (PagoMovil Venezuela, PixKey Brasil) en directory_audits.
 *
 * Uso:
 *   $this->auditLogger->log(
 *       recordType: DirectoryAudit::TYPE_PAGO_MOVIL,
 *       recordId: $pagoMovil->id,
 *       ownerUserId: $pagoMovil->user_id,
 *       actorUserId: $actorUserId,
 *       event: DirectoryAudit::EVENT_UPDATED,
 *       actorRole: $actorRole,
 *       changes: ['phone' => [$old, $new]],
 *   );
 */
final class DirectoryAuditLogger
{
    public function log(
        string $recordType,
        string $recordId,
        string $ownerUserId,
        ?string $actorUserId,
        string $event,
        ?string $actorRole = null,
        array $changes = [],
    ) : void {
        // En contexto CLI (Jobs, Commands) no hay request HTTP — evitar llamada a Request::ip().
        $isHttp = ! App::runningInConsole();

        DirectoryAudit::create([
            'record_type' => $recordType,
            'record_id' => $recordId,
            'owner_user_id' => $ownerUserId,
            'actor_user_id' => $actorUserId,
            'event' => $event,
            'actor_role' => $actorRole,
            // Un actor null (sistema/job) nunca es "impersonación" — solo
            // aplica cuando hay un actor humano identificado distinto del dueño.
            'is_impersonation' => $actorUserId !== null && $actorUserId !== $ownerUserId,
            'changes' => $changes ?: null,
            'ip_address' => $isHttp ? Request::ip() : null,
            'user_agent' => $isHttp ? Request::userAgent() : null,
        ]);
    }
}
