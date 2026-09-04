<?php

namespace App\Services\Roles\Admin\Subsidiary;

use App\Contracts\Roles\Subsidiary\SubsidiaryRepositoryInterface;
use App\Domains\Wallet\Contracts\WalletRepositoryInterface;
use App\DTOs\Roles\Admin\Subsidiary\AdminSubsidiaryDTO;
use App\Events\Admin\UserRegistered;
use App\Exceptions\Domain\Roles\Admin\Subsidiary\AdminSubsidiaryException;
use App\Http\Resources\Roles\Admin\Subsidiary\AdminSubsidiaryResource;
use App\Models\Roles\Subsidiary;
use App\Services\UserService;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

/**
 * Servicio de dominio responsable de operaciones CRUD
 * sobre sucursales en contexto AdminSubsidiary.
 *
 * Responsabilidades:
 * - Orquestar creación y mantenimiento del agregado Subsidiary
 * - Coordinar persistencia de entidades relacionadas
 * - Aplicar reglas de dominio de la sucursal
 *
 * NO retorna booleanos.
 * NO maneja HTTP.
 * FALLA lanzando excepciones de dominio.
 */
final class AdminSubsidiaryCrudService
{
    public function __construct(
        private readonly UserService $userService,
        private readonly WalletRepositoryInterface $walletRepository,
        private readonly SubsidiaryRepositoryInterface $subsidiaryRepository,
    ) {}

    /**
     * Crear una sucursal completa (User + Subsidiary + Wallet).
     *
     * @param AdminSubsidiaryDTO $dto
     * @return string Contraseña generada para la sucursal
     *
     * @throws AdminSubsidiaryException
     */
    public function create(AdminSubsidiaryDTO $dto): string
    {
        return DB::transaction(function () use ($dto) {

            $plainPassword = bin2hex(random_bytes(4));

            // 1. Usuario (Subsidiary no maneja zona horaria propia — nullable en users)
            $user = $this->userService->createUserFromDTO(
                (object) ['username' => $dto->username, 'email' => $dto->email, 'country' => $dto->country, 'timeZone' => null],
                $plainPassword
            );
            $user->assignRole('subsidiary');

            // 2. Sucursal — token/security_key son NOT NULL sin default en BD,
            // no se capturan desde el formulario (uso interno/integración).
            $subsidiary = $this->subsidiaryRepository->create([
                'name'         => $dto->name,
                'brand'        => $dto->brand,
                'product'      => $dto->product,
                'link_route'   => $dto->linkRoute,
                'token'        => Str::random(40),
                'security_key' => Str::random(40),
                'commission'   => $dto->commission ?? 0,
                'phone'        => $dto->phone,
                'commerce_id'  => $dto->commerce,
                'country_id'   => $dto->country,
                'currency_id'  => $dto->currency,
                'status'       => 'ACTIVE',
                'user_id'      => $user->id,
            ]);

            if (! $subsidiary) {
                throw AdminSubsidiaryException::creationFailed();
            }

            // 3. Wallet principal
            $this->walletRepository->create([
                'user_id'     => $user->id,
                'currency_id' => $dto->currency,
            ]);

            broadcast(new UserRegistered(
                userId: (string) $user->id,
                username: $user->username,
                email: $user->email,
                roleName: 'subsidiary',
                createdAt: $user->created_at->toISOString(),
            ));

            return $plainPassword;
        });
    }

    /**
     * Obtener sucursal para visualización.
     *
     * Carga jerarquía completa (mismo criterio que AdminCommerceCrudService::
     * show()/ManagerSellerCrudService::show()): hacia arriba (Commerce padre)
     * y hacia abajo (Sellers propios de esta Subsidiary) — antes solo
     * cargaba commerce:id,name (sin status), sin sellers, dejando la ficha
     * sin datos de jerarquía para mostrar.
     *
     * @param Subsidiary $subsidiary
     * @return AdminSubsidiaryResource
     */
    public function show(Subsidiary $subsidiary): AdminSubsidiaryResource
    {
        $subsidiary->load([
            'commerce:id,name,status',
            'country:id,name',
            'user:id,username,email',
            'sellers:id,name,phone,status,subsidiary_id',
        ])->loadCount('sellers');

        return new AdminSubsidiaryResource($subsidiary);
    }

    /**
     * Actualizar sucursal.
     *
     * @param Subsidiary $subsidiary
     * @param AdminSubsidiaryDTO $dto
     *
     * @throws AdminSubsidiaryException
     */
    public function update(Subsidiary $subsidiary, AdminSubsidiaryDTO $dto): void
    {
        DB::transaction(function () use ($subsidiary, $dto) {

            $this->subsidiaryRepository->update(
                $subsidiary->id,
                array_filter([
                    'name'        => $dto->name,
                    'phone'       => $dto->phone,
                    'commission'  => $dto->commission,
                    'commerce_id' => $dto->commerce,
                    'country_id'  => $dto->country,
                    'currency_id' => $dto->currency,
                    'brand'       => $dto->brand,
                    'product'     => $dto->product,
                    'link_route'  => $dto->linkRoute,
                ], static fn ($value) => $value !== null)
            );

            if ($dto->email !== null || $dto->username !== null) {
                $subsidiary->user()->update(array_filter([
                    'email'    => $dto->email,
                    'username' => $dto->username,
                ]));
            }
        });
    }

    /**
     * Alternar estado (ACTIVE / INACTIVE) de la sucursal.
     * También sincroniza el estado del usuario asociado.
     *
     * @param Subsidiary $subsidiary
     * @return bool True si el cambio fue exitoso
     *
     * @throws AdminSubsidiaryException Si la sucursal está eliminada
     */
    public function toggleStatus(Subsidiary $subsidiary): bool
    {
        if ($subsidiary->status === 'DELETED') {
            throw AdminSubsidiaryException::invalidStatusChange();
        }

        $newStatus = $subsidiary->status === 'ACTIVE' ? 'INACTIVE' : 'ACTIVE';

        return $this->subsidiaryRepository->changeStatus($subsidiary, $newStatus, 'user');
    }

    /**
     * Eliminación lógica (soft delete) de la sucursal.
     * También marca el usuario asociado como DELETED.
     *
     * @param Subsidiary $subsidiary
     * @return bool True si la eliminación fue exitosa
     *
     * @throws AdminSubsidiaryException Si la sucursal ya está eliminada
     */
    public function delete(Subsidiary $subsidiary): bool
    {
        if ($subsidiary->status === 'DELETED') {
            throw AdminSubsidiaryException::alreadyDeleted();
        }

        return $this->subsidiaryRepository->softDelete(
            model: $subsidiary,
            status: 'DELETED',
            relationsToDetach: [],
            userRelation: 'user'
        );
    }

    /**
     * Eliminación definitiva de la sucursal y su usuario asociado.
     *
     * @param Subsidiary $subsidiary
     *
     * @throws AdminSubsidiaryException
     */
    public function destroy(Subsidiary $subsidiary): void
    {
        DB::transaction(function () use ($subsidiary) {

            $subsidiary->loadMissing(['user']);

            // Elimina wallets del usuario
            $this->walletRepository->deleteByUserId($subsidiary->user_id);

            $user = $subsidiary->user;
            $userId = $subsidiary->user_id;

            $subsidiary->forceDelete();

            // Verifica si el usuario tiene otras sucursales activas
            $hasOtherSubsidiaries = Subsidiary::where('user_id', $userId)
                ->where('id', '!=', $subsidiary->id)
                ->exists();

            if (! $hasOtherSubsidiaries && $user) {
                $user->forceDelete();
            }
        });
    }
}
