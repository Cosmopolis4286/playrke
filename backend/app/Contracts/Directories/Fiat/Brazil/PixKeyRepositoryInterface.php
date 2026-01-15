<?php

namespace App\Contracts\Directories\Fiat\Brazil;

use App\Contracts\BaseRepositoryInterface;
use App\Models\Directories\Fiat\Brazil\PixKey;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato para el repositorio de claves PIX de Brasil.
 *
 * Define los métodos específicos para gestionar claves PIX,
 * incluyendo validaciones por tipo, usuario, banco y valor de clave.
 */
interface PixKeyRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Obtener claves PIX por usuario.
     *
     * @param string $userId ID del usuario
     * @return Collection Colección de claves PIX del usuario
     */
    public function getByUser(string $userId): Collection;

    /**
     * Obtener claves PIX por banco.
     *
     * @param string $bankId ID del banco brasileño
     * @return Collection Colección de claves del banco especificado
     */
    public function getByBank(string $bankId): Collection;

    /**
     * Obtener claves PIX por tipo.
     *
     * @param string $pixKeyType Tipo de clave PIX (CPF, CNPJ, EMAIL, PHONE, RANDOM)
     * @return Collection Colección de claves del tipo especificado
     */
    public function getByType(string $pixKeyType): Collection;

    /**
     * Obtener clave PIX por valor específico.
     *
     * @param string $pixKeyValue Valor de la clave PIX
     * @return PixKey|null La clave encontrada o null
     */
    public function getByPixKeyValue(string $pixKeyValue): ?PixKey;

    /**
     * Buscar claves PIX por tipo y valor.
     *
     * @param string $pixKeyType Tipo de clave PIX
     * @param string $pixKeyValue Valor de la clave PIX
     * @return Collection Claves que coinciden con tipo y valor
     */
    public function getByTypeAndValue(string $pixKeyType, string $pixKeyValue): Collection;

    /**
     * Verificar si existe una clave PIX específica.
     *
     * @param string $pixKeyValue Valor de la clave PIX
     * @return bool True si existe, false en caso contrario
     */
    public function existsPixKey(string $pixKeyValue): bool;

    /**
     * Verificar si un usuario tiene una clave PIX específica.
     *
     * @param string $userId ID del usuario
     * @param string $pixKeyType Tipo de clave PIX
     * @param string $pixKeyValue Valor de la clave PIX
     * @return bool True si el usuario tiene la clave, false en caso contrario
     */
    public function userHasPixKey(string $userId, string $pixKeyType, string $pixKeyValue): bool;

    /**
     * Obtener claves PIX por usuario y tipo.
     *
     * @param string $userId ID del usuario
     * @param string $pixKeyType Tipo de clave PIX
     * @return Collection Claves del usuario del tipo especificado
     */
    public function getByUserAndType(string $userId, string $pixKeyType): Collection;

    /**
     * Obtener claves PIX por usuario y banco.
     *
     * @param string $userId ID del usuario
     * @param string $bankId ID del banco
     * @return Collection Claves del usuario en el banco especificado
     */
    public function getByUserAndBank(string $userId, string $bankId): Collection;

    /**
     * Crear una nueva clave PIX.
     *
     * @param string $userId ID del usuario
     * @param string $bankId ID del banco brasileño
     * @param string $pixKeyType Tipo de clave PIX
     * @param string $pixKeyValue Valor de la clave PIX
     * @param string|null $name Nombre descriptivo (opcional)
     * @return PixKey La clave PIX creada
     */
    public function createPixKey(
        string $userId,
        string $bankId,
        string $pixKeyType,
        string $pixKeyValue,
        ?string $name = null
    ): PixKey;

    /**
     * Actualizar el nombre de una clave PIX.
     *
     * @param string $pixKeyId ID de la clave PIX
     * @param string $name Nuevo nombre descriptivo
     * @return bool True si se actualizó correctamente
     */
    public function updateName(string $pixKeyId, string $name): bool;

    /**
     * Actualizar el banco de una clave PIX.
     *
     * @param string $pixKeyId ID de la clave PIX
     * @param string $bankId ID del nuevo banco
     * @return bool True si se actualizó correctamente
     */
    public function updateBank(string $pixKeyId, string $bankId): bool;

    /**
     * Actualizar el valor de una clave PIX.
     *
     * @param string $pixKeyId ID de la clave PIX
     * @param string $pixKeyValue Nuevo valor de la clave
     * @return bool True si se actualizó correctamente
     */
    public function updatePixKeyValue(string $pixKeyId, string $pixKeyValue): bool;

    /**
     * Eliminar una clave PIX.
     *
     * @param string $pixKeyId ID de la clave PIX
     * @return bool True si se eliminó correctamente
     */
    public function deletePixKey(string $pixKeyId): bool;

    /**
     * Eliminar todas las claves PIX de un usuario.
     *
     * @param string $userId ID del usuario
     * @return int Número de claves eliminadas
     */
    public function deleteByUser(string $userId): int;

    /**
     * Eliminar claves PIX por banco.
     *
     * @param string $bankId ID del banco
     * @return int Número de claves eliminadas
     */
    public function deleteByBank(string $bankId): int;

    /**
     * Obtener estadísticas generales de claves PIX.
     *
     * @return array<string, mixed> Array con estadísticas
     */
    public function getPixKeyStatistics(): array;

    /**
     * Obtener estadísticas por tipo de clave PIX.
     *
     * @param string $pixKeyType Tipo de clave PIX
     * @return array<string, mixed> Array con estadísticas del tipo
     */
    public function getTypeStatistics(string $pixKeyType): array;

    /**
     * Obtener estadísticas por banco.
     *
     * @param string $bankId ID del banco
     * @return array<string, mixed> Array con estadísticas del banco
     */
    public function getBankStatistics(string $bankId): array;

    /**
     * Obtener estadísticas por usuario.
     *
     * @param string $userId ID del usuario
     * @return array<string, mixed> Array con estadísticas del usuario
     */
    public function getUserStatistics(string $userId): array;

    /**
     * Obtener claves PIX creadas recientemente.
     *
     * @param int $days Número de días hacia atrás (por defecto 30)
     * @return Collection Claves creadas en el período especificado
     */
    public function getRecentPixKey(int $days = 30): Collection;

    /**
     * Buscar claves PIX por valor parcial.
     *
     * @param string $searchTerm Término de búsqueda parcial
     * @return Collection Claves que contienen el término en el valor
     */
    public function searchByPartialValue(string $searchTerm): Collection;

    /**
     * Buscar claves PIX por nombre parcial.
     *
     * @param string $searchTerm Término de búsqueda parcial
     * @return Collection Claves que contienen el término en el nombre
     */
    public function searchByPartialName(string $searchTerm): Collection;

    /**
     * Obtener todos los bancos únicos con claves PIX registradas.
     *
     * @return Collection IDs únicos de bancos con claves PIX
     */
    public function getUniqueBanks(): Collection;

    /**
     * Obtener todos los tipos de clave PIX únicos utilizados.
     *
     * @return Collection Tipos únicos de claves PIX registradas
     */
    public function getUniqueTypes(): Collection;

    /**
     * Verificar si un usuario tiene claves PIX para un banco específico.
     *
     * @param string $userId ID del usuario
     * @param string $bankId ID del banco
     * @return bool True si tiene claves en el banco, false en caso contrario
     */
    public function hasKeysForBank(string $userId, string $bankId): bool;

    /**
     * Obtener la clave PIX principal de un usuario para un banco.
     *
     * @param string $userId ID del usuario
     * @param string $bankId ID del banco
     * @return PixKey|null La clave principal o null
     */
    public function getPrimaryKeyForBank(string $userId, string $bankId): ?PixKey;

    /**
     * Verificar si una clave PIX está siendo usada por otro usuario.
     *
     * @param string $pixKeyValue Valor de la clave PIX
     * @param string|null $excludeUserId ID de usuario a excluir
     * @return bool True si está en uso por otro usuario
     */
    public function isKeyUsedByOtherUser(string $pixKeyValue, ?string $excludeUserId = null): bool;

    /**
     * Obtener usuarios que comparten la misma clave PIX.
     *
     * @param string $pixKeyValue Valor de la clave PIX
     * @return Collection IDs de usuarios que usan la misma clave
     */
    public function getUsersSharingKey(string $pixKeyValue): Collection;

    /**
     * Validar formato de clave PIX según el tipo.
     *
     * @param string $pixKeyType Tipo de clave PIX
     * @param string $pixKeyValue Valor de la clave PIX
     * @return bool True si el formato es válido
     */
    public function validatePixKeyFormat(string $pixKeyType, string $pixKeyValue): bool;

    /**
     * Generar una clave PIX aleatoria válida.
     *
     * @return string Clave PIX aleatoria generada
     */
    public function generateRandomPixKey(): string;

    /**
     * Verificar si un CPF es válido para clave PIX.
     *
     * @param string $cpf CPF a validar
     * @return bool True si es válido
     */
    public function isValidCpf(string $cpf): bool;

    /**
     * Verificar si un CNPJ es válido para clave PIX.
     *
     * @param string $cnpj CNPJ a validar
     * @return bool True si es válido
     */
    public function isValidCnpj(string $cnpj): bool;
}
