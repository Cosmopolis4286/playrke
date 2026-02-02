<?php

namespace App\Services\Directory;

use App\DTOs\Directory\DirectoryDTO;
use App\Http\Resources\Directory\DirectoryResource;
use App\Repositories\Directory\DirectoryRepository;
use DomainException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Servicio para operaciones CRUD del directorio de Pago Móvil.
 *
 * Responsabilidades:
 * - Lógica de negocio para crear, leer, actualizar y eliminar
 * - Validaciones de dominio
 * - Manejo de transacciones
 * - Logging de operaciones
 *
 * @package App\Services\Directory
 */
final class DirectoryCrudService
{
    /**
     * Constructor del servicio CRUD.
     *
     * @param DirectoryRepository $repository Repositorio de datos
     */
    public function __construct(
        private readonly DirectoryRepository $repository
    ) {}

    /**
     * Obtiene todos los registros de pago móvil de un usuario.
     *
     * @param string $userId ID del usuario
     * @return array<int, array<string, mixed>> Lista de registros transformados
     */
    public function getAll(string $userId): array
    {
        $pagoMoviles = $this->repository->getAllByUser($userId);

        return DirectoryResource::collection($pagoMoviles)->resolve();
    }

    /**
     * Obtiene un registro específico de pago móvil.
     *
     * @param string $id ID del registro
     * @param string $userId ID del usuario (para validación de propiedad)
     * @return DirectoryResource Registro transformado
     *
     * @throws DomainException Si no se encuentra el registro o no pertenece al usuario
     */
    public function show(string $id, string $userId): DirectoryResource
    {
        $pagoMovil = $this->repository->findById($id, $userId);

        if (!$pagoMovil) {
            throw new DomainException('Registro de pago móvil no encontrado.');
        }

        return new DirectoryResource($pagoMovil);
    }

    /**
     * Crea un nuevo registro de pago móvil.
     *
     * @param DirectoryDTO $dto    DTO con los datos del registro
     * @param string       $userId ID del usuario propietario
     * @return bool True si se creó correctamente
     *
     * @throws DomainException Si ya existe un registro duplicado
     */
    public function create(DirectoryDTO $dto, string $userId): bool
    {
        // Validación de duplicados
        if ($this->repository->existsDuplicate(
            $dto->documentTypeId,
            $dto->documentNumber,
            $dto->bankId
        )) {
            throw new DomainException(
                'Ya existe un registro con este documento y banco.'
            );
        }

        try {
            DB::beginTransaction();

            $data = array_merge($dto->toArray(), ['user_id' => $userId]);

            $pagoMovil = $this->repository->create($data);

            DB::commit();

            Log::info('Pago móvil creado exitosamente', [
                'id' => $pagoMovil->id,
                'user_id' => $userId,
            ]);

            return true;
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Error al crear pago móvil', [
                'user_id' => $userId,
                'error' => $e->getMessage(),
            ]);

            throw new DomainException('Error al crear el registro de pago móvil.');
        }
    }

    /**
     * Actualiza un registro de pago móvil existente.
     *
     * @param DirectoryDTO $dto DTO con los datos actualizados
     * @return bool True si se actualizó correctamente
     *
     * @throws DomainException Si el registro no existe o hay duplicados
     */
    public function update(DirectoryDTO $dto): bool
    {
        if (!$dto->id) {
            throw new DomainException('ID del registro es requerido para actualizar.');
        }

        $existing = $this->repository->findById($dto->id);

        if (!$existing) {
            throw new DomainException('Registro de pago móvil no encontrado.');
        }

        // Validación de duplicados (excluyendo el registro actual)
        if ($this->repository->existsDuplicate(
            $dto->documentTypeId,
            $dto->documentNumber,
            $dto->bankId,
            $dto->id
        )) {
            throw new DomainException(
                'Ya existe otro registro con este documento y banco.'
            );
        }

        try {
            DB::beginTransaction();

            $updated = $this->repository->update($dto->id, $dto->toArray());

            DB::commit();

            Log::info('Pago móvil actualizado exitosamente', [
                'id' => $dto->id,
            ]);

            return $updated;
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Error al actualizar pago móvil', [
                'id' => $dto->id,
                'error' => $e->getMessage(),
            ]);

            throw new DomainException('Error al actualizar el registro de pago móvil.');
        }
    }

    /**
     * Elimina un registro de pago móvil (soft delete).
     *
     * @param string $id ID del registro
     * @param int $userId ID del usuario (para validación de propiedad)
     * @return bool True si se eliminó correctamente
     *
     * @throws DomainException Si el registro no existe o no pertenece al usuario
     */
    public function delete(string $id, int $userId): bool
    {
        $existing = $this->repository->findById($id, $userId);

        if (!$existing) {
            throw new DomainException('Registro de pago móvil no encontrado.');
        }

        try {
            DB::beginTransaction();

            $deleted = $this->repository->delete($id);

            DB::commit();

            Log::info('Pago móvil eliminado exitosamente', [
                'id' => $id,
                'user_id' => $userId,
            ]);

            return $deleted;
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Error al eliminar pago móvil', [
                'id' => $id,
                'error' => $e->getMessage(),
            ]);

            throw new DomainException('Error al eliminar el registro de pago móvil.');
        }
    }
}
