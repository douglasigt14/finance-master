<?php

namespace App\Services;

use App\DTOs\CreateTransactionDTO;
use App\DTOs\UpdateTransactionDTO;
use App\Models\Transaction;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TransactionService
{
    public function getAllByUser(int $userId, array $filters = []): Collection
    {
        $query = Transaction::where('user_id', $userId)
            ->with(['category', 'card'])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc');

        if (isset($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        if (isset($filters['category_id'])) {
            $query->where('category_id', $filters['category_id']);
        }

        if (isset($filters['card_id'])) {
            $query->where('card_id', $filters['card_id']);
        }

        if (isset($filters['payment_method'])) {
            $query->where('payment_method', $filters['payment_method']);
        }

        if (isset($filters['date_from'])) {
            $query->where('transaction_date', '>=', $filters['date_from']);
        }

        if (isset($filters['date_to'])) {
            $query->where('transaction_date', '<=', $filters['date_to']);
        }

        return $query->get();
    }

    public function getById(int $transactionId, int $userId): ?Transaction
    {
        return Transaction::where('id', $transactionId)
            ->where('user_id', $userId)
            ->with(['category', 'card'])
            ->first();
    }

    public function create(CreateTransactionDTO $dto): Transaction|Collection
    {
        return DB::transaction(function () use ($dto) {
            // If it's a credit card transaction with installments, create multiple transactions
            if ($dto->paymentMethod === 'CREDIT' && $dto->installmentsTotal > 1) {
                return $this->createInstallments($dto);
            }

            // Single transaction
            return Transaction::create([
                'user_id' => $dto->userId,
                'category_id' => $dto->categoryId,
                'card_id' => $dto->cardId,
                'type' => $dto->type,
                'payment_method' => $dto->paymentMethod,
                'amount' => $dto->amount,
                'description' => $dto->description,
                'transaction_date' => $dto->transactionDate,
                'installments_total' => $dto->installmentsTotal,
                'installment_number' => 1,
                'group_uuid' => null,
                'is_paid' => false,
            ]);
        });
    }

    private function createInstallments(CreateTransactionDTO $dto): Collection
    {
        $groupUuid = Str::uuid()->toString();
        $installmentAmount = $dto->amount / $dto->installmentsTotal;
        $transactions = collect();

        $transactionDate = \Carbon\Carbon::parse($dto->transactionDate);

        for ($i = 1; $i <= $dto->installmentsTotal; $i++) {
            $installmentDate = $transactionDate->copy()->addMonths($i - 1);

            $transaction = Transaction::create([
                'user_id' => $dto->userId,
                'category_id' => $dto->categoryId,
                'card_id' => $dto->cardId,
                'type' => $dto->type,
                'payment_method' => $dto->paymentMethod,
                'amount' => $installmentAmount,
                'description' => $dto->description . ' - Parcela ' . $i . '/' . $dto->installmentsTotal,
                'transaction_date' => $installmentDate->format('Y-m-d'),
                'installments_total' => $dto->installmentsTotal,
                'installment_number' => $i,
                'group_uuid' => $groupUuid,
                'is_paid' => false,
            ]);

            $transactions->push($transaction);
        }

        return $transactions;
    }

    public function update(Transaction $transaction, UpdateTransactionDTO $dto): Transaction
    {
        $transaction->update($dto->toArray());
        return $transaction->fresh()->load(['category', 'card']);
    }

    public function delete(Transaction $transaction): bool
    {
        // If it's part of an installment group, delete all installments
        if ($transaction->group_uuid) {
            return DB::transaction(function () use ($transaction) {
                Transaction::where('group_uuid', $transaction->group_uuid)
                    ->where('user_id', $transaction->user_id)
                    ->delete();
                return true;
            });
        }

        return $transaction->delete();
    }

    public function markAsPaid(Transaction $transaction): Transaction
    {
        $transaction->update(['is_paid' => true]);
        return $transaction->fresh();
    }

    public function markAsUnpaid(Transaction $transaction): Transaction
    {
        $transaction->update(['is_paid' => false]);
        return $transaction->fresh();
    }

    public function getInstallmentGroup(string $groupUuid, int $userId): Collection
    {
        return Transaction::where('group_uuid', $groupUuid)
            ->where('user_id', $userId)
            ->orderBy('installment_number')
            ->get();
    }
}
