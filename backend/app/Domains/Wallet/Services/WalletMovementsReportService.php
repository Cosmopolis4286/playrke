<?php

declare(strict_types=1);

namespace App\Domains\Wallet\Services;

use App\Domains\Wallet\Contracts\WalletMovementRepositoryInterface;
use App\Domains\Wallet\Models\WalletMovement;
use App\Mail\WalletMovementsSummaryMail;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Mail;

/**
 * Resumen semanal de movimientos de billetera enviado por email a los
 * admins con wallet.movements.manage — mismo público que ya recibe las
 * alertas de atraso in-app (ver StaleWalletMovementAlertService), sin crear
 * un permiso nuevo solo para esto.
 */
final class WalletMovementsReportService
{
    public function __construct(
        private readonly WalletMovementRepositoryInterface $movementRepository,
    ) {
    }

    /**
     * @return int cantidad de destinatarios a los que se envió el resumen
     */
    public function sendWeeklySummary(): int
    {
        $recipients = User::permission('wallet.movements.manage')
            ->where('status', 'ACTIVE')
            ->get(['id', 'email']);

        if ($recipients->isEmpty()) {
            return 0;
        }

        $startDate = Carbon::now()->subDays(7)->startOfDay();
        $summary   = $this->buildSummary($startDate);
        $csvContent = $this->buildCsv($startDate);

        foreach ($recipients as $recipient) {
            Mail::to($recipient->email)->queue(new WalletMovementsSummaryMail($summary, $csvContent));
        }

        return $recipients->count();
    }

    private function buildSummary(Carbon $startDate): array
    {
        $query = $this->movementRepository->queryForAdminPanel([])
            ->where('created_at', '>=', $startDate);

        $approvedCount = (clone $query)->where('status', WalletMovement::STATUS_COMPLETED)->count();
        $rejectedCount = (clone $query)->where('status', WalletMovement::STATUS_REJECTED)->count();
        $pendingCount  = WalletMovement::where('status', WalletMovement::STATUS_PENDING)->count();
        $total         = $approvedCount + $rejectedCount;

        $amountByCurrency = (clone $query)
            ->where('status', WalletMovement::STATUS_COMPLETED)
            ->join('wallets', 'wallets.id', '=', 'wallet_movements.wallet_id')
            ->join('currencies', 'currencies.id', '=', 'wallets.currency_id')
            ->selectRaw('currencies.iso_code, currencies.symbol, SUM(wallet_movements.amount) as total')
            ->groupBy('currencies.iso_code', 'currencies.symbol')
            ->get()
            ->map(fn ($row) => ['iso_code' => $row->iso_code, 'symbol' => $row->symbol, 'total' => (float) $row->total])
            ->all();

        $oldestPending = WalletMovement::where('status', WalletMovement::STATUS_PENDING)
            ->with('user:id,first_name,last_name,email')
            ->orderBy('created_at')
            ->limit(5)
            ->get()
            ->map(fn (WalletMovement $m) => [
                'user'       => $m->user ? trim("{$m->user->first_name} {$m->user->last_name}") : '—',
                'type'       => $m->type,
                'amount'     => number_format((float) $m->amount, 2),
                'created_at' => $m->created_at?->format('d/m/Y H:i'),
            ])
            ->all();

        return [
            'periodLabel'      => $startDate->format('d/m/Y') . ' — ' . now()->format('d/m/Y'),
            'approvedCount'    => $approvedCount,
            'rejectedCount'    => $rejectedCount,
            'pendingCount'     => $pendingCount,
            'rejectionRate'    => $total > 0 ? round($rejectedCount / $total * 100, 1) : 0.0,
            'amountByCurrency' => $amountByCurrency,
            'oldestPending'    => $oldestPending,
            'url'              => url('/wallet-movements'),
        ];
    }

    private function buildCsv(Carbon $startDate): string
    {
        $rows = $this->movementRepository->queryForAdminPanel([])
            ->where('created_at', '>=', $startDate)
            ->get();

        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, ['ID', 'Usuario', 'Email', 'Tipo', 'Estado', 'Monto', 'Moneda', 'Referencia', 'Creado', 'Procesado']);

        foreach ($rows as $m) {
            fputcsv($handle, [
                $m->id,
                trim(($m->user?->first_name ?? '') . ' ' . ($m->user?->last_name ?? '')),
                $m->user?->email,
                $m->type,
                $m->status,
                $m->amount,
                $m->wallet?->currency?->iso_code,
                $m->reference_id,
                $m->created_at?->format('Y-m-d H:i:s'),
                $m->processed_at?->format('Y-m-d H:i:s'),
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return $csv;
    }
}
