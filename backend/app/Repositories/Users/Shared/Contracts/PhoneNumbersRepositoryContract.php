<?php

namespace App\Repositories\Users\Shared\Contracts;

use App\Contracts\BaseRepositoryInterface;
use App\Models\Users\Shared\PhoneNumbers;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato para el repositorio de números de teléfono.
 *
 * Define los métodos específicos para gestionar números de teléfono de usuarios,
 * incluyendo validaciones por país, usuario y estado.
 */
interface PhoneNumbersRepositoryContract extends BaseRepositoryInterface
{
    /**
     * Obtener números de teléfono por usuario.
     *
     * @param string $userId ID del usuario
     * @return Collection Colección de números del usuario
     */
    public function getByUser(string $userId): Collection;

    /**
     * Obtener números activos por usuario.
     *
     * @param string $userId ID del usuario
     * @return Collection Colección de números activos del usuario
     */
    public function getActiveByUser(string $userId): Collection;

    /**
     * Obtener números por país.
     *
     * @param string $countryId ID del país
     * @return Collection Colección de números del país especificado
     */
    public function getByCountry(string $countryId): Collection;

    /**
     * Obtener números activos por país.
     *
     * @param string $countryId ID del país
     * @return Collection Colección de números activos del país
     */
    public function getActiveByCountry(string $countryId): Collection;

    /**
     * Obtener número específico por valor.
     *
     * @param string $phoneNumber Número de teléfono
     * @return PhoneNumbers|null El número encontrado o null
     */
    public function getByPhoneNumber(string $phoneNumber): ?PhoneNumbers;

    /**
     * Verificar si existe un número de teléfono.
     *
     * @param string $phoneNumber Número de teléfono
     * @return bool True si existe, false en caso contrario
     */
    public function existsPhoneNumber(string $phoneNumber): bool;

    /**
     * Verificar si un número está activo.
     *
     * @param string $phoneNumber Número de teléfono
     * @return bool True si está activo, false en caso contrario
     */
    public function isPhoneNumberActive(string $phoneNumber): bool;

    /**
     * Obtener todos los números activos.
     *
     * @return Collection Colección de todos los números activos
     */
    public function getActiveNumbers(): Collection;

    /**
     * Obtener todos los números inactivos.
     *
     * @return Collection Colección de todos los números inactivos
     */
    public function getInactiveNumbers(): Collection;

    /**
     * Buscar número específico por usuario y país.
     *
     * @param string $userId ID del usuario
     * @param string $countryId ID del país
     * @return Collection Números del usuario en el país especificado
     */
    public function getByUserAndCountry(string $userId, string $countryId): Collection;

    /**
     * Crear un nuevo número de teléfono.
     *
     * @param string $userId ID del usuario
     * @param string $countryId ID del país
     * @param string $phoneNumber Número de teléfono
     * @param bool $isActive Estado inicial (por defecto true)
     * @return PhoneNumbers El número creado
     */
    public function createPhoneNumber(
        string $userId,
        string $countryId,
        string $phoneNumber,
        bool $isActive = true
    ): PhoneNumbers;

    /**
     * Actualizar el estado de un número.
     *
     * @param string $phoneNumberId ID del número
     * @param bool $isActive Nuevo estado
     * @return bool True si se actualizó correctamente
     */
    public function updateStatus(string $phoneNumberId, bool $isActive): bool;

    /**
     * Activar un número de teléfono.
     *
     * @param string $phoneNumberId ID del número
     * @return bool True si se activó correctamente
     */
    public function activatePhoneNumber(string $phoneNumberId): bool;

    /**
     * Desactivar un número de teléfono.
     *
     * @param string $phoneNumberId ID del número
     * @return bool True si se desactivó correctamente
     */
    public function deactivatePhoneNumber(string $phoneNumberId): bool;

    /**
     * Actualizar el número de teléfono.
     *
     * @param string $phoneNumberId ID del registro
     * @param string $newPhoneNumber Nuevo número de teléfono
     * @return bool True si se actualizó correctamente
     */
    public function updatePhoneNumber(string $phoneNumberId, string $newPhoneNumber): bool;

    /**
     * Actualizar el país de un número.
     *
     * @param string $phoneNumberId ID del número
     * @param string $countryId ID del nuevo país
     * @return bool True si se actualizó correctamente
     */
    public function updateCountry(string $phoneNumberId, string $countryId): bool;

    /**
     * Eliminar un número de teléfono.
     *
     * @param string $phoneNumberId ID del número
     * @return bool True si se eliminó correctamente
     */
    public function deletePhoneNumber(string $phoneNumberId): bool;

    /**
     * Eliminar todos los números de un usuario.
     *
     * @param string $userId ID del usuario
     * @return int Número de registros eliminados
     */
    public function deleteByUser(string $userId): int;

    /**
     * Actualización masiva de estado para múltiples números.
     *
     * @param array $phoneNumberIds IDs de los números
     * @param bool $isActive Nuevo estado
     * @return int Número de registros actualizados
     */
    public function bulkUpdateStatus(array $phoneNumberIds, bool $isActive): int;

    /**
     * Obtener estadísticas generales de números de teléfono.
     *
     * @return array<string, mixed> Array con estadísticas
     */
    public function getPhoneNumberStatistics(): array;

    /**
     * Obtener estadísticas por país.
     *
     * @param string $countryId ID del país
     * @return array<string, mixed> Array con estadísticas del país
     */
    public function getCountryStatistics(string $countryId): array;

    /**
     * Obtener estadísticas por usuario.
     *
     * @param string $userId ID del usuario
     * @return array<string, mixed> Array con estadísticas del usuario
     */
    public function getUserStatistics(string $userId): array;

    /**
     * Obtener números creados recientemente.
     *
     * @param int $days Número de días hacia atrás (por defecto 30)
     * @return Collection Números creados en el período especificado
     */
    public function getRecentPhoneNumbers(int $days = 30): Collection;

    /**
     * Buscar números por número parcial.
     *
     * @param string $partialNumber Parte del número de teléfono
     * @return Collection Números que contienen el término parcial
     */
    public function searchByPartialNumber(string $partialNumber): Collection;

    /**
     * Obtener todos los países únicos con números registrados.
     *
     * @return Collection IDs únicos de países con números de teléfono
     */
    public function getUniqueCountries(): Collection;

    /**
     * Verificar si un usuario tiene un número activo para un país específico.
     *
     * @param string $userId ID del usuario
     * @param string $countryId ID del país
     * @return bool True si tiene número activo, false en caso contrario
     */
    public function hasActiveNumberForCountry(string $userId, string $countryId): bool;

    /**
     * Obtener el número principal de un usuario para un país.
     *
     * @param string $userId ID del usuario
     * @param string $countryId ID del país
     * @return PhoneNumbers|null El número principal o null
     */
    public function getPrimaryNumberForCountry(string $userId, string $countryId): ?PhoneNumbers;

    /**
     * Verificar si un número está siendo usado por otro usuario.
     *
     * @param string $phoneNumber Número de teléfono
     * @param string|null $excludeUserId ID de usuario a excluir
     * @return bool True si está en uso por otro usuario
     */
    public function isNumberUsedByOtherUser(string $phoneNumber, ?string $excludeUserId = null): bool;

    /**
     * Obtener usuarios que comparten el mismo número de teléfono.
     *
     * @param string $phoneNumber Número de teléfono
     * @return Collection IDs de usuarios que usan el mismo número
     */
    public function getUsersSharingNumber(string $phoneNumber): Collection;

    /**
     * Validar formato de número de teléfono según el país.
     *
     * @param string $phoneNumber Número de teléfono
     * @param string $countryId ID del país
     * @return bool True si el formato es válido
     */
    public function validatePhoneNumberFormat(string $phoneNumber, string $countryId): bool;
}
