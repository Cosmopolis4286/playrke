<?php

namespace App\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Banks_BRA;
use App\Models\DocumentType;
use Illuminate\Http\Request;
use App\Models\Pix;
use App\Models\PixKeys;
use App\Traits\UserVal;
use Exception;
use Illuminate\Support\Facades\DB;

class PixController extends Controller
{
    use UserVal;

    public function index()
    {
        $user = $this->user()->id;
        $wallet = $this->Wallet($user);

        if ($wallet->coin != 2) return redirect()->back();

        $pixs = Pix::with('Bank')->where("user", $user)->get();
        $typeChave = PixKeys::all();

        return view('all.directory.br.index', compact('wallet', 'pixs', 'typeChave'));
    }

    private function insertNewBank($infoBank)
    {
        $arr = json_decode($infoBank);

        $existsBank = Banks_BRA::getByCode($arr->code);

        if (!$existsBank) Banks_BRA::create([
            'name' => $arr->name, 'code' => $arr->code,
            'full_name' => $arr->full_name, 'ispb' => $arr->ispb
        ]);
    }

    public function create(Request $request)
    {
        DB::beginTransaction();

        try {
            $this->insertNewBank($request->arr_bank);

            $existPix = Pix::getValuePixKey($request->pix_key_value);

            if (!$existPix) {
                Pix::create([
                    'bank' => $request->bank,
                    'pix_key' => $request->pix_key,
                    'pix_key_value' => $request->pix_key_value,
                    'user' => auth()->id()
                ]);

                DB::commit();

                return redirect()->back()->with([
                    'message' => 'Pix registrado con éxito',
                    'type' => 'success',
                ]);
            }

            return redirect()->back()->with([
                'message' => 'Esta Chave Pix ya se encuentra registrada',
                'type' => 'warning',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();

            dd($e->getMessage());

            return redirect()->back()->with([
                'message' => 'Ocurrió un error al registrar el Pix.' . $e->getMessage() . 'Inténtalo de nuevo.',
                'type' => 'error',
            ]);
        }
    }

    public function update(Request $request, $id)
    {
        $this->insertNewBank($request->arr_bank);

        DB::beginTransaction();

        try {
            $pix = Pix::findOrFail(decrypt($id));

            $pix->update([
                'bank' => $request->bank,
                'pix_key' => $request->pix_key,
                'pix_key_value' => $request->pix_key_value,
                'user' => auth()->id()
            ]);

            DB::commit();

            return redirect()->back()->with([
                'message' => 'Pix actualizado con éxito',
                'type' => 'success',
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with([
                'message' => 'Ha ocurrido un error al actualizar el Pix. Inténtalo de nuevo.',
                'type' => 'error',
            ]);
        }
    }

    public function delete(Request $request)
    {
        Pix::destroy(decrypt($request->id));
        return redirect()->back()->with('message', 'Esta Chave Pix ha sido eliminada')->with('type', 'success');
    }

    public function getInfo($id)
    {
        return Pix::where('id', decrypt($id))->first(['bank', 'pix_key', 'pix_key_value']);
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
