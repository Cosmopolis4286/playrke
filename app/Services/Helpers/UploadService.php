<?php

namespace App\Services\Helpers;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use Illuminate\Http\Exceptions\HttpResponseException;

class UploadService
{
    /**
     * Guarda una imagen en disco público dentro de una carpeta específica.
     *
     * @param UploadedFile $image
     * @param string $folder
     * @param int|string $creatorID
     * @return string
     * @throws HttpResponseException
     */
    public function saveImage(UploadedFile $image, string $folder, $creatorID): string
    {
        try {
            if (!$image->isValid()) {
                throw new \Exception('El archivo no es válido o está corrupto.');
            }

            $fileName = Carbon::now()->timestamp . '.' . $image->getClientOriginalExtension();
            $creatorFolder = "$folder/$creatorID";

            Storage::disk('public')->deleteDirectory($creatorFolder);
            $image->storeAs($creatorFolder, $fileName, 'public');

            return $fileName;
        } catch (\Exception $e) {
            Log::error("Error al guardar la imagen: " . $e->getMessage());

            throw new HttpResponseException(response()->json([
                'message' => 'Error al guardar la imagen. Inténtalo de nuevo.',
                'error' => $e->getMessage()
            ], 500));
        }
    }
}
