<?php

namespace App\Services\Directory;

use App\DTOs\Directories\Fiat\Brazil\PixKeyDTO;
use App\Repositories\Directories\Fiat\Brazil\PixKeyRepository;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

final class PixKeyCrudService
{
    public function __construct(
        private readonly PixKeyRepository $repository
    ) {}

    /**
     * Obtiene una clave PIX verificando que pertenece al usuario.
     *
     * @throws DomainException Si no existe o no pertenece al usuario
     */
    public function show(string $id, string $userId): array
    {
        $pixKey = $this->repository->find($id);

        if (!$pixKey || (string) $pixKey->user_id !== $userId) {
            throw new DomainException('Clave PIX no encontrada.');
        }

        $pixKey->loadMissing('bank');

        return [
            'id'            => (string) $pixKey->id,
            'name'          => $pixKey->name,
            'pix_key_type'  => $pixKey->pix_key_type,
            'pix_key_value' => $pixKey->pix_key_value,
            'status'        => $pixKey->status,
            'bank_id'       => (string) $pixKey->bank_id,
            'bank'          => $pixKey->bank
                ? ['id' => (string) $pixKey->bank->id, 'name' => $pixKey->bank->name]
                : null,
        ];
    }

    /**
     * Crea una nueva clave PIX para el usuario.
     *
     * @throws DomainException Si ya existe una clave PIX duplicada para el usuario
     */
    public function create(PixKeyDTO $dto, string $userId): bool
    {
        if ($this->repository->userHasPixKey($userId, $dto->pixKeyType, $dto->pixKeyValue)) {
            throw new DomainException('Ya tienes registrada esta clave PIX.');
        }

        try {
            DB::beginTransaction();

            $this->repository->create(array_merge($dto->toArray(), ['user_id' => $userId]));

            DB::commit();

            Log::info('PixKey creada', ['user_id' => $userId, 'type' => $dto->pixKeyType]);

            return true;
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error al crear PixKey', ['user_id' => $userId, 'error' => $e->getMessage()]);
            throw new DomainException('Error al guardar la clave PIX.');
        }
    }

    /**
     * Actualiza una clave PIX existente.
     *
     * @throws DomainException Si el registro no existe o no pertenece al usuario
     */
    public function update(PixKeyDTO $dto, string $userId): bool
    {
        $existing = $this->repository->find($dto->id);

        if (!$existing || (string) $existing->user_id !== $userId) {
            throw new DomainException('Clave PIX no encontrada.');
        }

        try {
            DB::beginTransaction();

            $this->repository->update($dto->id, $dto->toArray());

            DB::commit();

            Log::info('PixKey actualizada', ['id' => $dto->id]);

            return true;
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error al actualizar PixKey', ['id' => $dto->id, 'error' => $e->getMessage()]);
            throw new DomainException('Error al actualizar la clave PIX.');
        }
    }

    /**
     * Elimina una clave PIX.
     *
     * @throws DomainException Si el registro no existe o no pertenece al usuario
     */
    public function delete(string $id, string $userId): bool
    {
        $existing = $this->repository->find($id);

        if (!$existing || (string) $existing->user_id !== $userId) {
            throw new DomainException('Clave PIX no encontrada.');
        }

        try {
            DB::beginTransaction();

            $result = $this->repository->deletePixKey($id);

            DB::commit();

            Log::info('PixKey eliminada', ['id' => $id, 'user_id' => $userId]);

            return $result;
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error al eliminar PixKey', ['id' => $id, 'error' => $e->getMessage()]);
            throw new DomainException('Error al eliminar la clave PIX.');
        }
    }

    /**
     * Alterna el status ACTIVE/INACTIVE de una clave PIX.
     *
     * @throws DomainException Si no existe o no pertenece al usuario
     */
    public function toggleStatus(string $id, string $userId): bool
    {
        $pixKey = $this->repository->find($id);

        if (!$pixKey || (string) $pixKey->user_id !== $userId) {
            throw new DomainException('Clave PIX no encontrada.');
        }

        $newStatus = $pixKey->status === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE';

        try {
            DB::beginTransaction();

            $this->repository->update($id, ['status' => $newStatus]);

            DB::commit();

            Log::info('PixKey status actualizado', ['id' => $id, 'status' => $newStatus]);

            return true;
        } catch (\Throwable $e) {
            DB::rollBack();
            Log::error('Error al cambiar status PixKey', ['id' => $id, 'error' => $e->getMessage()]);
            throw new DomainException('Error al cambiar el estado de la clave PIX.');
        }
    }
}
