<?php

namespace App\Console\Commands;

use App\Models\Roles\Seller;
use App\Services\Roles\CommissionHierarchyGuard;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

/**
 * Auditoría de SOLO LECTURA de la jerarquía comercial (Comercializadora →
 * Agencia → Vendedor). No corrige nada: cada hallazgo es una decisión de
 * negocio (a quién subir/bajar comisión, a qué comercializadora asignar un
 * vendedor) que se resuelve desde las pantallas de admin.
 *
 * Dos tipos de hallazgo:
 * - TOPE: agencia o vendedor con comisión por encima de su nivel superior
 *   (mismas reglas que CommissionHierarchyGuard). Siguen vendiendo — el
 *   reparto en cascada (CommissionCalculationService::networkSplit()) nunca
 *   cobra negativo; el nivel superior simplemente no recibe margen. Se
 *   corrigen editando la tasa del hijo (bajar) o del padre (subir): el guard
 *   solo valida cuando la tasa cambia, así que el resto de la ficha sigue
 *   siendo editable mientras tanto.
 * - HUÉRFANO: vendedor sin Comercializadora resoluble (manager_user_id no es
 *   un Commerce — legados creados por un 'customer' antes de que dejara de
 *   poder tener sellers). No puede vender (Seller::hasActiveHierarchy()
 *   false). Se corrige desde /admin/sellers → editar → elegir Manager.
 *
 * Exit code 1 si hay hallazgos (usable como chequeo post-deploy).
 */
class AuditCommissionHierarchyCommand extends Command
{
    protected $signature = 'commissions:audit-hierarchy';

    protected $description = 'Lista comisiones fuera de tope (vendedor ≤ agencia ≤ comercializadora) y vendedores sin comercializadora';

    public function handle(CommissionHierarchyGuard $guard): int
    {
        $violations = $guard->findViolations();
        $orphans = $this->findOrphans();

        if ($violations === [] && $orphans === []) {
            $this->info('✔ Jerarquía comercial consistente: sin comisiones fuera de tope ni vendedores huérfanos.');

            return self::SUCCESS;
        }

        if ($violations !== []) {
            $this->warn(count($violations) . ' comisión(es) fuera de tope — siguen vendiendo, pero su nivel superior no recibe margen:');
            $this->table(
                ['Tipo', 'ID', 'Nombre', 'Comisión', 'Tope', 'Nivel superior', 'Corrección'],
                array_map(fn (array $v) => [
                    $v['type'], $v['id'], $v['name'],
                    number_format($v['rate'], 2) . '%',
                    number_format($v['max'], 2) . '%',
                    $v['parent'],
                    sprintf('bajar a ≤ %s%% o subir el nivel superior a ≥ %s%%', number_format($v['max'], 2), number_format($v['rate'], 2)),
                ], $violations),
            );
        }

        if ($orphans !== []) {
            $this->warn(count($orphans) . ' vendedor(es) sin comercializadora — no pueden vender hasta reasignarlos (/admin/sellers → editar → Manager):');
            $this->table(['ID', 'Nombre', 'Estado', 'Manager actual (user_id)', 'Rol del manager'], $orphans);
        }

        return self::FAILURE;
    }

    /** @return list<array{0: string, 1: string, 2: string, 3: string, 4: string}> */
    private function findOrphans(): array
    {
        $orphans = [];

        Seller::with(['subsidiary:id,commerce_id', 'subsidiary.commerce:id,user_id', 'managerCommerce:id,user_id'])
            ->where('status', '!=', 'DELETED')
            ->chunkById(500, function ($sellers) use (&$orphans) {
                foreach ($sellers as $seller) {
                    if ($seller->resolveCommerce() !== null) {
                        continue;
                    }

                    $managerRole = DB::table('role_user')
                        ->join('roles', 'roles.id', '=', 'role_user.role_id')
                        ->where('role_user.user_id', $seller->manager_user_id)
                        ->value('roles.name');

                    $orphans[] = [
                        (string) $seller->id,
                        (string) $seller->name,
                        (string) $seller->status,
                        (string) ($seller->manager_user_id ?? '—'),
                        (string) ($managerRole ?? '—'),
                    ];
                }
            });

        return $orphans;
    }
}
