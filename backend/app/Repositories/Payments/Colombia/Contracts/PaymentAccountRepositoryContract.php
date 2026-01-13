<?php

namespace App\Repositories\Payments\Colombia\Contracts;

use App\Repositories\Base\Contracts\BaseRepositoryContract;
use App\Models\Payments\Colombia\PaymentAccount;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato para el repositorio de métodos de pago de Colombia.
 *
 * Define los métodos específicos para gestionar cuentas de pago colombianas,
 * incluyendo validaciones por gateway, usuario y número de cuenta.
 */
interface PaymentAccountRepositoryContract extends BaseRepositoryContract
{
    /**
     * Obtener cuentas de pago por usuario.
     *
     * @param string $userId ID del usuario
     * @return Collection Colección de cuentas del usuario
     */
    public function getByUser(string $userId): Collection;

    /**
     * Obtener cuentas activas por usuario.
     *
     * @param string $userId ID del usuario
     * @return Collection Colección de cuentas activas del usuario
     */
    public function getActiveByUser(string $userId): Collection;

    /**
     * Obtener cuentas por gateway de pago.
     *
     * @param string $gateway Gateway de pago (BANCOLOMBIA, DAVIPLATA, NEQUI)
     * @return Collection Colección de cuentas del gateway especificado
     */
    public function getByGateway(string $gateway): Collection;

    /**
     * Obtener cuentas por número de documento.
     *
     * @param string $documentNumber Número de documento
     * @return Collection Colección de cuentas con el documento especificado
     */
    public function getByDocumentNumber(string $documentNumber): Collection;

    /**
     * Obtener cuentas por número de cuenta.
     *
     * @param string $accountNumber Número de cuenta
     * @return Collection Colección de cuentas con el número especificado
     */
    public function getByAccountNumber(string $accountNumber): Collection;

    /**
     * Obtener todas las cuentas activas.
     *
     * @return Collection Colección de todas las cuentas activas
     */
    public function getActiveAccounts(): Collection;

    /**
     * Obtener todas las cuentas inactivas.
     *
     * @return Collection Colección de todas las cuentas inactivas
     */
    public function getInactiveAccounts(): Collection;

    /**
     * Buscar cuenta específica por todos los parámetros.
     *
     * @param string $gateway Gateway de pago
     * @param string $userName Nombre del titular
     * @param string $accountNumber Número de cuenta
     * @param string $documentNumber Número de documento
     * @return PaymentAccount|null La cuenta encontrada o null
     */
    public function findByAllParameters(
        string $gateway,
        string $userName,
        string $accountNumber,
        string $documentNumber
    ): ?PaymentAccount;

    /**
     * Verificar si existe una cuenta con los parámetros dados.
     *
     * @param string $gateway Gateway de pago
     * @param string $userName Nombre del titular
     * @param string $accountNumber Número de cuenta
     * @param string $documentNumber Número de documento
     * @param string|null $excludeUserId ID de usuario a excluir (para updates)
     * @return bool True si existe, false en caso contrario
     */
    public function existsAccount(
        string $gateway,
        string $userName,
        string $accountNumber,
        string $documentNumber,
        ?string $excludeUserId = null
    ): bool;

    /**
     * Crear una nueva cuenta de pago.
     *
     * @param string $gateway Gateway de pago
     * @param string $userName Nombre del titular
     * @param string $accountNumber Número de cuenta
     * @param string $documentNumber Número de documento
     * @param string $userId ID del usuario
     * @param bool $isActive Estado inicial (por defecto true)
     * @return PaymentAccount La cuenta creada
     */
    public function createAccount(
        string $gateway,
        string $userName,
        string $accountNumber,
        string $documentNumber,
        string $userId,
        bool $isActive = true
    ): PaymentAccount;

    /**
     * Actualizar el estado de una cuenta.
     *
     * @param string $accountId ID de la cuenta
     * @param bool $isActive Nuevo estado
     * @return bool True si se actualizó correctamente
     */
    public function updateStatus(string $accountId, bool $isActive): bool;

    /**
     * Activar una cuenta.
     *
     * @param string $accountId ID de la cuenta
     * @return bool True si se activó correctamente
     */
    public function activateAccount(string $accountId): bool;

    /**
     * Desactivar una cuenta.
     *
     * @param string $accountId ID de la cuenta
     * @return bool True si se desactivó correctamente
     */
    public function deactivateAccount(string $accountId): bool;

    /**
     * Actualizar información de la cuenta.
     *
     * @param string $accountId ID de la cuenta
     * @param array $data Datos a actualizar
     * @return bool True si se actualizó correctamente
     */
    public function updateAccountInfo(string $accountId, array $data): bool;

    /**
     * Eliminar una cuenta.
     *
     * @param string $accountId ID de la cuenta
     * @return bool True si se eliminó correctamente
     */
    public function deleteAccount(string $accountId): bool;

    /**
     * Eliminar todas las cuentas de un usuario.
     *
     * @param string $userId ID del usuario
     * @return int Número de cuentas eliminadas
     */
    public function deleteByUser(string $userId): int;

    /**
     * Actualización masiva de estado para múltiples cuentas.
     *
     * @param array $accountIds IDs de las cuentas
     * @param bool $isActive Nuevo estado
     * @return int Número de registros actualizados
     */
    public function bulkUpdateStatus(array $accountIds, bool $isActive): int;

    /**
     * Obtener estadísticas generales de cuentas de pago.
     *
     * @return array<string, mixed> Array con estadísticas
     */
    public function getAccountStatistics(): array;

    /**
     * Obtener estadísticas por gateway.
     *
     * @param string $gateway Gateway de pago
     * @return array<string, mixed> Array con estadísticas del gateway
     */
    public function getGatewayStatistics(string $gateway): array;

    /**
     * Obtener estadísticas por usuario.
     *
     * @param string $userId ID del usuario
     * @return array<string, mixed> Array con estadísticas del usuario
     */
    public function getUserStatistics(string $userId): array;

    /**
     * Obtener cuentas creadas recientemente.
     *
     * @param int $days Número de días hacia atrás (por defecto 30)
     * @return Collection Cuentas creadas en el período especificado
     */
    public function getRecentAccounts(int $days = 30): Collection;

    /**
     * Buscar cuentas por nombre de titular (búsqueda parcial).
     *
     * @param string $searchTerm Término de búsqueda parcial
     * @return Collection Cuentas que contienen el término en el nombre
     */
    public function searchByUserName(string $searchTerm): Collection;

    /**
     * Obtener todos los gateways únicos utilizados.
     *
     * @return Collection Gateways únicos con cuentas registradas
     */
    public function getUniqueGateways(): Collection;

    /**
     * Verificar si un usuario tiene una cuenta activa para un gateway específico.
     *
     * @param string $userId ID del usuario
     * @param string $gateway Gateway de pago
     * @return bool True si tiene cuenta activa, false en caso contrario
     */
    public function hasActiveAccountForGateway(string $userId, string $gateway): bool;

    /**
     * Obtener la cuenta principal de un usuario para un gateway.
     *
     * @param string $userId ID del usuario
     * @param string $gateway Gateway de pago
     * @return PaymentAccount|null La cuenta principal o null
     */
    public function getPrimaryAccountForGateway(string $userId, string $gateway): ?PaymentAccount;

    /**
     * Verificar si un número de cuenta está siendo usado por otro usuario.
     *
     * @param string $accountNumber Número de cuenta
     * @param string $gateway Gateway de pago
     * @param string|null $excludeUserId ID de usuario a excluir
     * @return bool True si está en uso por otro usuario
     */
    public function isAccountUsedByOtherUser(
        string $accountNumber,
        string $gateway,
        ?string $excludeUserId = null
    ): bool;
}
