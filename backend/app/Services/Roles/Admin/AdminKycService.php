<?php

namespace App\Services\Roles\Admin;

use App\Models\KycDocument;
use App\Models\User;
use App\Services\KycService;
use Illuminate\Support\Facades\Auth;

/**
 * Capa admin sobre KycService: cola de revisión (listado/detalle) de
 * documentos enviados por jugadores. KycService concentra la lógica de
 * dominio (submitDocument/approve/reject, usada también desde el lado
 * jugador); este servicio solo arma las vistas de consulta que el panel
 * admin necesita y delega aprobar/rechazar al service existente.
 */
final class AdminKycService
{
    private const PAGE_SIZE = 20;

    public function __construct(
        private readonly KycService $kycService,
    ) {}

    /**
     * $filters acepta: status (pending|approved|rejected). Sin filtro,
     * PENDING es el valor por defecto real de trabajo del admin — es la cola
     * de documentos por revisar, no un archivo histórico.
     */
    public function fetchList(array $filters = [], int $page = 1): array
    {
        $status = $filters['status'] ?? KycDocument::STATUS_PENDING;

        $query = KycDocument::with('user:id,first_name,last_name,username,email')
            ->when($status !== 'all', fn ($q) => $q->where('status', $status))
            ->latest('created_at');

        $paginator = $query->paginate(self::PAGE_SIZE, ['*'], 'page', $page);

        return [
            'columns'      => $this->columns(),
            'rows'         => $paginator->getCollection()->map(fn (KycDocument $d) => $this->toRow($d))->values(),
            'total'        => $paginator->total(),
            'per_page'     => $paginator->perPage(),
            'current_page' => $paginator->currentPage(),
            'last_page'    => $paginator->lastPage(),
        ];
    }

    private function columns(): array
    {
        return [
            ['key' => 'player_name',     'label' => 'Jugador',        'sortable' => false],
            ['key' => 'document_type',   'label' => 'Tipo documento', 'sortable' => false],
            ['key' => 'status',          'label' => 'Estado',         'sortable' => false],
            ['key' => 'submitted_at',    'label' => 'Enviado',        'sortable' => false],
        ];
    }

    public function show(KycDocument $document): array
    {
        $document->load('user:id,first_name,last_name,username,email,kyc_level,kyc_status');

        return [
            'id'               => (string) $document->id,
            'document_type'    => $document->document_type,
            'document_number'  => $document->document_number,
            'status'           => $document->status,
            'rejection_reason' => $document->rejection_reason,
            'reviewed_at'      => $document->reviewed_at?->format('d/m/Y H:i:s'),
            'created_at'       => $document->created_at?->format('d/m/Y H:i:s'),
            'has_front'        => (bool) $document->front_path,
            'has_back'         => (bool) $document->back_path,
            'has_selfie'       => (bool) $document->selfie_path,
            'user'             => [
                'id'         => (string) $document->user->id,
                'full_name'  => trim("{$document->user->first_name} {$document->user->last_name}"),
                'username'   => $document->user->username,
                'email'      => $document->user->email,
                'kyc_level'  => $document->user->kyc_level,
                'kyc_status' => $document->user->kyc_status,
            ],
        ];
    }

    public function approve(KycDocument $document): void
    {
        $this->kycService->approve($document, Auth::user());
    }

    public function reject(KycDocument $document, string $reason): void
    {
        $this->kycService->reject($document, Auth::user(), $reason);
    }

    private function toRow(KycDocument $document): array
    {
        return [
            'id'              => (string) $document->id,
            'document_type'   => $document->document_type,
            'status'          => $document->status,
            'player_name'     => trim("{$document->user?->first_name} {$document->user?->last_name}"),
            'player_username' => $document->user?->username,
            'player_email'    => $document->user?->email,
            'submitted_at'    => $document->created_at?->format('d/m/Y H:i:s'),
        ];
    }
}
