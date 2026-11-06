<?php

namespace App\Console\Commands;

use App\Domains\Wallet\Models\WalletMovement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class MigrateWalletReceiptsToPrivateStorageCommand extends Command
{
    protected $signature = 'wallet:migrate-receipts-private {--apply : Ejecuta la migración y elimina las copias públicas verificadas}';

    protected $description = 'Migra comprobantes de billetera desde el disco público al almacenamiento privado';

    public function handle(): int
    {
        $public = Storage::disk('public');
        $private = Storage::disk('local');
        $apply = (bool) $this->option('apply');
        $stats = ['found' => 0, 'migrated' => 0, 'missing' => 0, 'failed' => 0];

        WalletMovement::query()
            ->whereNotNull('receipt_path')
            ->orderBy('id')
            ->chunkById(200, function($movements) use ($public, $private, $apply, &$stats) {
                foreach ($movements as $movement) {
                    $path = (string) $movement->receipt_path;

                    // Solo procesa el namespace conocido de comprobantes.
                    // No acepta rutas arbitrarias guardadas en la BD.
                    if (! str_starts_with($path, 'wallet/receipts/') || str_contains($path, '..')) {
                        continue;
                    }

                    if (! $public->exists($path)) {
                        if (! $private->exists($path)) {
                            $stats['missing']++;
                        }

                        continue;
                    }

                    $stats['found']++;

                    if (! $apply) {
                        continue;
                    }

                    try {
                        if (! $private->exists($path)) {
                            $stream = $public->readStream($path);

                            if (! is_resource($stream)) {
                                throw new RuntimeException('No se pudo leer el archivo de origen.');
                            }

                            try {
                                $written = $private->writeStream($path, $stream);
                            } finally {
                                fclose($stream);
                            }

                            if (! $written || ! $private->exists($path)) {
                                throw new RuntimeException('No se pudo verificar la copia privada.');
                            }
                        }

                        // La copia pública solo se elimina después de confirmar
                        // que el archivo privado existe.
                        if (! $public->delete($path)) {
                            throw new RuntimeException('La copia privada existe, pero no se pudo eliminar la copia pública.');
                        }

                        $stats['migrated']++;
                    } catch (Throwable $e) {
                        $stats['failed']++;
                        $this->error($path . ': ' . $e->getMessage());
                    }
                }
            }, 'id');

        $this->table(
            ['Archivos públicos encontrados', 'Migrados', 'Sin archivo', 'Fallidos', 'Modo'],
            [[
                $stats['found'],
                $stats['migrated'],
                $stats['missing'],
                $stats['failed'],
                $apply ? 'APLICADO' : 'SIMULACIÓN',
            ]],
        );

        if (! $apply) {
            $this->comment('Ejecuta wallet:migrate-receipts-private --apply para copiar al disco privado y retirar las copias públicas.');
        }

        return $stats['failed'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
