<?php

namespace App\Services\Directory;

use App\DTOs\Directories\Fiat\Venezuela\PagoMovilDTO;
use App\Exceptions\Domain\Directory\DirectoryException;
use App\Http\Resources\Directories\Fiat\Venezuela\PagoMovilResource;
use App\Models\Directories\DirectoryAudit;
use App\Models\Directories\Fiat\Venezuela\PagoMovil;
use App\Repositories\Directories\Fiat\Venezuela\PagoMovilRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * Servicio para operaciones CRUD del directorio de Pago Móvil.
 *
 * Responsabilidades:
 * - Lógica de negocio para crear, leer, actualizar y eliminar
 * - Validaciones de dominio (ownership, límite de registros, duplicados)
 * - Auditoría de cambios (directory_audits, vía DirectoryAuditLogger)
 * - Manejo de transacciones
 * - Logging de operaciones
 */
final class DirectoryCrudService
{
    public function __construct(
        private readonly PagoMovilRepository $repository,
        private readonly DirectoryAuditLogger $auditLogger,
    ) {
    }

    /**
     * Crea un nuevo registro de pago móvil.
     *
     * @param PagoMovilDTO $dto         DTO con los datos del registro
     * @param string       $userId      ID del usuario propietario (owner efectivo — puede ser distinto del actor si un admin gestiona en su nombre)
     * @param string|null  $actorUserId ID de quien ejecuta la acción (para auditoría — null = mismo que $userId)
     * @param string|null  $actorRole   Rol del actor (para auditoría)
     *
     * @return bool True si se creó correctamente
     *
     * @throws DirectoryException Si ya existe un registro duplicado o se alcanzó el límite por usuario
     */
    public function create(PagoMovilDTO $dto, string $userId, ?string $actorUserId = null, ?string $actorRole = null) : bool
    {
        try {
            DB::beginTransaction();

            // Conteo y duplicado se re-verifican DENTRO de la transacción con
            // lockForUpdate() sobre las filas existentes del usuario — sin esto,
            // dos requests concurrentes (doble click, dos pestañas) podían pasar
            // ambas el chequeo antes de que cualquiera hiciera commit, resultando
            // en más de MAX_RECORDS_PER_USER registros reales (mismo patrón de
            // lockForUpdate() ya establecido en BingoRoomCapacityGuard para
            // prevenir overbooking).
            if ($this->repository->countActiveByUserForUpdate($userId) >= DirectoryLimits::MAX_RECORDS_PER_USER) {
                throw DirectoryException::limitReached(DirectoryLimits::MAX_RECORDS_PER_USER);
            }

            if ($this->repository->existsPagoMovil(
                $dto->documentTypeId,
                $dto->documentNumber,
                $dto->bankId,
                $dto->phone,
            )) {
                throw DirectoryException::duplicate('Ya existe un registro con este documento y banco.');
            }

            $data = array_merge($dto->toArray(), ['user_id' => $userId]);

            $pagoMovil = $this->repository->create($data);

            DB::commit();

            Log::info('Pago móvil creado exitosamente', [
                'id' => $pagoMovil->id,
                'user_id' => $userId,
            ]);

            $this->auditLogger->log(
                recordType: DirectoryAudit::TYPE_PAGO_MOVIL,
                recordId: (string) $pagoMovil->id,
                ownerUserId: $userId,
                actorUserId: $actorUserId ?? $userId,
                event: DirectoryAudit::EVENT_CREATED,
                actorRole: $actorRole,
            );

            return true;
        } catch (DirectoryException $e) {
            // Límite alcanzado / duplicado: error de negocio real, no una falla
            // de la operación — se relanza tal cual (con su propio httpStatus y
            // mensaje) en vez de aplanarlo a operationFailed() genérico.
            DB::rollBack();

            throw $e;
        } catch (Throwable $e) {
            DB::rollBack();

            Log::error('Error al crear pago móvil', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            throw DirectoryException::operationFailed('Error al crear el registro de pago móvil.');
        }
    }

    /**
     * Elimina un registro de pago móvil (soft delete).
     *
     * @param string      $id          ID del registro
     * @param int         $userId      ID del usuario dueño (para validación de propiedad)
     * @param string|null $actorUserId ID de quien ejecuta la acción (para auditoría)
     * @param string|null $actorRole   Rol del actor (para auditoría)
     *
     * @return bool True si se eliminó correctamente
     *
     * @throws DirectoryException Si el registro no existe o no pertenece al usuario
     */
    public function delete(string $id, int $userId, ?string $actorUserId = null, ?string $actorRole = null) : bool
    {
        $existing = $this->repository->find($id);

        if (! $existing || $existing->user_id != $userId) {
            throw DirectoryException::notFound('Registro de pago móvil no encontrado.');
        }

        try {
            DB::beginTransaction();

            $deleted = $this->repository->delete($id);

            DB::commit();

            Log::info('Pago móvil eliminado exitosamente', [
                'id' => $id,
                'user_id' => $userId,
            ]);

            $this->auditLogger->log(
                recordType: DirectoryAudit::TYPE_PAGO_MOVIL,
                recordId: $id,
                ownerUserId: (string) $userId,
                actorUserId: $actorUserId ?? (string) $userId,
                event: DirectoryAudit::EVENT_DELETED,
                actorRole: $actorRole,
            );

            return $deleted;
        } catch (Throwable $e) {
            DB::rollBack();

            Log::error('Error al eliminar pago móvil', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            throw DirectoryException::operationFailed('Error al eliminar el registro de pago móvil.');
        }
    }

    /**
     * Obtiene todos los registros de pago móvil de un usuario.
     *
     * @param string $userId ID del usuario
     *
     * @return array<int, array<string, mixed>> Lista de registros transformados
     */
    public function getAll(string $userId) : array
    {
        $pagoMoviles = $this->repository->getByUser($userId);

        return PagoMovilResource::collection($pagoMoviles)->resolve();
    }

    /**
     * Obtiene un registro específico de pago móvil.
     *
     * @param string $id     ID del registro
     * @param string $userId ID del usuario dueño (para validación de propiedad)
     *
     * @return PagoMovilResource Registro transformado
     *
     * @throws DirectoryException Si no se encuentra el registro o no pertenece al usuario
     */
    public function show(string $id, string $userId) : PagoMovilResource
    {
        $pagoMovil = $this->repository->find($id);

        if (! $pagoMovil || $pagoMovil->user_id != $userId) {
            throw DirectoryException::notFound('Registro de pago móvil no encontrado.');
        }

        return new PagoMovilResource($pagoMovil);
    }

    /**
     * Alternar estado del directorio (ACTIVE / INACTIVE).
     * También sincroniza el estado del usuario asociado.
     *
     * Antes no validaba propiedad — el caller (DirectoryService::toggleStatus)
     * resolvía el PagoMovil directo por findOrFail($id) sin comparar user_id,
     * permitiendo que cualquier usuario desactivara la cuenta bancaria de
     * otro. Mismo criterio que PixKeyCrudService::toggleStatus() (Brasil).
     *
     * @param PagoMovil   $pagoMovil   Registro de directorio a actualizar
     * @param string      $userId      ID del usuario dueño (para validación de propiedad)
     * @param string|null $actorUserId ID de quien ejecuta la acción (para auditoría)
     * @param string|null $actorRole   Rol del actor (para auditoría)
     *
     * @return bool True si el cambio fue exitoso
     *
     * @throws DirectoryException Si el registro no pertenece al usuario o está eliminado
     */
    public function toggleStatus(PagoMovil $pagoMovil, string $userId, ?string $actorUserId = null, ?string $actorRole = null) : bool
    {
        if ((string) $pagoMovil->user_id !== $userId) {
            throw DirectoryException::notFound('Registro de pago móvil no encontrado.');
        }

        if ($pagoMovil->status === 'DELETED') {
            throw DirectoryException::invalidStatusChange();
        }

        $previousStatus = $pagoMovil->status;
        $result = $this->repository->toggleStatus($pagoMovil);

        if ($result) {
            $this->auditLogger->log(
                recordType: DirectoryAudit::TYPE_PAGO_MOVIL,
                recordId: (string) $pagoMovil->id,
                ownerUserId: $userId,
                actorUserId: $actorUserId ?? $userId,
                event: DirectoryAudit::EVENT_STATUS_CHANGED,
                actorRole: $actorRole,
                changes: ['status' => [$previousStatus, $pagoMovil->fresh()?->status]],
            );
        }

        return $result;
    }

    /**
     * Actualiza un registro de pago móvil existente.
     *
     * Antes NO recibía $userId ni validaba propiedad — cualquier usuario
     * autenticado podía hacer PATCH /directory/{id-de-otro-usuario}/update y
     * sobrescribir el teléfono/banco/cédula de Pago Móvil ajeno (destino de
     * sus retiros). Mismo criterio que show()/delete() (que sí validan) y
     * que PixKeyCrudService::update() (Brasil), que ya lo hacía bien.
     *
     * @param PagoMovilDTO $dto         DTO con los datos actualizados
     * @param string       $userId      ID del usuario dueño (para validación de propiedad)
     * @param string|null  $actorUserId ID de quien ejecuta la acción (para auditoría)
     * @param string|null  $actorRole   Rol del actor (para auditoría)
     *
     * @return bool True si se actualizó correctamente
     *
     * @throws DirectoryException Si el registro no existe, no pertenece al usuario, o hay duplicados
     */
    public function update(PagoMovilDTO $dto, string $userId, ?string $actorUserId = null, ?string $actorRole = null) : bool
    {
        if (! $dto->id) {
            throw DirectoryException::missingId();
        }

        $existing = $this->repository->find($dto->id);

        if (! $existing || (string) $existing->user_id !== $userId) {
            throw DirectoryException::notFound('Registro de pago móvil no encontrado.');
        }

        // Validación de duplicados (excluyendo el usuario actual)
        if ($this->repository->existsPagoMovil(
            $dto->documentTypeId,
            $dto->documentNumber,
            $dto->bankId,
            $dto->phone,
            $existing->user_id,
        )) {
            throw DirectoryException::duplicate('Ya existe otro registro con este documento y banco.');
        }

        $changes = $this->diffChanges($existing, $dto->toArray());

        try {
            DB::beginTransaction();

            $this->repository->update($dto->id, $dto->toArray());

            DB::commit();

            Log::info('Pago móvil actualizado exitosamente', [
                'id' => $dto->id,
            ]);

            $this->auditLogger->log(
                recordType: DirectoryAudit::TYPE_PAGO_MOVIL,
                recordId: (string) $dto->id,
                ownerUserId: $userId,
                actorUserId: $actorUserId ?? $userId,
                event: DirectoryAudit::EVENT_UPDATED,
                actorRole: $actorRole,
                changes: $changes,
            );

            return true;
        } catch (Throwable $e) {
            DB::rollBack();

            Log::error('Error al actualizar pago móvil', [
                'id' => $dto->id,
                'error' => $e->getMessage(),
            ]);

            throw DirectoryException::operationFailed('Error al actualizar el registro de pago móvil.');
        }
    }

    /**
     * Compara los campos relevantes del registro existente contra el nuevo
     * payload — solo lo que realmente cambió queda en la auditoría, no el
     * registro completo en cada edición.
     *
     * @return array<string, array{0: mixed, 1: mixed}>
     */
    private function diffChanges(PagoMovil $existing, array $newData) : array
    {
        $tracked = ['holder_name', 'document_type_id', 'document_number', 'phone', 'bank_id'];
        $changes = [];

        foreach ($tracked as $field) {
            if (! array_key_exists($field, $newData)) {
                continue;
            }

            $old = (string) $existing->{$field};
            $new = (string) $newData[$field];

            if ($old !== $new) {
                $changes[$field] = [$old, $new];
            }
        }

        return $changes;
    }
}
