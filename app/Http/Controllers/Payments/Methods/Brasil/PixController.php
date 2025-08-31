<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;

use App\Models\BanksBrasil;
use App\Models\Pix;

use App\Traits\Users\UserValTrait;

use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PixController extends Controller
{
    use UserValTrait;

    public function index()
    {
        $pixs = Pix::with('Bank')->where("user", Auth::id())->get();
        $typeChave = Pix::getPixKeys();

        return view('all.directory.br.index', compact('pixs', 'typeChave'));
    }

    private function insertNewBank($infoBank)
    {
        $bank = json_decode($infoBank);
        $ISPB = $bank->ispb;

        $bankID = BanksBrasil::getValueByISPB($ISPB, 'id');

        if ($bankID) return $bankID;

        return BanksBrasil::create([
            'name' => $bank->name,
            'full_name' => $bank->full_name,
            'ispb' => $ISPB
        ])->id;
    }

    public function create(Request $request)
    {
        DB::beginTransaction();

        try {
            $bankID = $this->insertNewBank($request->arr_bank);

            $pixKey = $request->pix_key;
            $pixKeyVal = $request->pix_key_value;

            if (Pix::existsPixKey($pixKey, $pixKeyVal)) {
                return redirect()->back()->with([
                    'message' => 'Esta Chave Pix ya se encuentra registrada',
                    'type' => 'warning',
                ]);
            }

            Pix::create([
                'name' => $request->name,
                'bank' => $bankID,
                'pix_key' => $request->pix_key,
                'pix_key_value' => $request->pix_key_value,
                'user' => Auth::id()
            ]);

            DB::commit();

            return redirect()->back()->with([
                'message' => 'Pix registrado con éxito',
                'type' => 'success',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Ha ocurrido un error al registar el Pix ' . $e->getMessage());
            return redirect()->back()->with([
                'message' => 'Ocurrió un error',
                'type' => 'warning',
            ]);
        }
    }

    public function update(Request $request, $id)
    {
        DB::beginTransaction();

        try {

            $bankID = $this->insertNewBank($request->arr_bank);

            $pixKey = $request->pix_key;
            $pixKeyVal = $request->pix_key_value;

            if (Pix::existsPixKey($pixKey, $pixKeyVal, Auth::id())) {
                return redirect()->back()->with([
                    'message' => 'Esta Chave Pix ya se encuentra registrada',
                    'type' => 'warning',
                ]);
            }

            $pix = Pix::findOrFail(decrypt($id));

            $pix->update([
                'name' => $request->name,
                'bank' => $bankID,
                'pix_key' => $pixKey,
                'pix_key_value' => $pixKeyVal
            ]);

            DB::commit();

            return redirect()->back()->with([
                'message' => 'Pix actualizado con éxito',
                'type' => 'success',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Ha ocurrido un error al actualizar el Pix ' . $e->getMessage());
            return redirect()->back()->with([
                'message' => 'Ha ocurrido un error al actualizar el Pix. Inténtalo de nuevo.',
                'type' => 'warning',
            ]);
        }
    }

    public function delete(Request $request)
    {
        DB::beginTransaction();

        try {
            Pix::destroy(decrypt($request->id));
            DB::commit();
            return redirect()->back()->with('message', 'Esta Chave Pix ha sido eliminada')->with('type', 'success');
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error al eliminar Pix: ' . $e->getMessage());
        }
    }

    public function getInfo($id)
    {
        return Pix::getById(decrypt($id), ['name', 'bank', 'pix_key', 'pix_key_value']);
    }

    public function getBanks()
    {
        // Inicializa cURL
        $ch = curl_init();

        try {
            // Establece la URL de la API
            curl_setopt($ch, CURLOPT_URL, "https://brasilapi.com.br/api/banks/v1");

            // Indica que la respuesta de la solicitud debe ser devuelta como string
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);

            // Ejecuta la solicitud cURL
            $response = curl_exec($ch);

            // Verifica si hubo un error en la solicitud
            if (curl_errno($ch)) {
                throw new Exception('Error: ' . curl_error($ch));
            }

            // Decodifica la respuesta JSON en un array asociativo
            $data = json_decode($response, true);

            // Verifica si la decodificación fue exitosa
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new Exception('Error al decodificar el JSON: ' . json_last_error_msg());
            }

            return $data;
        } catch (Exception $e) {
            // Manejo del error (puedes registrar el error o realizar otra acción)
            error_log($e->getMessage());
            return null;
        } finally {
            // Cierra el recurso cURL
            curl_close($ch);
        }
    }
}
