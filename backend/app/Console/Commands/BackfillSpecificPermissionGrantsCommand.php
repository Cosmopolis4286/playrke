<?php

namespace App\Console\Commands;

use App\Models\User;
use App\Models\UserSpecificPermissionGrant;
use Illuminate\Console\Command;

/**
 * One-off: migra users.specific_permissions (JSON plano de strings, sin
 * expiración) a filas en user_specific_permission_grants (expires_at null =
 * permanente, igual comportamiento que hoy). No borra la columna JSON —
 * eso queda para una migración de schema aparte una vez confirmado que el
 * backfill corrió limpio en cada entorno.
 */
class BackfillSpecificPermissionGrantsCommand extends Command
{
    protected $signature = 'permissions:backfill-specific-grants';

    protected $description = 'Migra users.specific_permissions (JSON) a la tabla user_specific_permission_grants';

    public function handle(): int
    {
        $users = User::whereNotNull('specific_permissions')->get(['id', 'specific_permissions']);
        $migrated = 0;
        $skipped = 0;

        foreach ($users as $user) {
            $names = $user->specific_permissions ?? [];
            if ($names === []) {
                continue;
            }

            $alreadyGranted = UserSpecificPermissionGrant::where('user_id', $user->id)
                ->active()
                ->whereIn('permission_name', $names)
                ->pluck('permission_name')
                ->all();

            foreach (array_diff($names, $alreadyGranted) as $name) {
                UserSpecificPermissionGrant::create([
                    'user_id' => $user->id,
                    'permission_name' => $name,
                    'reason' => 'Migrado desde specific_permissions (JSON legado)',
                ]);
                $migrated++;
            }

            $skipped += count($alreadyGranted);
        }

        $this->info("Backfill completo: {$migrated} grants creados, {$skipped} ya existían.");

        return self::SUCCESS;
    }
}
