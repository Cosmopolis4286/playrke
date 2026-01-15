<?php

namespace App\Services;

use App\Contracts\BaseRepositoryInterface;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Hash;
use InvalidArgumentException;
use RuntimeException;

abstract class BaseService
{
    /**
     * Repositorio asociado al servicio.
     */
    protected BaseRepositoryInterface $repository;

    /**
     * Estados válidos del sistema.
     */
    protected const VALID_STATUSES = [
        'ACTIVE',
        'INACTIVE',
        'BLOCKED',
        'DELETED',
    ];

    /**
     * @param BaseRepositoryInterface $repository
     */
    public function __construct(BaseRepositoryInterface $repository)
    {
        $this->repository = $repository;
    }

    /* =====================================================
     | Validaciones
     |=====================================================*/

    protected function assertValidStatus(string $status): void
    {
        if (!in_array($status, self::VALID_STATUSES, true)) {
            throw new InvalidArgumentException("Estado inválido: {$status}");
        }
    }

    /* =====================================================
     | Acciones comunes
     |=====================================================*/

    /**
     * Soft delete dinámico con soporte para usuario relacionado.
     *
     * @param string|int|Model $model
     * @param string|null $status Estado a aplicar (ej: DELETED)
     * @param array $relationsToDetach Relaciones many-to-many a detach
     * @param string|null $userRelation Relación belongsTo hacia User (ej: 'user')
     */
    public function softDelete(
        string|int|Model $model,
        ?string $status = 'DELETED',
        array $relationsToDetach = [],
        ?string $userRelation = null
    ): void {
        DB::transaction(function () use ($model, $status, $relationsToDetach, $userRelation) {

            /** @var Model $model */
            $model = $model instanceof Model
                ? $model
                : $this->repository->findOrFail($model);

            // Validar SoftDeletes en modelo principal
            if (!in_array(SoftDeletes::class, class_uses_recursive($model), true)) {
                throw new RuntimeException('El modelo no soporta SoftDeletes');
            }

            if ($model->trashed()) {
                return;
            }

            /* ---------------------------------------------
         | Actualizar status del modelo principal
         ---------------------------------------------*/
            if ($status !== null && isset($model->status)) {
                $this->assertValidStatus($status);
                $model->status = $status;
                $model->save();
            }

            /* ---------------------------------------------
         | Detach relaciones many-to-many
         ---------------------------------------------*/
            foreach ($relationsToDetach as $relation) {
                if (method_exists($model, $relation)) {
                    $model->$relation()->detach();
                }
            }

            /* ---------------------------------------------
         | Soft delete del usuario relacionado (opcional)
         ---------------------------------------------*/
            if ($userRelation && method_exists($model, $userRelation)) {
                $model->loadMissing($userRelation);

                $user = $model->$userRelation;

                if ($user instanceof Model) {
                    if (!in_array(SoftDeletes::class, class_uses_recursive($user), true)) {
                        throw new RuntimeException('El usuario no soporta SoftDeletes');
                    }

                    if (!$user->trashed()) {
                        if ($status !== null && isset($user->status)) {
                            $user->status = $status;
                            $user->save();
                        }

                        $user->delete();
                    }
                }
            }

            /* ---------------------------------------------
         | Soft delete del modelo principal
         ---------------------------------------------*/
            $model->delete();
        });
    }

    /**
     * Cambio de estado genérico.
     */
    public function changeStatus(
        Model $model,
        ?string $newStatus = null,
        string $userRelation = 'user'
    ): void {
        $status = $newStatus ?? ($model->status === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE');

        $this->assertValidStatus($status);

        DB::transaction(function () use ($model, $status, $userRelation) {
            $model->update(['status' => $status]);

            $model->loadMissing($userRelation);

            if ($model->$userRelation instanceof Model) {
                $model->$userRelation->update(['status' => $status]);
            }
        });
    }

    /**
     * Resetear contraseña del usuario asociado.
     */
    public function resetPassword(
        Model $model,
        int $length = 12,
        string $userRelation = 'user'
    ): string {
        $newPassword = Str::random($length);

        $model->loadMissing($userRelation);

        if (!$model->$userRelation instanceof Model) {
            throw new RuntimeException('Relación de usuario no encontrada');
        }

        $model->$userRelation->update([
            'password' => Hash::make($newPassword),
        ]);

        return $newPassword;
    }
}
