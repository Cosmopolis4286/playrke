<?php

namespace App\Services\Directory;

use App\DTOs\Directories\Fiat\Venezuela\PagoMovilDTO;
use App\Exceptions\Domain\Directory\DirectoryException;
use App\Http\Resources\Directories\Fiat\Venezuela\PagoMovilResource;
use App\Models\Directories\Fiat\Venezuela\PagoMovil;
use App\Repositories\Directories\Fiat\Venezuela\PagoMovilRepository;
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
     * @param PagoMovilRepository $repository Repositorio de datos
     */
    public function __construct(
        private readonly PagoMovilRepository $repository
    ) {}

    /**
     * Obtiene todos los registros de pago móvil de un usuario.
     *
     * @param string $userId ID del usuario
     * @return array<int, array<string, mixed>> Lista de registros transformados
     */
    public function getAll(string $userId): array
    {
        $pagoMoviles = $this->repository->getByUser($userId);

        return PagoMovilResource::collection($pagoMoviles)->resolve();
    }

    /**
     * Obtiene un registro específico de pago móvil.
     *
     * @param string $id ID del registro
     * @param string $userId ID del usuario (para validación de propiedad)
     * @return PagoMovilResource Registro transformado
     *
     * @throws DirectoryException Si no se encuentra el registro o no pertenece al usuario
     */
    public function show(string $id, string $userId): PagoMovilResource
    {
        $pagoMovil = $this->repository->find($id);

        if (!$pagoMovil || $pagoMovil->user_id != $userId) {
            throw DirectoryException::notFound('Registro de pago móvil');
        }

        return new PagoMovilResource($pagoMovil);
    }

    /**
     * Crea un nuevo registro de pago móvil.
     *
     * @param PagoMovilDTO $dto    DTO con los datos del registro
     * @param string       $userId ID del usuario propietario
     * @return bool True si se creó correctamente
     *
     * @throws DirectoryException Si ya existe un registro duplicado
     */
    public function create(PagoMovilDTO $dto, string $userId): bool
    {
        // Validación de duplicados
        if ($this->repository->existsPagoMovil(
            $dto->documentTypeId,
            $dto->documentNumber,
            $dto->bankId,
            $dto->phone
        )) {
            throw DirectoryException::duplicate('Ya existe un registro con este documento y banco.');
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

            throw DirectoryException::operationFailed('Error al crear el registro de pago móvil.');
        }
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
     * @param PagoMovilDTO $dto DTO con los datos actualizados
     * @param string       $userId ID del usuario (para validación de propiedad)
     * @return bool True si se actualizó correctamente
     *
     * @throws DirectoryException Si el registro no existe, no pertenece al usuario, o hay duplicados
     */
    public function update(PagoMovilDTO $dto, string $userId): bool
    {
        if (!$dto->id) {
            throw DirectoryException::missingId();
        }

        $existing = $this->repository->find($dto->id);

        if (!$existing || (string) $existing->user_id !== $userId) {
            throw DirectoryException::notFound('Registro de pago móvil');
        }

        // Validación de duplicados (excluyendo el usuario actual)
        if ($this->repository->existsPagoMovil(
            $dto->documentTypeId,
            $dto->documentNumber,
            $dto->bankId,
            $dto->phone,
            $existing->user_id
        )) {
            throw DirectoryException::duplicate('Ya existe otro registro con este documento y banco.');
        }

        try {
            DB::beginTransaction();

            $this->repository->update($dto->id, $dto->toArray());

            DB::commit();

            Log::info('Pago móvil actualizado exitosamente', [
                'id' => $dto->id,
            ]);

            return true;
        } catch (\Throwable $e) {
            DB::rollBack();

            Log::error('Error al actualizar pago móvil', [
                'id' => $dto->id,
                'error' => $e->getMessage(),
            ]);

            throw DirectoryException::operationFailed('Error al actualizar el registro de pago móvil.');
        }
    }

    /**
     * Elimina un registro de pago móvil (soft delete).
     *
     * @param string $id ID del registro
     * @param int $userId ID del usuario (para validación de propiedad)
     * @return bool True si se eliminó correctamente
     *
     * @throws DirectoryException Si el registro no existe o no pertenece al usuario
     */
    public function delete(string $id, int $userId): bool
    {
        $existing = $this->repository->find($id);

        if (!$existing || $existing->user_id != $userId) {
            throw DirectoryException::notFound('Registro de pago móvil');
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

            throw DirectoryException::operationFailed('Error al eliminar el registro de pago móvil.');
        }
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
     * @param PagoMovil $pagoMovil Registro de directorio a actualizar
     * @param string    $userId    ID del usuario (para validación de propiedad)
     * @return bool True si el cambio fue exitoso
     *
     * @throws DirectoryException Si el registro no pertenece al usuario o está eliminado
     */
    public function toggleStatus(PagoMovil $pagoMovil, string $userId): bool
    {
        if ((string) $pagoMovil->user_id !== $userId) {
            throw DirectoryException::notFound('Registro de pago móvil');
        }

        if ($pagoMovil->status === 'DELETED') {
            throw DirectoryException::invalidStatusChange();
        }

        return $this->repository->toggleStatus($pagoMovil);
    }
}
