<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class FileController extends Controller
{
    public function showImagePaymentCtns($filename)
    {
        $filePath = "bingo/payments/all-ctns/{$filename}";
        return $this->showImage($filePath, $filename);
    }

    public function showImagePaymentPrizeCtns($filename)
    {
        $filePath = "bingo/payments/prize-ctns/{$filename}";
        return $this->showImage($filePath, $filename);
    }

    private function showImage($filePath, $filename)
    {
        // Verificar si el usuario está autenticado
        abort_unless(Auth::check(), 403, 'Unauthorized action.');

        // Ruta dentro de "storage/app/private"
        $storagePath = "private/{$filePath}";

        // Verificar si el archivo existe en "storage/app/private"
        abort_if(!Storage::disk('local')->exists($storagePath), 404, 'File not found.');

        // Obtener la ruta absoluta del archivo
        $path = storage_path("app/{$storagePath}");

        // Retornar el archivo con su tipo MIME correcto
        return response()->file($path);
    }

    public static function resizeImage($filePath, $fileName)
    {
        try {
            $fullPath = $filePath . '/' . $fileName;

            if (Storage::disk('private')->exists($fullPath) && strpos(Storage::mimeType($fullPath), 'image') !== false) {
                Image::make(storage_path("app/private/{$fullPath}"))
                    ->resize(720, null, function ($constraint) {
                        $constraint->aspectRatio();
                    })->save();
            }
        } catch (\Exception $e) {
            Log::error('Error al redimensionar la imagen: ' . $e->getMessage());
        }
    }

    public static function saveImage(UploadedFile $image, $rout)
    {
        try {
            if (!$image || !$image->isValid()) {
                Log::error('El archivo de imagen no es válido');
                return false;
            }

            $newFileName = time() . '.' . $image->getClientOriginalExtension();
            $image->storeAs('private/' . $rout . '/', $newFileName);
            self::resizeImage($rout, $newFileName);

            return $newFileName;
        } catch (\Throwable $th) {
            Log::error('Error al guardar la imagen: ' . $th->getMessage());
            return null;
        }
    }
}
