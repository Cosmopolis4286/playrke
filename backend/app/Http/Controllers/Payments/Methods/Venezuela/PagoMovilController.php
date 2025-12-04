<?php

namespace App\Http\Controllers\Payments\Methods\Venezuela;

use App\Http\Controllers\Controller;

use App\Models\Payments\Methods\Venezuela\PagoMovil;
use App\Traits\Users\UserTrait;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PagoMovilController extends Controller
{
    use UserTrait;

    public function index()
    {
        $user = $this->user(['id'])->id;
        $pagoMovils = PagoMovil::where("user", $user)->get();

        return view('modules.user.directory.ve.pagomovil.index', compact('pagoMovils'));
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
        $documentType = $request->document_type;
        $documentVal = $request->document_val;
        $bank = $request->bank;
        $phone = $request->phone;

        $existPagoMovil = PagoMovil::existsPagoMovil($documentType, $documentVal, $bank, $phone, Auth::id());

        if ($existPagoMovil) {
            return redirect()->back()->with('message', 'Este PagoMovil ya se encuentra registrado')->with('type', 'warning');
        }

        $pagoMovil = PagoMovil::findOrFail(decrypt($id));

        if (!$pagoMovil) {
            return redirect()->back()->with('message', 'Ha ocurrido un error')->with('type', 'warning');
        }

        DB::beginTransaction();

        try {
            $pagoMovil->update([
                'document_type' => $documentType,
                'document' => $documentVal,
                'phone' => $phone,
                'bank' => $bank,
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
