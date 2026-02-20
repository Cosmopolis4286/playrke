<?php

namespace App\Services\Banks;

use App\Contracts\Banks\BankBrazilRepositoryInterface;
use App\Models\Banks\BankBrazil;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * Servicio para obtener bancos de Brasil desde la API externa (BrasilAPI).
 *
 * Responsabilidades:
 * - Consultar la API https://brasilapi.com.br/api/banks/v1
 * - Normalizar y filtrar la respuesta
 * - Persistir bancos nuevos en la BD (firstOrCreate por ISPB)
 * - Devolver la colección lista para el frontend
 *
 * NO contiene lógica de dominio ni acceso HTTP directo al controller.
 */
final class BrazilBankApiService
{
    private const API_URL = 'https://brasilapi.com.br/api/banks/v1';
    private const TIMEOUT_SECONDS = 10;

    public function __construct(
        private readonly BankBrazilRepositoryInterface $repository
    ) {}

    /**
     * Obtiene todos los bancos de Brasil desde la API externa,
     * persiste los nuevos en la BD y retorna la lista ordenada.
     *
     * @return array<int, array{id: string, name: string, full_name: string, ispb: string}>
     *
     * @throws RuntimeException Si la API no responde o devuelve datos inválidos.
     */
    public function fetchAndSync(): array
    {
        $raw = $this->callApi();

        $synced = [];

        foreach ($raw as $item) {
            $ispb = trim($item['ispb'] ?? '');
            $name = trim($item['name'] ?? '');

            // Ignorar registros sin ISPB o sin nombre útil
            if ($ispb === '' || $name === '') {
                continue;
            }

            $bank = $this->repository->firstOrCreateByISPB($ispb, [
                'name'      => $name,
                'full_name' => trim($item['fullName'] ?? $name),
                'ispb'      => $ispb,
            ]);

            $synced[] = [
                'id'        => $bank->id,
                'name'      => $bank->name,
                'full_name' => $bank->full_name,
                'ispb'      => $bank->ispb,
            ];
        }

        // Ordenar por nombre para el frontend
        usort($synced, fn($a, $b) => strcmp($a['name'], $b['name']));

        return $synced;
    }

    /**
     * Llama a la BrasilAPI y retorna el array crudo.
     *
     * @return array<int, array<string, mixed>>
     *
     * @throws RuntimeException
     */
    private function callApi(): array
    {
        try {
            $response = Http::timeout(self::TIMEOUT_SECONDS)
                ->acceptJson()
                ->get(self::API_URL);

            if ($response->failed()) {
                throw new RuntimeException(
                    'BrasilAPI respondió con status ' . $response->status()
                );
            }

            $data = $response->json();

            if (!is_array($data)) {
                throw new RuntimeException('BrasilAPI devolvió una respuesta inesperada.');
            }

            return $data;
        } catch (RuntimeException $e) {
            throw $e;
        } catch (\Throwable $e) {
            Log::error('BrazilBankApiService: error al contactar BrasilAPI', [
                'error' => $e->getMessage(),
            ]);

            throw new RuntimeException('No se pudo contactar la API de bancos de Brasil.');
        }
    }
}
