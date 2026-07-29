<?php

namespace Database\Seeders;

use App\Models\WithdrawalLimit;
use Illuminate\Database\Seeder;

class WithdrawalLimitSeeder extends Seeder
{
    /**
     * Límites globales por nivel KYC (currency_id null = todas las monedas).
     *
     * kyc_level  0 = sin verificar (solo un monto simbólico)
     *            1 = email + teléfono verificados
     *            2 = documento de identidad verificado (KYC completo)
     */
    public function run(): void
    {
        $limits = [
            ['kyc_level' => 0, 'min_per_transaction' => 1, 'max_per_transaction' => 50,   'max_daily' => 50,    'max_weekly' => 100,   'max_monthly' => 200],
            ['kyc_level' => 1, 'min_per_transaction' => 1, 'max_per_transaction' => 500,  'max_daily' => 1000,  'max_weekly' => 3000,  'max_monthly' => 10000],
            ['kyc_level' => 2, 'min_per_transaction' => 1, 'max_per_transaction' => 5000, 'max_daily' => 10000, 'max_weekly' => 30000, 'max_monthly' => 100000],
        ];

        foreach ($limits as $limit) {
            WithdrawalLimit::firstOrCreate(
                ['currency_id' => null, 'kyc_level' => $limit['kyc_level']],
                $limit + ['is_active' => true],
            );
        }
    }
}
