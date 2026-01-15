<?php

namespace App\Contracts\Directories\Fiat\Venezuela;

use App\Contracts\BaseRepositoryInterface;
use App\Models\Directories\Fiat\Venezuela\PagoMovil;
use Illuminate\Database\Eloquent\Collection;

/**
 * Contrato para el repositorio de Pago Móvil Venezuela.
 *
 * Define los métodos específicos para gestionar registros de pago móvil,
 * incluyendo validaciones, consultas por usuario, documento y banco.
 */
interface PagoMovilRepositoryInterface extends BaseRepositoryInterface
{
    /**
     * Obtener registros de pago móvil por usuario.
     *
     * @param string $userId ID del usuario
     * @return Collection Colección de registros de pago móvil del usuario
     */
    public function getByUser(string $userId): Collection;

    /**
     * Obtener registros de pago móvil por banco.
     *
     * @param string $bankId ID del banco
     * @return Collection Colección de registros del banco especificado
     */
    public function getByBank(string $bankId): Collection;

    /**
     * Obtener registros de pago móvil por tipo de documento.
     *
     * @param string $documentTypeId ID del tipo de documento
     * @return Collection Colección de registros con el tipo de documento
     */
    public function getByDocumentType(string $documentTypeId): Collection;

    /**
     * Obtener registros por número de documento.
     *
     * @param string $document Número de documento
     * @return Collection Colección de registros con el documento
     */
    public function getByDocument(string $document): Collection;

    /**
     * Obtener registros por número de teléfono.
     *
     * @param string $phone Número de teléfono
     * @return Collection Colección de registros con el teléfono
     */
    public function getByPhone(string $phone): Collection;

    /**
     * Buscar un registro específico por todos los parámetros.
     *
     * @param string $documentTypeId ID del tipo de documento
     * @param string $document Número de documento
     * @param string $bankId ID del banco
     * @param string $phone Número de teléfono
     * @return PagoMovil|null El registro encontrado o null
     */
    public function findByAllParameters(
        string $documentTypeId,
        string $document,
        string $bankId,
        string $phone
    ): ?PagoMovil;

    /**
     * Obtener el usuario asociado a un pago móvil específico.
     *
     * @param string $documentTypeId ID del tipo de documento
     * @param string $document Número de documento
     * @param string $bankId ID del banco
     * @param string $phone Número de teléfono
     * @return string|null ID del usuario o null si no existe
     */
    public function getUserByPagoMovil(
        string $documentTypeId,
        string $document,
        string $bankId,
        string $phone
    ): ?string;

    /**
     * Verificar si existe un pago móvil con los parámetros dados.
     *
     * @param string $documentTypeId ID del tipo de documento
     * @param string $document Número de documento
     * @param string $bankId ID del banco
     * @param string $phone Número de teléfono
     * @param string|null $excludeUserId ID de usuario a excluir (para updates)
     * @return bool True si existe, false en caso contrario
     */
    public function existsPagoMovil(
        string $documentTypeId,
        string $document,
        string $bankId,
        string $phone,
        ?string $excludeUserId = null
    ): bool;

    /**
     * Crear un nuevo registro de pago móvil.
     *
     * @param string $documentTypeId ID del tipo de documento
     * @param string $document Número de documento
     * @param string $phone Número de teléfono
     * @param string $bankId ID del banco
     * @param string $userId ID del usuario
     * @return PagoMovil El registro creado
     */
    public function createPagoMovil(
        string $documentTypeId,
        string $document,
        string $phone,
        string $bankId,
        string $userId
    ): PagoMovil;

    /**
     * Actualizar el teléfono de un registro.
     *
     * @param string $pagoMovilId ID del registro
     * @param string $phone Nuevo número de teléfono
     * @return bool True si se actualizó correctamente
     */
    public function updatePhone(string $pagoMovilId, string $phone): bool;

    /**
     * Actualizar el documento de un registro.
     *
     * @param string $pagoMovilId ID del registro
     * @param string $documentTypeId ID del tipo de documento
     * @param string $document Número de documento
     * @return bool True si se actualizó correctamente
     */
    public function updateDocument(
        string $pagoMovilId,
        string $documentTypeId,
        string $document
    ): bool;

    /**
     * Actualizar el banco de un registro.
     *
     * @param string $pagoMovilId ID del registro
     * @param string $bankId ID del nuevo banco
     * @return bool True si se actualizó correctamente
     */
    public function updateBank(string $pagoMovilId, string $bankId): bool;

    /**
     * Eliminar un registro de pago móvil.
     *
     * @param string $pagoMovilId ID del registro
     * @return bool True si se eliminó correctamente
     */
    public function deletePagoMovil(string $pagoMovilId): bool;

    /**
     * Eliminar todos los registros de un usuario.
     *
     * @param string $userId ID del usuario
     * @return int Número de registros eliminados
     */
    public function deleteByUser(string $userId): int;

    /**
     * Obtener estadísticas de pago móvil.
     *
     * @return array<string, mixed> Array con estadísticas
     */
    public function getPagoMovilStatistics(): array;

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
     * Obtener registros creados recientemente.
     *
     * @param int $days Número de días hacia atrás (por defecto 30)
     * @return Collection Registros creados en el período especificado
     */
    public function getRecentRegistrations(int $days = 30): Collection;

    /**
     * Buscar registros por número de documento parcial.
     *
     * @param string $partialDocument Parte del número de documento
     * @return Collection Registros que contienen el documento parcial
     */
    public function searchByPartialDocument(string $partialDocument): Collection;

    /**
     * Buscar registros por número de teléfono parcial.
     *
     * @param string $partialPhone Parte del número de teléfono
     * @return Collection Registros que contienen el teléfono parcial
     */
    public function searchByPartialPhone(string $partialPhone): Collection;

    /**
     * Obtener todos los bancos únicos utilizados.
     *
     * @return Collection IDs únicos de bancos con pago móvil registrado
     */
    public function getUniqueBanks(): Collection;

    /**
     * Obtener todos los tipos de documento únicos utilizados.
     *
     * @return Collection IDs únicos de tipos de documento utilizados
     */
    public function getUniqueDocumentTypes(): Collection;

    /**
     * Verificar si un teléfono está siendo usado por otro usuario.
     *
     * @param string $phone Número de teléfono
     * @param string|null $excludeUserId ID de usuario a excluir
     * @return bool True si el teléfono está en uso por otro usuario
     */
    public function isPhoneUsedByOtherUser(string $phone, ?string $excludeUserId = null): bool;

    /**
     * Obtener usuarios que comparten el mismo número de teléfono.
     *
     * @param string $phone Número de teléfono
     * @return Collection IDs de usuarios que usan el mismo teléfono
     */
    public function getUsersSharingPhone(string $phone): Collection;
}
