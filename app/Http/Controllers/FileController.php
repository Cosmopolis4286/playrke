<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Log;

class FileController extends Controller
{
    public function showImagePaymentCtns($filename)
    {
        return $this->showImage('payments/all-ctns/' . $filename);
    }

    public function showImagePaymentPrizeCtns($filename)
    {
        return $this->showImage('payments/prize-ctns/' . $filename);
    }

    private function showImage($filePath)
    {
        // Verificar si el usuario está autenticado
        if (auth()->check()) {
            // Verificar si el archivo existe en el disco 'public/bingo'
            if (Storage::disk('public')->exists('bingo/' . $filePath)) {
                // Obtener el archivo desde la carpeta storage
                $path = storage_path('app/public/bingo/' . $filePath);
                $file = file_get_contents($path);
                $mimeType = mime_content_type($path);

                // Devolver el archivo con el tipo de contenido adecuado
                return response($file, 200)->header('Content-Type', $mimeType);
            } else {
                // Devolver un error si el archivo no existe
                abort(404, 'File not found.');
            }
        } else {
            // Devolver un error si el usuario no está autenticado
            abort(403, 'Unauthorized action.');
        }
    }

    public static function resizeImage($filePath, $fileName)
    {
        try {
            $fullPath = $filePath . '/' . $fileName;

            if (Storage::disk('public')->exists($fullPath) && strpos(Storage::mimeType($fullPath), 'image') !== false) {
                Image::make(storage_path('app/public/' . $fullPath))
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
            $image->storeAs('public/' . $rout . '/', $newFileName);
            self::resizeImage($rout, $newFileName);

            return $newFileName;
        } catch (\Throwable $th) {
            Log::error('Error al guardar la imagen: ' . $th->getMessage());
            return null;
        }
    }
}
