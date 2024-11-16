<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Banks_VEN;
use App\Models\PagoMovil;
use App\Models\CodTef;
use App\Models\DocumentType;
use App\Traits\UserVal;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PagoMovilController extends Controller
{
    use UserVal;

    public function index()
    {
        $user = $this->user(['id'])->id;
        $wallet = $this->wallet();

        if ($wallet->coin != 2) return redirect()->back();

        $pagoMovils = PagoMovil::where("user", $user)->get();

        return view('modules.user.directory.ve.pagomovil.index', compact('wallet', 'pagoMovils'));
    }

    public function list() // Falta Ajustar
    {
        $user = $this->user(['id']);
        $wallet = $this->wallet();
        $banks = Banks_VEN::All();
        $codTelfVe = CodTef::All();
        $typeIdent = DocumentType::where('country', 234)->where('status', 1)->get();
        $pagoMovil = PagoMovil::where('user', $user)->count();
        dd(444);

        return view(
            'modules.user.pagomovil.create',
            compact('user', 'wallet', 'banks', 'codTelfVe', 'typeIdent', 'pagoMovil')
        );
    }

    public function create(Request $request)
    {
        $documentType = $request->document_type;
        $documentVal = $request->document_val;
        $bank = $request->bank;
        $phone = $request->phone;

        $existPagoMovil = PagoMovil::existsPagoMovil($documentType, $documentVal, $bank, $phone);

        if ($existPagoMovil) {
            return redirect()->back()->with('message', 'Este PagoMovil ya se encuentra registrado')->with('type', 'warning');
        }

        DB::beginTransaction();

        try {
            PagoMovil::create([
                'document_type' => $documentType,
                'document' => $documentVal,
                'phone' => $phone,
                'bank' => $bank,
                'user' => Auth::id()
            ]);

            DB::commit();
            return redirect()->back()->with('message', 'Registro exitoso')->with('type', 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al registrar Pago Movil: ' . $e->getMessage());
        }
    }

    public function update(Request $request, $id)
    {
        $pagoMovil = PagoMovil::findOrFail(decrypt($id));

        if (!$pagoMovil) {
            return redirect()->back()->with('message', 'Ha ocurrido un error')->with('type', 'warning');
        }

        DB::beginTransaction();

        try {
            $pagoMovil->update([
                'document_type' => $request->document_type,
                'document' => $request->document_val,
                'phone' => $request->phone,
                'bank' => $request->bank,
            ]);

            DB::commit();
            return redirect()->back()->with('message', 'PagoMovil actualizado')->with('type', 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al actualizar Pago Movil: ' . $e->getMessage());
        }
    }

    public function delete(Request $request)
    {
        DB::beginTransaction();

        try {
            PagoMovil::destroy(decrypt($request->id));
            DB::commit();
            return redirect()->back()->with('message', 'PagoMovil eliminado')->with('type', 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar Pago Movil: ' . $e->getMessage());
        }
    }

    public function getInfo($id)
    {
        return PagoMovil::where('id', decrypt($id))->first(['document_type', 'document', 'phone', 'bank']);
    }
}
