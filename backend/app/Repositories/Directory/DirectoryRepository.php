<?php

namespace App\Repositories\Directory;

use App\Models\Directories\Fiat\Venezuela\PagoMovil;
use Illuminate\Database\Eloquent\Collection;

/**
 * Repositorio para gestionar operaciones de base de datos
 * del directorio de Pago Móvil (Venezuela).
 *
 * Responsabilidades:
 * - Consultas y persistencia
 * - Eager loading de relaciones
 * - Queries optimizados
 *
 * NO contiene lógica de negocio.
 *
 * @package App\Repositories\Directory
 */
final class DirectoryRepository
{
    /**
     * Constructor del repositorio.
     */
    public function __construct(
        private readonly PagoMovil $model
    ) {}

    /**
     * Obtiene todos los registros de pago móvil de un usuario.
     *
     * @param string $userId ID del usuario
     * @param array $fields Campos a seleccionar SELECT
     *
     * @return Collection Colección de registros PagoMovil
     */
    public function getAllByUser(string $userId, $fields = ['*']): Collection
    {
        return $this->model
            ->with(['documentType:id,code,description', 'bank:id,name,code'])
            ->byUser($userId)
            ->orderBy('created_at', 'desc')
            ->get($fields);
    }

    /**
     * Obtiene un registro de pago móvil por su ID.
     *
     * @param string $id          ID del registro
     * @param string|null $userId ID del usuario (opcional, para validación)
     * @return PagoMovil|null Registro encontrado o null
     */
    public function findById(string $id, ?string $userId = null): ?PagoMovil
    {
        $query = $this->model
            ->with(['documentType:id,code,description', 'bank:id,name,code'])
            ->where('id', $id);

        if ($userId !== null) {
            $query->byUser($userId);
        }

        return $query->first();
    }

    /**
     * Crea un nuevo registro de pago móvil.
     *
     * @param array<string, mixed> $data Datos para crear
     * @return PagoMovil Registro creado
     */
    public function create(array $data): PagoMovil
    {
        return $this->model->create($data);
    }

    /**
     * Actualiza un registro de pago móvil existente.
     *
     * @param string               $id   ID del registro a actualizar
     * @param array<string, mixed> $data Datos actualizados
     * @return bool True si se actualizó correctamente
     */
    public function update(string $id, array $data): bool
    {
        $pagoMovil = $this->model->find($id);

        if (!$pagoMovil) {
            return false;
        }

        return $pagoMovil->update($data);
    }

    /**
     * Elimina un registro de pago móvil (soft delete).
     *
     * @param string $id ID del registro a eliminar
     * @return bool True si se eliminó correctamente
     */
    public function delete(string $id): bool
    {
        $pagoMovil = $this->model->find($id);

        if (!$pagoMovil) {
            return false;
        }

        return (bool) $pagoMovil->delete();
    }

    /**
     * Verifica si existe un pago móvil con el mismo documento y banco.
     *
     * @param string      $documentTypeId  ID del tipo de documento
     * @param string      $documentNumber  Número de documento
     * @param string      $bankId          ID del banco
     * @param string|null $excludeId       ID a excluir (para updates)
     * @return bool True si existe un duplicado
     */
    public function existsDuplicate(
        string $documentTypeId,
        string $documentNumber,
        string $bankId,
        ?string $excludeId = null
    ): bool {
        $query = $this->model
            ->where('document_type_id', $documentTypeId)
            ->where('document_number', $documentNumber)
            ->where('bank_id', $bankId);

        if ($excludeId !== null) {
            $query->where('id', '!=', $excludeId);
        }

        return $query->exists();
    }

    /**
     * Busca registros por número de teléfono.
     *
     * @param string $phone Número de teléfono
     * @return Collection Colección de registros
     */
    public function findByPhone(string $phone): Collection
    {
        return $this->model
            ->with(['documentType:id,code,description', 'bank:id,name,code'])
            ->byPhone($phone)
            ->get();
    }

    /**
     * Cuenta los registros de pago móvil de un usuario.
     *
     * @param string $userId ID del usuario
     * @return int Cantidad de registros
     */
    public function countByUser(string $userId): int
    {
        return $this->model->byUser($userId)->count();
    }
}
