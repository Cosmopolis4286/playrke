<?php

namespace App\Repositories\Games\Shared\Historical\Contracts;

use App\Repositories\Base\Contracts\BaseRepositoryContract;
use App\Models\Shared\Historical\PlayHistory;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

/**
 * Contrato para el repositorio de historial de juegos.
 *
 * Define los métodos específicos para gestionar el historial de partidas,
 * incluyendo consultas por usuario, juego, fecha y estadísticas de juego.
 */
interface PlayHistoryRepositoryContract extends BaseRepositoryContract
{
    /**
     * Obtener historial de juegos por usuario.
     *
     * @param string $userId ID del usuario
     * @param int|null $limit Límite de registros (opcional)
     * @return Collection Colección de historial del usuario
     */
    public function getByUser(string $userId, ?int $limit = null): Collection;

    /**
     * Obtener historial por juego.
     *
     * @param string $gameId ID del juego
     * @param int|null $limit Límite de registros (opcional)
     * @return Collection Colección de historial del juego
     */
    public function getByGame(string $gameId, ?int $limit = null): Collection;

    /**
     * Obtener historial por sala.
     *
     * @param int $roomId ID de la sala
     * @param int|null $limit Límite de registros (opcional)
     * @return Collection Colección de historial de la sala
     */
    public function getByRoom(int $roomId, ?int $limit = null): Collection;

    /**
     * Obtener historial por moneda.
     *
     * @param string $currencyId ID de la moneda
     * @param int|null $limit Límite de registros (opcional)
     * @return Collection Colección de historial con la moneda
     */
    public function getByCurrency(string $currencyId, ?int $limit = null): Collection;

    /**
     * Obtener historial en un rango de fechas.
     *
     * @param Carbon $startDate Fecha de inicio
     * @param Carbon $endDate Fecha de fin
     * @param int|null $limit Límite de registros (opcional)
     * @return Collection Historial en el rango de fechas
     */
    public function getByDateRange(Carbon $startDate, Carbon $endDate, ?int $limit = null): Collection;

    /**
     * Obtener historial por usuario y juego.
     *
     * @param string $userId ID del usuario
     * @param string $gameId ID del juego
     * @param int|null $limit Límite de registros (opcional)
     * @return Collection Historial del usuario en el juego específico
     */
    public function getByUserAndGame(string $userId, string $gameId, ?int $limit = null): Collection;

    /**
     * Obtener historial por usuario en un rango de fechas.
     *
     * @param string $userId ID del usuario
     * @param Carbon $startDate Fecha de inicio
     * @param Carbon $endDate Fecha de fin
     * @return Collection Historial del usuario en el período
     */
    public function getByUserAndDateRange(string $userId, Carbon $startDate, Carbon $endDate): Collection;

    /**
     * Obtener partidas con ganancias positivas.
     *
     * @param string|null $userId ID del usuario (opcional)
     * @param int|null $limit Límite de registros (opcional)
     * @return Collection Partidas con ganancias
     */
    public function getWinningPlays(?string $userId = null, ?int $limit = null): Collection;

    /**
     * Obtener partidas con ganancias por encima de un monto.
     *
     * @param float $minGain Ganancia mínima
     * @param string|null $userId ID del usuario (opcional)
     * @param int|null $limit Límite de registros (opcional)
     * @return Collection Partidas con ganancias altas
     */
    public function getHighGainPlays(float $minGain, ?string $userId = null, ?int $limit = null): Collection;

    /**
     * Crear un nuevo registro de historial.
     *
     * @param string $userId ID del usuario
     * @param string $gameId ID del juego
     * @param string $currencyId ID de la moneda
     * @param string $action Acción realizada
     * @param float $gain Ganancia obtenida
     * @param int|null $roomId ID de la sala (opcional)
     * @param Carbon|null $playedAt Fecha y hora del juego (opcional, por defecto ahora)
     * @return PlayHistory El registro creado
     */
    public function createPlayRecord(
        string $userId,
        string $gameId,
        string $currencyId,
        string $action,
        float $gain = 0.0,
        ?int $roomId = null,
        ?Carbon $playedAt = null
    ): PlayHistory;

    /**
     * Actualizar la ganancia de un registro.
     *
     * @param string $playHistoryId ID del registro
     * @param float $gain Nueva ganancia
     * @return bool True si se actualizó correctamente
     */
    public function updateGain(string $playHistoryId, float $gain): bool;

    /**
     * Actualizar la acción de un registro.
     *
     * @param string $playHistoryId ID del registro
     * @param string $action Nueva acción
     * @return bool True si se actualizó correctamente
     */
    public function updateAction(string $playHistoryId, string $action): bool;

    /**
     * Eliminar registros de historial antiguos.
     *
     * @param int $daysOld Días de antigüedad para eliminar
     * @return int Número de registros eliminados
     */
    public function deleteOldRecords(int $daysOld): int;

    /**
     * Eliminar todo el historial de un usuario.
     *
     * @param string $userId ID del usuario
     * @return int Número de registros eliminados
     */
    public function deleteByUser(string $userId): int;

    /**
     * Obtener estadísticas generales de juego.
     *
     * @return array<string, mixed> Array con estadísticas
     */
    public function getGeneralStatistics(): array;

    /**
     * Obtener estadísticas por usuario.
     *
     * @param string $userId ID del usuario
     * @return array<string, mixed> Array con estadísticas del usuario
     */
    public function getUserStatistics(string $userId): array;

    /**
     * Obtener estadísticas por juego.
     *
     * @param string $gameId ID del juego
     * @return array<string, mixed> Array con estadísticas del juego
     */
    public function getGameStatistics(string $gameId): array;

    /**
     * Obtener estadísticas por sala.
     *
     * @param int $roomId ID de la sala
     * @return array<string, mixed> Array con estadísticas de la sala
     */
    public function getRoomStatistics(int $roomId): array;

    /**
     * Obtener estadísticas por período de tiempo.
     *
     * @param Carbon $startDate Fecha de inicio
     * @param Carbon $endDate Fecha de fin
     * @return array<string, mixed> Array con estadísticas del período
     */
    public function getPeriodStatistics(Carbon $startDate, Carbon $endDate): array;

    /**
     * Obtener los mejores jugadores por ganancias.
     *
     * @param int $limit Límite de jugadores (por defecto 10)
     * @param Carbon|null $startDate Fecha de inicio (opcional)
     * @param Carbon|null $endDate Fecha de fin (opcional)
     * @return Collection Top jugadores con sus ganancias
     */
    public function getTopPlayersByGain(int $limit = 10, ?Carbon $startDate = null, ?Carbon $endDate = null): Collection;

    /**
     * Obtener los juegos más populares.
     *
     * @param int $limit Límite de juegos (por defecto 10)
     * @param Carbon|null $startDate Fecha de inicio (opcional)
     * @param Carbon|null $endDate Fecha de fin (opcional)
     * @return Collection Juegos más jugados
     */
    public function getPopularGames(int $limit = 10, ?Carbon $startDate = null, ?Carbon $endDate = null): Collection;

    /**
     * Obtener actividad de juego por horas del día.
     *
     * @param Carbon|null $startDate Fecha de inicio (opcional)
     * @param Carbon|null $endDate Fecha de fin (opcional)
     * @return array<int, int> Array con actividad por hora [hora => cantidad]
     */
    public function getActivityByHour(?Carbon $startDate = null, ?Carbon $endDate = null): array;

    /**
     * Obtener actividad de juego por días de la semana.
     *
     * @param Carbon|null $startDate Fecha de inicio (opcional)
     * @param Carbon|null $endDate Fecha de fin (opcional)
     * @return array<int, int> Array con actividad por día [día => cantidad]
     */
    public function getActivityByDayOfWeek(?Carbon $startDate = null, ?Carbon $endDate = null): array;

    /**
     * Obtener historial reciente de actividad.
     *
     * @param int $hours Número de horas hacia atrás (por defecto 24)
     * @param int|null $limit Límite de registros (opcional)
     * @return Collection Actividad reciente
     */
    public function getRecentActivity(int $hours = 24, ?int $limit = null): Collection;

    /**
     * Buscar en historial por acción.
     *
     * @param string $action Acción a buscar
     * @param string|null $userId ID del usuario (opcional)
     * @param int|null $limit Límite de registros (opcional)
     * @return Collection Registros que coinciden con la acción
     */
    public function searchByAction(string $action, ?string $userId = null, ?int $limit = null): Collection;

    /**
     * Obtener resumen diario de actividad.
     *
     * @param Carbon $date Fecha específica
     * @return array<string, mixed> Resumen del día
     */
    public function getDailySummary(Carbon $date): array;

    /**
     * Obtener resumen mensual de actividad.
     *
     * @param int $year Año
     * @param int $month Mes
     * @return array<string, mixed> Resumen del mes
     */
    public function getMonthlySummary(int $year, int $month): array;

    /**
     * Verificar si un usuario ha jugado un juego específico.
     *
     * @param string $userId ID del usuario
     * @param string $gameId ID del juego
     * @return bool True si ha jugado el juego
     */
    public function hasUserPlayedGame(string $userId, string $gameId): bool;

    /**
     * Obtener la última partida de un usuario.
     *
     * @param string $userId ID del usuario
     * @return PlayHistory|null La última partida o null
     */
    public function getLastPlayByUser(string $userId): ?PlayHistory;

    /**
     * Obtener el total de ganancias de un usuario.
     *
     * @param string $userId ID del usuario
     * @param Carbon|null $startDate Fecha de inicio (opcional)
     * @param Carbon|null $endDate Fecha de fin (opcional)
     * @return float Total de ganancias
     */
    public function getTotalGainsByUser(string $userId, ?Carbon $startDate = null, ?Carbon $endDate = null): float;

    /**
     * Contar partidas por usuario.
     *
     * @param string $userId ID del usuario
     * @param Carbon|null $startDate Fecha de inicio (opcional)
     * @param Carbon|null $endDate Fecha de fin (opcional)
     * @return int Número total de partidas
     */
    public function countPlaysByUser(string $userId, ?Carbon $startDate = null, ?Carbon $endDate = null): int;
}