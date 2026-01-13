<?php

namespace App\Repositories\Games\Shared\Transactions;

use App\Models\Games\Shared\Transactions\Transaction;
use App\Repositories\Base\BaseRepository;
use App\Repositories\Games\Shared\Transactions\Contracts\TransactionRepositoryContract;
use Illuminate\Database\Eloquent\Collection;
use Carbon\Carbon;

/**
 * Repositorio para la gestión de transacciones.
 *
 * Implementa las operaciones específicas para el modelo Transaction,
 * incluyendo consultas por código, cliente, estado y gestión
 * de transacciones financieras del sistema.
 */
class TransactionRepository extends BaseRepository implements TransactionRepositoryContract
{
    /**
     * Constructor del repositorio.
     *
     * @param Transaction $model Instancia del modelo Transaction
     */
    public function __construct(Transaction $model)
    {
        parent::__construct($model);
    }

    /**
     * {@inheritDoc}
     */
    public function getValueByCode(string $code, string $field): mixed
    {
        return $this->model->where('code', $code)->value($field);
    }

    /**
     * {@inheritDoc}
     */
    public function existsCodeForCustomer(string $code, string $customer): bool
    {
        return $this->model->where('code', $code)
            ->where('customer', $customer)
            ->exists();
    }

    /**
     * {@inheritDoc}
     */
    public function getByCustomer(string $customerId): Collection
    {
        return $this->model->where('customer', $customerId)->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getByType(string $type): Collection
    {
        return $this->model->where('transaction_type', $type)->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getByGame(string $gameId): Collection
    {
        return $this->model->where('game', $gameId)->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getByCurrency(string $currencyId): Collection
    {
        return $this->model->where('currency', $currencyId)->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getByAmountRange(float $minAmount, float $maxAmount): Collection
    {
        return $this->model->whereBetween('amount', [$minAmount, $maxAmount])->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getByDateRange(string $startDate, string $endDate): Collection
    {
        return $this->model->whereBetween('created_at', [
            Carbon::parse($startDate),
            Carbon::parse($endDate)
        ])->get();
    }

    /**
     * {@inheritDoc}
     */
    public function getTransactionStats(): array
    {
        $total = $this->model->count();
        $totalAmount = $this->model->sum('amount');
        $avgAmount = $this->model->avg('amount');
        $byStatus = $this->model->selectRaw('status, COUNT(*) as count')
            ->groupBy('status')
            ->pluck('count', 'status')
            ->toArray();

        return [
            'total_transactions' => $total,
            'total_amount' => round($totalAmount, 2),
            'average_amount' => round($avgAmount ?? 0, 2),
            'by_status' => $byStatus
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function getCustomerStats(string $customerId): array
    {
        $transactions = $this->getByCustomer($customerId);

        return [
            'total_transactions' => $transactions->count(),
            'total_amount' => round($transactions->sum('amount'), 2),
            'average_amount' => round($transactions->avg('amount') ?? 0, 2),
            'max_amount' => $transactions->max('amount') ?? 0,
            'min_amount' => $transactions->min('amount') ?? 0
        ];
    }

    /**
     * {@inheritDoc}
     */
    public function createTransaction(array $data): mixed
    {
        $errors = $this->validateTransactionData($data);
        if (!empty($errors)) {
            throw new \InvalidArgumentException('Datos de transacción inválidos: ' . json_encode($errors));
        }

        return $this->model->create($data);
    }

    /**
     * {@inheritDoc}
     */
    public function updateStatus(string $transactionId, string $status): bool
    {
        return $this->model->where('id', $transactionId)
            ->update(['status' => $status]) > 0;
    }

    /**
     * {@inheritDoc}
     */
    public function validateTransactionData(array $data): array
    {
        $errors = [];

        // Validar campos requeridos
        $required = ['customer', 'code', 'transaction_type', 'currency', 'amount'];
        foreach ($required as $field) {
            if (!isset($data[$field]) || empty($data[$field])) {
                $errors[$field] = "El campo {$field} es requerido";
            }
        }

        // Validar monto
        if (isset($data['amount']) && $data['amount'] <= 0) {
            $errors['amount'] = 'El monto debe ser mayor a cero';
        }

        return $errors;
    }
}
