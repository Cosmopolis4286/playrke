<?php

namespace App\Services\Kyc;

use App\Models\KycDocument;
use App\Services\NotificationService;
use App\Traits\ResolvesSuperadminIds;

/**
 * Alerta diaria de documentos KYC pendientes con antigüedad — antes el
 * único lugar donde se veía el volumen de pendientes era entrando
 * manualmente a /admin/kyc, sin ningún aviso proactivo (mismo gap que ya
 * se cerró para permisos individuales, ver
 * NotifyExpiringSpecificPermissionGrantsCommand).
 */
final class KycPendingAlertService
{
    use ResolvesSuperadminIds;

    private const STALE_AFTER_HOURS = 48;

    public function __construct(
        private readonly NotificationService $notificationService,
    ) {
    }

    /**
     * Notifica a superadmins el conteo de documentos KYC pendientes con más
     * de STALE_AFTER_HOURS horas de antigüedad. Sin columna de dedupe: se
     * notifica en cada corrida diaria mientras sigan pendientes — mismo
     * criterio que logs:alert-threshold, aceptable para la cadencia diaria
     * de este aviso.
     *
     * @return int cantidad de documentos pendientes atrasados encontrados
     */
    public function notifyStalePending() : int
    {
        $staleCount = KycDocument::where('status', KycDocument::STATUS_PENDING)
            ->where('created_at', '<=', now()->subHours(self::STALE_AFTER_HOURS))
            ->count();

        if ($staleCount === 0) {
            return 0;
        }

        foreach ($this->superadminIds() as $superadminId) {
            $this->notificationService->create(
                userId: $superadminId,
                type: 'SYSTEM',
                title: '🪪 Documentos KYC pendientes de revisión',
                message: "Hay {$staleCount} documento(s) KYC pendientes con más de " . self::STALE_AFTER_HOURS . ' horas sin revisar.',
                data: [
                    'stale_count' => $staleCount,
                    'url' => '/admin/kyc',
                ],
            );
        }

        return $staleCount;
    }
}
