<?php

namespace App\Services\Roles\Admin;

use App\Models\KycDocument;
use App\Models\User;
use App\Services\KycService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\LazyCollection;

/**
 * Capa admin sobre KycService: cola de revisión (listado/detalle/stats/
 * historial) de documentos enviados por jugadores. KycService concentra la
 * lógica de dominio (submitDocument/approve/reject, usada también desde el
 * lado jugador); este servicio solo arma las vistas de consulta que el panel
 * admin necesita y delega aprobar/rechazar al service existente.
 */
final class AdminKycService
{
    private const PAGE_SIZE = 20;

    public function __construct(
        private readonly KycService $kycService,
    ) {
    }

    public function approve(KycDocument $document) : void
    {
        $this->kycService->approve($document, Auth::user());
    }

    /**
     * @return LazyCollection<int, KycDocument>
     */
    public function exportForCsv(array $filters = []) : LazyCollection
    {
        return $this->buildFilteredQuery($filters)->latest('created_at')->cursor();
    }

    /**
     * $filters acepta: status (pending|approved|rejected|all), document_type,
     * search (nombre/username/email del jugador). Sin filtro de status,
     * PENDING es el valor por defecto real de trabajo del admin — es la cola
     * de documentos por revisar, no un archivo histórico.
     */
    public function fetchList(array $filters = [], int $page = 1) : array
    {
        $paginator = $this->buildFilteredQuery($filters)
            ->latest('created_at')
            ->paginate(self::PAGE_SIZE, ['*'], 'page', $page);

        return [
            'columns' => $this->columns(),
            'rows' => $paginator->getCollection()->map(fn (KycDocument $d) => $this->toRow($d))->values(),
            'total' => $paginator->total(),
            'per_page' => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
        ];
    }

    public function reject(KycDocument $document, string $reason) : void
    {
        $this->kycService->reject($document, Auth::user(), $reason);
    }

    public function show(KycDocument $document) : array
    {
        $document->load('user:id,first_name,last_name,username,email,kyc_level,kyc_status');

        return [
            'id' => (string) $document->id,
            'document_type' => $document->document_type,
            'document_number' => $document->document_number,
            'status' => $document->status,
            'rejection_reason' => $document->rejection_reason,
            'reviewed_at' => $document->reviewed_at?->format('d/m/Y H:i:s'),
            'reviewed_by' => $this->reviewerName($document),
            'created_at' => $document->created_at?->format('d/m/Y H:i:s'),
            'has_front' => (bool) $document->front_path,
            'has_back' => (bool) $document->back_path,
            'has_selfie' => (bool) $document->selfie_path,
            'user' => [
                'id' => (string) $document->user->id,
                'full_name' => trim("{$document->user->first_name} {$document->user->last_name}"),
                'username' => $document->user->username,
                'email' => $document->user->email,
                'kyc_level' => $document->user->kyc_level,
                'kyc_status' => $document->user->kyc_status,
            ],
            'history' => $this->historyFor($document),
        ];
    }

    /**
     * Contadores para los StatTile del panel — total pendiente (la cola de
     * trabajo real), aprobados/rechazados hoy (productividad del día) y tasa
     * de rechazo histórica (señal de calidad de las solicitudes entrantes).
     */
    public function stats() : array
    {
        $today = now()->startOfDay();

        $reviewedTotal = KycDocument::whereIn('status', [KycDocument::STATUS_APPROVED, KycDocument::STATUS_REJECTED])->count();
        $rejectedTotal = KycDocument::where('status', KycDocument::STATUS_REJECTED)->count();

        return [
            'pending' => KycDocument::where('status', KycDocument::STATUS_PENDING)->count(),
            'approved_today' => KycDocument::where('status', KycDocument::STATUS_APPROVED)->where('reviewed_at', '>=', $today)->count(),
            'rejected_today' => KycDocument::where('status', KycDocument::STATUS_REJECTED)->where('reviewed_at', '>=', $today)->count(),
            'rejection_rate' => $reviewedTotal > 0 ? round(($rejectedTotal / $reviewedTotal) * 100, 1) : 0.0,
        ];
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder<KycDocument>
     */
    private function buildFilteredQuery(array $filters = [])
    {
        $status = $filters['status'] ?? KycDocument::STATUS_PENDING;

        return KycDocument::query()
            ->with('user:id,first_name,last_name,username,email,kyc_level')
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->when(! empty($filters['document_type']), fn ($q) => $q->where('document_type', $filters['document_type']))
            ->when(! empty($filters['search']), function($q) use ($filters) {
                $term = $filters['search'];
                $q->whereHas('user', function($userQuery) use ($term) {
                    $userQuery->where('first_name', 'LIKE', "%{$term}%")
                        ->orWhere('last_name', 'LIKE', "%{$term}%")
                        ->orWhere('username', 'LIKE', "%{$term}%")
                        ->orWhere('email', 'LIKE', "%{$term}%");
                });
            });
    }

    private function columns() : array
    {
        return [
            ['key' => 'player_name',     'label' => 'Jugador',        'sortable' => false],
            ['key' => 'document_type',   'label' => 'Tipo documento', 'sortable' => false],
            ['key' => 'kyc_level',       'label' => 'Nivel KYC',      'sortable' => false],
            ['key' => 'status',          'label' => 'Estado',         'sortable' => false],
            ['key' => 'submitted_at',    'label' => 'Enviado',        'sortable' => false],
        ];
    }

    /**
     * Envíos previos del mismo usuario (excluyendo el documento actual) —
     * contexto de decisión clave: un jugador con 3 rechazos previos amerita
     * más escrutinio que uno enviando por primera vez.
     */
    private function historyFor(KycDocument $document) : array
    {
        return KycDocument::where('user_id', $document->user_id)
            ->where('id', '!=', $document->id)
            ->latest('created_at')
            ->limit(10)
            ->get()
            ->map(fn (KycDocument $d) => [
                'id' => (string) $d->id,
                'document_type' => $d->document_type,
                'status' => $d->status,
                'rejection_reason' => $d->rejection_reason,
                'created_at' => $d->created_at?->format('d/m/Y H:i:s'),
                'reviewed_at' => $d->reviewed_at?->format('d/m/Y H:i:s'),
            ])
            ->values()
            ->all();
    }

    private function reviewerName(KycDocument $document) : ?string
    {
        if (! $document->reviewed_by) {
            return null;
        }

        $reviewer = User::query()->select('id', 'first_name', 'last_name', 'username')->find($document->reviewed_by);

        if (! $reviewer) {
            return null;
        }

        return trim("{$reviewer->first_name} {$reviewer->last_name}") ?: $reviewer->username;
    }

    private function toRow(KycDocument $document) : array
    {
        return [
            'id' => (string) $document->id,
            'document_type' => $document->document_type,
            'kyc_level' => $document->user?->kyc_level,
            'status' => $document->status,
            'player_name' => trim("{$document->user?->first_name} {$document->user?->last_name}"),
            'player_username' => $document->user?->username,
            'player_email' => $document->user?->email,
            'submitted_at' => $document->created_at?->format('d/m/Y H:i:s'),
        ];
    }
}
