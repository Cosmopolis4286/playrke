<?php

namespace App\Services\Banks;

use App\Contracts\Banks\BankBrazilRepositoryInterface;
use App\Models\Banks\BankBrazil;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
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
     * Retorna los bancos de Brasil desde la BD.
     * Si la tabla está vacía, primero sincroniza desde la BrasilAPI.
     * Siempre retorna desde la BD local.
     *
     * @return Collection<int, BankBrazil>
     *
     * @throws RuntimeException Si la tabla está vacía y la API no responde.
     */
    public function getOrSync(): Collection
    {
        if ($this->repository->isEmpty()) {
            $this->syncFromApi();
        }

        return $this->repository->getAllOrderedByName(['id', 'name', 'full_name', 'ispb']);
    }

    /**
     * Fuerza re-sincronización completa: vacía la tabla y re-inserta todos los bancos
     * desde la BrasilAPI. Usado por el endpoint HTTP directo para refrescar manualmente.
     * Retorna la lista ordenada como array.
     *
     * @return array<int, array{id: string, name: string, full_name: string, ispb: string}>
     *
     * @throws RuntimeException Si la API no responde o devuelve datos inválidos.
     */
    public function fetchAndSync(): array
    {
        // Limpiar tabla antes de re-sincronizar para garantizar datos frescos y completos
        DB::table('banks_brazil')->truncate();

        $this->syncFromApi();

        return $this->repository
            ->getAllOrderedByName(['id', 'name', 'full_name', 'ispb'])
            ->map(fn(BankBrazil $bank) => [
                'id'        => $bank->id,
                'name'      => $bank->name,
                'full_name' => $bank->full_name,
                'ispb'      => $bank->ispb,
            ])
            ->values()
            ->all();
    }

    /**
     * Llama a la BrasilAPI y persiste en batch solo los bancos nuevos (por ISPB).
     * Usa insertOrIgnore en chunks de 100 para evitar timeouts con los 500 registros.
     * Los IDs Snowflake se pre-generan en PHP antes del insert.
     *
     * @throws RuntimeException
     */
    private function syncFromApi(): void
    {
        $raw = $this->callApi();

        // Normalizar y filtrar registros válidos
        $incoming = [];
        foreach ($raw as $item) {
            $ispb = trim($item['ispb'] ?? '');
            $name = trim($item['name'] ?? '');

            if ($ispb === '' || $name === '') {
                continue;
            }

            $incoming[$ispb] = [
                'name'      => mb_substr($name, 0, 100),
                'full_name' => mb_substr(trim($item['fullName'] ?? $name), 0, 150),
                'ispb'      => $ispb,
            ];
        }

        if (empty($incoming)) {
            return;
        }

        // Traer ISPBs ya existentes en una sola query
        $existingIspbs = DB::table('banks_brazil')
            ->whereIn('ispb', array_keys($incoming))
            ->pluck('ispb')
            ->flip()
            ->all(); // [ispb => true]

        // Filtrar solo los nuevos y pre-generar IDs Snowflake
        $now    = Carbon::now();
        $toInsert = [];

        foreach ($incoming as $ispb => $data) {
            if (isset($existingIspbs[$ispb])) {
                continue;
            }

            $toInsert[] = [
                'id'         => BankBrazil::generateSnowflakeId(),
                'name'       => $data['name'],
                'full_name'  => $data['full_name'],
                'ispb'       => $ispb,
                'created_at' => $now,
                'updated_at' => $now,
            ];
        }

        // Insert en chunks de 100 para evitar límites de SQL
        foreach (array_chunk($toInsert, 100) as $chunk) {
            DB::table('banks_brazil')->insertOrIgnore($chunk);
        }
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
