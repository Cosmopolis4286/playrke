<?php

namespace App\Http\Controllers;

use Illuminate\Support\Facades\Storage;
use Illuminate\Http\UploadedFile;
use Intervention\Image\ImageManager;
use Intervention\Image\Drivers\Gd\Driver as GdDriver;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class FileController extends Controller
{
    private static ?ImageManager $imageManager = null;

    /**
     * Obtiene una instancia de ImageManager con el driver configurado
     */
    private static function getImageManager(): ImageManager
    {
        if (!self::$imageManager) {
            $driver = new GdDriver(); // Asegúrate de que GdDriver está bien importado
            self::$imageManager = new ImageManager($driver);
        }
        return self::$imageManager;
    }

    public function showImagePaymentCtns($filename)
    {
        return $this->showImage("private/game/bingo/payments/all-ctns/{$filename}");
    }

    public function showImagePaymentPrizeCtns($filename)
    {
        return $this->showImage("private/game/bingo/payments/prize-ctns/{$filename}");
    }

    private function showImage($filePath)
    {
        abort_unless(Auth::check(), 403, 'Unauthorized action.');
        abort_if(!Storage::disk('local')->exists($filePath), 404, 'File not found.');

        return response()->file(storage_path("app/{$filePath}"));
    }

    private static function resizeImage(string $filePath)
    {
        try {
            if (!Storage::disk('local')->exists($filePath) || strpos(Storage::mimeType($filePath), 'image') === false) {
                return;
            }

            $fullPath = storage_path("app/{$filePath}");
            $image = self::getImageManager()->read($fullPath);

            $image->scale(width: 720)->save($fullPath);
        } catch (\Exception $e) {
            Log::error("Error al redimensionar la imagen: {$e->getMessage()}");
        }
    }

    public static function saveImage(UploadedFile $image, string $directory, string $type = 'PB'): ?string
    {
        try {
            if (!$image->isValid()) {
                Log::error('El archivo de imagen no es válido');
                return null;
            }

            $type = $type === 'PV' ? 'private' : 'public';

            $fileName = time() . '.' . $image->getClientOriginalExtension();

            $directory = "$type/$directory";

            $filePath = "$directory/{$fileName}";

            $image->storeAs($directory, $fileName, 'local');

            self::resizeImage($filePath);

            return $fileName;
        } catch (\Throwable $th) {
            Log::error("Error al guardar la imagen: {$th->getMessage()}");
            return null;
        }
    }
}
