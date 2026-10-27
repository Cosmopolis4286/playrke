<?php

namespace App\Http\Controllers;

use App\Models\KycDocument;
use App\Services\KycService;
use DomainException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Throwable;

class KycController extends Controller
{
    public function __construct(private readonly KycService $kycService)
    {
    }

    /** POST /kyc/{document}/approve  (admin) */
    public function approve(Request $request, KycDocument $document) : JsonResponse
    {
        try {
            $this->kycService->approve($document, $request->user());

            return response()->json(['success' => true, 'message' => 'Documento aprobado.']);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /** POST /kyc/{document}/reject  (admin) */
    public function reject(Request $request, KycDocument $document) : JsonResponse
    {
        $request->validate(['reason' => ['required', 'string', 'max:500']]);

        try {
            $this->kycService->reject($document, $request->user(), $request->input('reason'));

            return response()->json(['success' => true, 'message' => 'Documento rechazado.']);
        } catch (Throwable $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    /** GET /kyc/status */
    public function status(Request $request) : JsonResponse
    {
        return response()->json([
            'success' => true,
            'data' => $this->kycService->statusForUser($request->user()),
        ]);
    }

    /** POST /kyc/submit — el usuario sube su documento */
    public function submit(Request $request) : JsonResponse
    {
        $request->validate([
            'document_type' => ['required', 'string', 'in:' . implode(',', KycDocument::DOCUMENT_TYPES)],
            'document_number' => ['required', 'string', 'max:50'],
            'front' => ['required', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
            'back' => ['nullable', 'file', 'mimes:jpg,jpeg,png,pdf', 'max:10240'],
            'selfie' => ['nullable', 'file', 'mimes:jpg,jpeg,png', 'max:10240'],
        ]);

        try {
            $doc = $this->kycService->submitDocument(
                user: $request->user(),
                documentType: $request->input('document_type'),
                documentNumber: $request->input('document_number'),
                front: $request->file('front'),
                back: $request->file('back'),
                selfie: $request->file('selfie'),
            );

            return response()->json([
                'success' => true,
                'message' => 'Documentos enviados. Un administrador los revisará en 24-48 horas.',
                'data' => ['document_id' => (string) $doc->id],
            ]);
        } catch (DomainException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (Throwable $e) {
            Log::error('[KYC] Error al subir documento', ['error' => $e->getMessage()]);

            return response()->json(['success' => false, 'message' => 'Error interno. Intenta de nuevo.'], 500);
        }
    }
}
