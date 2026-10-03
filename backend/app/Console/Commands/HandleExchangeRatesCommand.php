<?php

namespace App\Console\Commands;

use App\Models\ExchangeRate;
use App\Models\UserPreference;
use App\Services\Commands\CommandService;
use App\Services\ExchangeRate\BcvExchangeRateService;
use App\Services\ExchangeRate\ExchangeRateApiService;
use App\Services\ExchangeRate\ExchangeRateUpdaterService;
use Illuminate\Console\Command;
use Throwable;

class HandleExchangeRatesCommand extends Command
{
    protected $signature = 'handle:exchange-rates';

    protected $description = 'Actualiza las tasas de cambio USD para los usuarios con auto-actualización habilitada';

    public function __construct(
        private readonly ExchangeRateApiService $apiService,
        private readonly BcvExchangeRateService $bcvApiService,
        private readonly ExchangeRateUpdaterService $updaterService,
    ) {
        parent::__construct();
    }

    public function handle(): void
    {
        $userIds = UserPreference::query()
            ->where('category', UserPreference::CATEGORY_EXCHANGE_RATE)
            ->where('key', 'auto_update_enabled')
            ->where('value', 'true')
            ->whereNotNull('user_id')
            ->pluck('user_id')
            ->map(fn($id) => (int) $id)
            ->all();

        $bcvUserIds = UserPreference::query()
            ->where('category', UserPreference::CATEGORY_EXCHANGE_RATE)
            ->where('key', 'bcv_auto_update_enabled')
            ->where('value', 'true')
            ->whereNotNull('user_id')
            ->pluck('user_id')
            ->map(fn($id) => (int) $id)
            ->all();

        // Usuarios que solo activaron BCV (VES) sin la actualización general (Binance).
        $bcvOnlyUserIds = array_values(array_diff($bcvUserIds, $userIds));

        // Auto-update por moneda individual (ej. solo COP, ver
        // ExchangeRateService::enableCurrencyAutoUpdate) — claves dinámicas
        // 'auto_update_enabled:{ISO}', agrupadas por usuario → lista de ISO
        // codes. Solo aporta algo para usuarios que NO tienen ya el switch
        // global activo (si lo tienen, esa moneda ya se actualiza con el
        // resto en $rates).
        $currencyPrefs = UserPreference::query()
            ->where('category', UserPreference::CATEGORY_EXCHANGE_RATE)
            ->where('key', 'like', 'auto_update_enabled:%')
            ->where('value', 'true')
            ->whereNotNull('user_id')
            ->get(['user_id', 'key']);

        $isoByUser = [];
        foreach ($currencyPrefs as $pref) {
            $userId = (int) $pref->user_id;
            if (in_array($userId, $userIds, true)) {
                continue;
            }
            $iso = substr($pref->key, strlen('auto_update_enabled:'));
            $isoByUser[$userId][] = $iso;
        }

        if (empty($userIds) && empty($bcvOnlyUserIds) && empty($isoByUser)) {
            $this->info('Ningún usuario tiene la actualización automática habilitada.');
            return;
        }

        $needsRates = !empty($userIds) || !empty($isoByUser);
        $rates = $needsRates ? $this->apiService->fetch() : null;
        $ratesFetchFailed = $needsRates && empty($rates);

        if ($ratesFetchFailed) {
            $this->error('No se pudieron obtener las tasas desde la API externa.');
        }

        $bcvRate = !empty($bcvUserIds) ? $this->bcvApiService->fetch() : null;
        $bcvFetchFailed = !empty($bcvUserIds) && $bcvRate === null;

        if ($bcvFetchFailed) {
            $this->error('No se pudo obtener la tasa oficial del BCV.');
        }

        if ($ratesFetchFailed && empty($bcvOnlyUserIds)) {
            $this->logExecutionSafely('No se pudieron obtener las tasas desde la API externa.', 'ERROR');
            return;
        }

        if (empty($rates) && $bcvFetchFailed) {
            $this->logExecutionSafely('No se pudieron obtener las tasas desde la API externa ni la tasa del BCV.', 'ERROR');
            return;
        }

        $updatedCount = 0;
        $failedUserIds = [];

        // Usuarios con actualización general (Binance), sustituyendo VES por BCV si aplica.
        if (!empty($rates)) {
            foreach ($userIds as $userId) {
                $userRates = $rates;

                if ($bcvRate !== null && in_array($userId, $bcvUserIds, true)) {
                    $userRates['VES'] = $bcvRate;
                }

                $this->updateOneUser($userId, $userRates, $updatedCount, $failedUserIds);
            }
        }

        // Usuarios que solo activaron BCV: solo se actualiza VES.
        if ($bcvRate !== null) {
            foreach ($bcvOnlyUserIds as $userId) {
                $this->updateOneUser($userId, ['VES' => $bcvRate], $updatedCount, $failedUserIds);
            }
        }

        // Usuarios con auto-update de monedas individuales (sin el switch
        // global): solo las monedas que activaron explícitamente.
        if (!empty($rates)) {
            foreach ($isoByUser as $userId => $isoCodes) {
                $userRates = array_intersect_key($rates, array_flip($isoCodes));

                if (empty($userRates)) {
                    continue;
                }

                $this->updateOneUser($userId, $userRates, $updatedCount, $failedUserIds);
            }
        }

        // ERROR solo si NINGÚN usuario se actualizó y hubo al menos un
        // intento — un fallo aislado (usuario eliminado entre el momento en
        // que se listó su preferencia y la ejecución del loop, error
        // transitorio de BD) ya no tira todo el ciclo a ERROR si el resto
        // de usuarios sí se actualizó correctamente. Antes, updateRates()
        // corría dentro de un único try/catch envolviendo ambos loops: una
        // excepción de cualquier usuario detenía el resto del batch sin
        // dejar rastro de cuántos sí se habían procesado antes del fallo.
        //
        // command_logs.status es un enum(['success','error']) — solo esos
        // 2 valores caben en la columna (ver migración
        // create_command_logs_table), así que un resultado "parcial" (algún
        // usuario falló pero no todos) se reporta como SUCCESS con el
        // detalle de fallos en el propio mensaje `$output`, no como un
        // tercer status que MySQL rechazaría.
        $totalAttempted = count($userIds) + count($bcvOnlyUserIds) + count($isoByUser);
        $hasPartialFailures = $totalAttempted > 0 && !empty($failedUserIds) && $updatedCount > 0;
        $status = ($totalAttempted > 0 && $updatedCount === 0) ? 'ERROR' : 'SUCCESS';

        $output = "Tasas actualizadas para {$updatedCount} de {$totalAttempted} usuario(s).";
        if (!empty($failedUserIds)) {
            $output .= ' Fallaron: #' . implode(', #', $failedUserIds) . '.';
        }

        $this->logExecutionSafely($output, $status);

        if ($status === 'ERROR') {
            $this->error($output);
        } elseif ($hasPartialFailures) {
            $this->warn($output);
        } else {
            $this->info($output);
        }
    }

    /**
     * Actualiza un usuario individual dentro del loop, con su propio
     * try/catch — updateRates() ya envuelve la lógica de dominio en su
     * propio try/catch (retorna bool), pero la resolución de rol
     * (RoleUserService::getActiveUserRolePrefix()) corre ANTES de ese
     * try/catch interno y sí puede propagar una excepción no capturada
     * (ej. el usuario fue eliminado entre listar la preferencia y ejecutar
     * este loop) — sin este wrapper, ese caso detenía el resto del batch.
     *
     * @param-out int $updatedCount
     * @param-out int[] $failedUserIds
     */
    private function updateOneUser(int $userId, array $userRates, int &$updatedCount, array &$failedUserIds): void
    {
        try {
            // source: SOURCE_SYSTEM — este comando ES el cronjob automático
            // (ver ExchangeRate::SOURCE_SYSTEM), lo que distingue estas
            // filas de las creadas manualmente o vía el botón "Consultar"
            // (SOURCE_USER, el default) en el widget/histórico.
            $ok = $this->updaterService->updateRates($userId, $userRates, ExchangeRate::SOURCE_SYSTEM);

            if ($ok) {
                $this->info("Tasas actualizadas para usuario #{$userId}.");
                $updatedCount++;
            } else {
                $failedUserIds[] = $userId;
                $this->warn("No se pudo actualizar la tasa del usuario #{$userId}.");
            }
        } catch (Throwable $e) {
            $failedUserIds[] = $userId;
            $this->error("Error actualizando usuario #{$userId}: {$e->getMessage()}");
        }
    }

    /**
     * Envuelto en try/catch: si logCommandExecution() fallara por cualquier
     * motivo, el error visible al operador debe ser el original de la
     * tarea, no uno de logging que lo enmascare. Extraído a método propio
     * porque este comando registra el resultado desde 3 puntos distintos
     * (2 salidas tempranas + el flujo normal).
     */
    private function logExecutionSafely(string $output, string $status): void
    {
        try {
            app(CommandService::class)->logCommandExecution($this->signature, $output, $status);
        } catch (Throwable $logEx) {
            $this->error('No se pudo registrar la ejecución del comando: ' . $logEx->getMessage());
        }
    }
}
