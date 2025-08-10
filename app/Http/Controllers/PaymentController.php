<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

use App\Models\BingoCtnsPayment;

use Illuminate\Support\Facades\Auth;

class PaymentController extends Controller
{
    public function showPaymentProof($fileName)
    {
        $user = Auth::user();

        // Verificar que el usuario tiene permiso para ver este archivo
        $payment = BingoCtnsPayment::where('payment_proof', $fileName)->where('sender', $user->id)->first();

        if (!$payment) {
            abort(403, 'No tienes permiso para ver este archivo.');
        }

        $path = storage_path('app/payments/' . $fileName);

        if (!file_exists($path)) {
            abort(404, 'El archivo no existe.');
        }

        return response()->download($path);
    }
}
