<?php

namespace App\Http\Controllers\Directories\Fiat\Colombia;

use App\Http\Controllers\Controller;
use App\Models\Directories\Fiat\Colombia\PaymentAccount;

use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class PaymentAccountController extends Controller
{
    public function create(Request $request)
    {
        $gateway = $request->gateway;
        $userName = $request->user_name;
        $accNumber = $request->acc_number;
        $documentNumber = $request->document_number;

        if (PaymentAccount::existsRegister($gateway, $userName, $accNumber, $documentNumber)) {
            return redirect()->back()->with([
                'message' => 'Esta Chave Pix ya se encuentra registrada',
                'type' => 'warning'
            ]);
        }

        DB::beginTransaction();

        try {
            PaymentAccount::create([
                'gateway' => $gateway,
                'user_name' => $userName,
                'acc_number' => $accNumber,
                'document_number' => $documentNumber,
                'user' => Auth::id()
            ]);

            DB::commit();

            return redirect()->back()->with([
                'message' => 'Registro efectuado con éxito',
                'type' => 'success',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with([
                'message' => 'Ocurrió un error',
                'type' => 'warning',
            ]);
        }
    }

    public function update(Request $request, $id)
    {
        $gateway = $request->gateway;
        $userName = $request->user_name;
        $accNumber = $request->acc_number;
        $documentNumber = $request->document_number;

        if (PaymentAccount::existsRegister($gateway, $userName, $accNumber, $documentNumber, Auth::id())) {
            return redirect()->back()->with([
                'message' => 'Esta Chave Pix ya se encuentra registrada',
                'type' => 'warning'
            ]);
        }

        DB::beginTransaction();

        try {

            $register = PaymentAccount::findOrFail(decrypt($id));

            $register->update([
                'gateway' => $gateway,
                'user_name' => $userName,
                'acc_number' => $accNumber,
                'document_number' => $documentNumber
            ]);

            DB::commit();

            return redirect()->back()->with([
                'message' => 'Registro actualizado con éxito',
                'type' => 'success',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with([
                'message' => 'Ha ocurrido un error al actualizar el Registro',
                'type' => 'warning',
            ]);
        }
    }

    public function delete(Request $request)
    {
        DB::beginTransaction();

        try {
            PaymentAccount::destroy(decrypt($request->id));
            DB::commit();
            return redirect()->back()->with('message', 'El registro ha sido eliminado')->with('type', 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar Registro: ' . $e->getMessage());
        }
    }

    public function getInfo($id)
    {
        return PaymentAccount::getById(decrypt($id), ['gateway', 'acc_number', 'user_name', 'document_number']);
    }
}
