<?php

namespace App\Http\Controllers\Platform;

use App\Domains\Games\Bingo\Services\Verification\BingoCardVerificationService;
use App\Http\Controllers\Controller;
use App\Http\Requests\Platform\BingoCard\VerifyCardRequest;
use Illuminate\Http\JsonResponse;

/**
 * Verificación pública de un cartón de Bingo (sin sesión) — equivalente a
 * consultar un ticket físico: quien tenga el serial + últimos 4 dígitos del
 * teléfono del comprador puede confirmar números, estado y premio.
 */
class BingoCardVerifyController extends Controller
{
    public function __construct(
        private readonly BingoCardVerificationService $service,
    ) {
    }

    public function verify(VerifyCardRequest $request) : JsonResponse
    {
        return $this->handleServiceJson(function() use ($request) {
            $data = $request->validated();

            return [
                'card' => $this->service->verify($data['serial_number'], $data['phone_last4']),
            ];
        });
    }
}
