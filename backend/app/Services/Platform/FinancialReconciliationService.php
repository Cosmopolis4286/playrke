<?php

namespace App\Services\Platform;

use App\Models\Platform\FinancialReconciliationItem;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Alertas de conciliación financiera. Reemplaza el "solo queda en el log" de
 * los movimientos de dinero que no pueden completarse automáticamente (ej. la
 * plataforma ya retiró el RKE que habría que devolver). Se muestran en el
 * dashboard de superadmin (panel de Conciliación) hasta que alguien las resuelve.
 */
final class FinancialReconciliationService
{
    public function openCount() : int
    {
        return FinancialReconciliationItem::where('status', FinancialReconciliationItem::STATUS_OPEN)->count();
    }

    /** @return array<int, array<string, mixed>> */
    public function openItems(int $limit = 50) : array
    {
        return FinancialReconciliationItem::with('currency:id,iso_code,symbol')
            ->where('status', FinancialReconciliationItem::STATUS_OPEN)
            ->latest()
            ->limit($limit)
            ->get()
            ->map(fn (FinancialReconciliationItem $i) => [
                'id' => $i->id,
                'type' => $i->type,
                'message' => $i->message,
                'amount' => (float) $i->amount,
                'currency' => $i->currency?->iso_code,
                'symbol' => $i->currency?->symbol,
                'room_id' => $i->room_id,
                'lot_id' => $i->lot_id,
                'details' => $i->details,
                'created_at' => $i->created_at?->toISOString(),
            ])
            ->all();
    }

    /**
     * Registra una alerta. Idempotente por $idempotencyKey (reintentos de jobs).
     * Nunca lanza: registrar la alerta no debe romper el flujo que la origina.
     */
    public function record(
        string $type,
        string $message,
        float $amount = 0.0,
        ?string $currencyId = null,
        ?string $roomId = null,
        ?string $lotId = null,
        ?string $idempotencyKey = null,
        array $details = [],
    ) : ?FinancialReconciliationItem {
        try {
            if ($idempotencyKey !== null) {
                $existing = FinancialReconciliationItem::where('idempotency_key', $idempotencyKey)->first();
                if ($existing) {
                    return $existing;
                }
            }

            return FinancialReconciliationItem::create([
                'type' => $type,
                'status' => FinancialReconciliationItem::STATUS_OPEN,
                'message' => mb_substr($message, 0, 255),
                'amount' => round($amount, 4),
                'currency_id' => $currencyId,
                'room_id' => $roomId,
                'lot_id' => $lotId,
                'idempotency_key' => $idempotencyKey,
                'details' => $details ?: null,
            ]);
        } catch (Throwable $e) {
            Log::error('[FinancialReconciliationService] No se pudo registrar la alerta de conciliación', [
                'type' => $type, 'message' => $message, 'amount' => $amount, 'error' => $e->getMessage(),
            ]);

            return null;
        }
    }

    public function resolve(string $id, string $resolvedBy, ?string $notes = null) : FinancialReconciliationItem
    {
        $item = FinancialReconciliationItem::findOrFail($id);
        if ($item->status === FinancialReconciliationItem::STATUS_OPEN) {
            $item->update([
                'status' => FinancialReconciliationItem::STATUS_RESOLVED,
                'resolved_at' => now(),
                'resolved_by' => $resolvedBy,
                'resolution_notes' => $notes ? mb_substr($notes, 0, 255) : null,
            ]);
        }

        return $item;
    }
}
