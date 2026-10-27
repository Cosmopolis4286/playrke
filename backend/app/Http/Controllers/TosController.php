<?php

namespace App\Http\Controllers;

use App\Services\TosService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TosController extends Controller
{
    public function __construct(private readonly TosService $tosService)
    {
    }

    /**
     * POST /tos/accept
     * El usuario acepta la versión activa. Registra versión + timestamp + IP.
     */
    public function accept(Request $request) : JsonResponse
    {
        $user = $request->user();

        if (! $user) {
            return response()->json(['success' => false, 'message' => 'No autenticado.'], 401);
        }

        $this->tosService->accept($user, $request->ip() ?? '');

        return response()->json([
            'success' => true,
            'message' => 'Términos aceptados.',
            'accepted_version' => $user->fresh()->tos_accepted_version,
        ]);
    }

    /**
     * GET /tos/current
     * Devuelve la versión activa y si el usuario autenticado la ha aceptado.
     */
    public function current(Request $request) : JsonResponse
    {
        $version = $this->tosService->currentVersion();
        $user = $request->user();

        return response()->json([
            'success' => true,
            'version' => $version?->version,
            'title' => $version?->title,
            'summary' => $version?->summary,
            'effective_at' => $version?->effective_at,
            'needs_acceptance' => $user ? $this->tosService->needsAcceptance($user) : true,
            'accepted_version' => $user?->tos_accepted_version,
            'accepted_at' => $user?->tos_accepted_at,
        ]);
    }
}
