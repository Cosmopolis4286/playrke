<?php

namespace App\Services\Directory;

use App\DTOs\Directories\Fiat\Brazil\PixKeyDTO;
use App\Exceptions\Domain\Directory\DirectoryException;
use App\Models\Directories\DirectoryAudit;
use App\Repositories\Directories\Fiat\Brazil\PixKeyRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Throwable;

final class PixKeyCrudService
{
    public function __construct(
        private readonly PixKeyRepository $repository,
        private readonly DirectoryAuditLogger $auditLogger,
    ) {
    }

    /**
     * Crea una nueva clave PIX para el usuario.
     *
     * @param string      $userId      ID del usuario propietario (owner efectivo)
     * @param string|null $actorUserId ID de quien ejecuta la acción (para auditoría — null = mismo que $userId)
     * @param string|null $actorRole   Rol del actor (para auditoría)
     *
     * @throws DirectoryException Si ya existe una clave PIX duplicada o se alcanzó el límite por usuario
     */
    public function create(PixKeyDTO $dto, string $userId, ?string $actorUserId = null, ?string $actorRole = null) : bool
    {
        try {
            DB::beginTransaction();

            // Conteo y duplicado se re-verifican DENTRO de la transacción con
            // lockForUpdate() — mismo patrón que DirectoryCrudService::create()
            // (VE), sin esto dos requests concurrentes podían pasar ambas el
            // chequeo de límite antes de que cualquiera hiciera commit.
            if ($this->repository->countActiveByUserForUpdate($userId) >= DirectoryLimits::MAX_RECORDS_PER_USER) {
                throw DirectoryException::limitReached(DirectoryLimits::MAX_RECORDS_PER_USER);
            }

            if ($this->repository->userHasPixKey($userId, $dto->pixKeyType, $dto->pixKeyValue)) {
                throw DirectoryException::duplicate('Ya tienes registrada esta clave PIX.');
            }

            $pixKey = $this->repository->create(array_merge($dto->toArray(), ['user_id' => $userId]));

            DB::commit();

            Log::info('PixKey creada', ['user_id' => $userId, 'type' => $dto->pixKeyType]);

            $this->auditLogger->log(
                recordType: DirectoryAudit::TYPE_PIX_KEY,
                recordId: (string) $pixKey->id,
                ownerUserId: $userId,
                actorUserId: $actorUserId ?? $userId,
                event: DirectoryAudit::EVENT_CREATED,
                actorRole: $actorRole,
            );

            return true;
        } catch (DirectoryException $e) {
            // Límite alcanzado / duplicado: error de negocio real, no una falla
            // de la operación — se relanza tal cual, mismo criterio que
            // DirectoryCrudService::create().
            DB::rollBack();

            throw $e;
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Error al crear PixKey', ['user_id' => $userId, 'error' => $e->getMessage()]);

            throw DirectoryException::operationFailed('Error al guardar la clave PIX.');
        }
    }

    /**
     * Elimina una clave PIX.
     *
     * @param string      $userId      ID del usuario dueño (para validación de propiedad)
     * @param string|null $actorUserId ID de quien ejecuta la acción (para auditoría)
     * @param string|null $actorRole   Rol del actor (para auditoría)
     *
     * @throws DirectoryException Si el registro no existe o no pertenece al usuario
     */
    public function delete(string $id, string $userId, ?string $actorUserId = null, ?string $actorRole = null) : bool
    {
        $existing = $this->repository->find($id);

        if (! $existing || (string) $existing->user_id !== $userId) {
            throw DirectoryException::notFound('Clave PIX no encontrada.');
        }

        try {
            DB::beginTransaction();

            $result = $this->repository->deletePixKey($id);

            DB::commit();

            Log::info('PixKey eliminada', ['id' => $id, 'user_id' => $userId]);

            $this->auditLogger->log(
                recordType: DirectoryAudit::TYPE_PIX_KEY,
                recordId: $id,
                ownerUserId: $userId,
                actorUserId: $actorUserId ?? $userId,
                event: DirectoryAudit::EVENT_DELETED,
                actorRole: $actorRole,
            );

            return $result;
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Error al eliminar PixKey', ['id' => $id, 'error' => $e->getMessage()]);

            throw DirectoryException::operationFailed('Error al eliminar la clave PIX.');
        }
    }

    /**
     * Obtiene una clave PIX verificando que pertenece al usuario.
     *
     * @throws DirectoryException Si no existe o no pertenece al usuario
     */
    public function show(string $id, string $userId) : array
    {
        $pixKey = $this->repository->find($id);

        if (! $pixKey || (string) $pixKey->user_id !== $userId) {
            throw DirectoryException::notFound('Clave PIX no encontrada.');
        }

        $pixKey->loadMissing('bank');

        return [
            'id' => (string) $pixKey->id,
            'holder_name' => $pixKey->holder_name,
            'pix_key_type' => $pixKey->pix_key_type,
            'pix_key_value' => $pixKey->pix_key_value,
            'status' => $pixKey->status,
            'bank_id' => (string) $pixKey->bank_id,
            'bank' => $pixKey->bank
                ? ['id' => (string) $pixKey->bank->id, 'name' => $pixKey->bank->name]
                : null,
        ];
    }

    /**
     * Alterna el status ACTIVE/INACTIVE de una clave PIX.
     *
     * @param string      $userId      ID del usuario dueño (para validación de propiedad)
     * @param string|null $actorUserId ID de quien ejecuta la acción (para auditoría)
     * @param string|null $actorRole   Rol del actor (para auditoría)
     *
     * @throws DirectoryException Si no existe o no pertenece al usuario
     */
    public function toggleStatus(string $id, string $userId, ?string $actorUserId = null, ?string $actorRole = null) : bool
    {
        $pixKey = $this->repository->find($id);

        if (! $pixKey || (string) $pixKey->user_id !== $userId) {
            throw DirectoryException::notFound('Clave PIX no encontrada.');
        }

        $previousStatus = $pixKey->status;
        $newStatus = $pixKey->status === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE';

        try {
            DB::beginTransaction();

            $this->repository->update($id, ['status' => $newStatus]);

            DB::commit();

            Log::info('PixKey status actualizado', ['id' => $id, 'status' => $newStatus]);

            $this->auditLogger->log(
                recordType: DirectoryAudit::TYPE_PIX_KEY,
                recordId: $id,
                ownerUserId: $userId,
                actorUserId: $actorUserId ?? $userId,
                event: DirectoryAudit::EVENT_STATUS_CHANGED,
                actorRole: $actorRole,
                changes: ['status' => [$previousStatus, $newStatus]],
            );

            return true;
        } catch (Throwable $e) {
            DB::rollBack();
            Log::error('Error al cambiar status PixKey', ['id' => $id, 'error' => $e->getMessage()]);

            throw DirectoryException::operationFailed('Error al cambiar el estado de la clave PIX.');
        }
    }

    /**
     * Actualiza una clave PIX existente.
     *
     * @param string      $userId      ID del usuario dueño (para validación de propiedad)
     * @param string|null $actorUserId ID de quien ejecuta la acción (para auditoría)
     * @param string|null $actorRole   Rol del actor (para auditoría)
     *
     * @throws DirectoryException Si el registro no existe o no pertenece al usuario
     */
    public function update(PixKeyDTO $dto, string $userId, ?string $actorUserId = null, ?string $actorRole = null) : bool
    {
        $existing = $this->repository->find($dto->id);

        if (! $existing || (string) $existing->user_id !== $userId) {
            throw DirectoryException::notFound('Clave PIX no encontrada.');
        }

        $changes = $this->diffChanges($existing, $dto->toArray());

        try {
            DB::beginTransaction();

            $this->repository->update($dto->id, $dto->toArray());

            DB::commit();

            Log::info('PixKey actualizada', ['id' => $dto->id]);

            $this->auditLogger->log(
                recordType: DirectoryAudit::TYPE_PIX_KEY,
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
            Log::error('Error al actualizar PixKey', ['id' => $dto->id, 'error' => $e->getMessage()]);

            throw DirectoryException::operationFailed('Error al actualizar la clave PIX.');
        }
    }

    /**
     * Compara los campos relevantes del registro existente contra el nuevo
     * payload — solo lo que realmente cambió queda en la auditoría.
     *
     * @return array<string, array{0: mixed, 1: mixed}>
     */
    private function diffChanges(\App\Models\Directories\Fiat\Brazil\PixKey $existing, array $newData) : array
    {
        $tracked = ['holder_name', 'pix_key_type', 'pix_key_value', 'bank_id'];
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
