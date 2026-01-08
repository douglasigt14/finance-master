<?php

namespace App\Services;

use App\DTOs\CreateTransactionDTO;
use App\DTOs\UpdateTransactionDTO;
use App\Models\Card;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class TransactionService
{
    public function __construct(
        private InvoiceService $invoiceService
    ) {
    }
    public function getAllByUser(int $userId, array $filters = []): Collection
    {
        $query = Transaction::where('user_id', $userId)
            ->with(['category', 'card', 'debtor'])
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
            ->with(['category', 'card', 'debtor'])
            ->first();
    }

    public function create(CreateTransactionDTO $dto): Transaction|Collection
    {
        return DB::transaction(function () use ($dto) {
            // If it's a credit card transaction with installments, create multiple transactions
            if ($dto->paymentMethod === 'CREDIT' && $dto->installmentsTotal > 1) {
                $result = $this->createInstallments($dto);
                // Recalculate affected invoices
                $this->recalculateAffectedInvoices($dto->cardId, $result);
                return $result;
            }

            // Single transaction
            $transaction = Transaction::create([
                'user_id' => $dto->userId,
                'category_id' => $dto->categoryId,
                'card_id' => $dto->cardId,
                'debtor_id' => $dto->debtorId,
                'type' => $dto->type,
                'payment_method' => $dto->paymentMethod,
                'amount' => $dto->amount,
                'description' => $dto->description,
                'card_description' => $dto->cardDescription,
                'transaction_date' => $dto->transactionDate,
                'installments_total' => $dto->installmentsTotal,
                'installment_number' => 1,
                'group_uuid' => null,
                'is_paid' => false,
            ]);

            // Recalculate affected invoice if it's a credit transaction
            if ($dto->paymentMethod === 'CREDIT' && $dto->cardId) {
                $this->recalculateInvoiceForTransaction($transaction);
            }

            return $transaction;
        });
    }

    private function createInstallments(CreateTransactionDTO $dto): Collection
    {
        $groupUuid = Str::uuid()->toString();
        $installmentAmount = $dto->amount / $dto->installmentsTotal;
        $transactions = new Collection();

        $transactionDate = Carbon::parse($dto->transactionDate);
        $card = Card::find($dto->cardId);

        // Ensure future invoices exist for all installments based on their actual dates
        if ($card) {
            // Create invoices for each installment date
            for ($i = 1; $i <= $dto->installmentsTotal; $i++) {
                $installmentDate = $transactionDate->copy()->addMonths($i - 1);
                $month = $installmentDate->month;
                $year = $installmentDate->year;

                // Determine which cycle this installment belongs to
                if ($installmentDate->day < $card->closing_day) {
                    // Installment is in the previous cycle
                    $month = $installmentDate->copy()->subMonth()->month;
                    $year = $installmentDate->copy()->subMonth()->year;
                }

                // Create invoice for this cycle if it doesn't exist
                $this->invoiceService->getOrCreateInvoice($card, $month, $year);
            }
        }

        for ($i = 1; $i <= $dto->installmentsTotal; $i++) {
            $installmentDate = $transactionDate->copy()->addMonths($i - 1);

            $transaction = Transaction::create([
                'user_id' => $dto->userId,
                'category_id' => $dto->categoryId,
                'card_id' => $dto->cardId,
                'debtor_id' => $dto->debtorId,
                'type' => $dto->type,
                'payment_method' => $dto->paymentMethod,
                'amount' => $installmentAmount,
                'description' => $dto->description . ' - Parcela ' . $i . '/' . $dto->installmentsTotal,
                'card_description' => $dto->cardDescription,
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
        $oldCardId = $transaction->card_id;
        $oldDate = $transaction->transaction_date;
        $oldPaymentMethod = $transaction->payment_method;

        $transaction->update($dto->toArray());
        $updated = $transaction->fresh()->load(['category', 'card', 'debtor']);

        // Recalculate invoices if it's a credit transaction
        if ($updated->payment_method === 'CREDIT' && $updated->card_id) {
            $this->recalculateInvoiceForTransaction($updated);
            
            // Also recalculate old invoice if card or date changed
            if ($oldCardId && ($oldCardId != $updated->card_id || $oldDate != $updated->transaction_date)) {
                $oldCard = Card::find($oldCardId);
                if ($oldCard) {
                    $oldDateCarbon = Carbon::parse($oldDate);
                    $this->recalculateInvoiceForDate($oldCard, $oldDateCarbon);
                }
            }
        } elseif ($oldPaymentMethod === 'CREDIT' && $oldCardId) {
            // Transaction was changed from credit to something else, recalculate old invoice
            $oldCard = Card::find($oldCardId);
            if ($oldCard) {
                $oldDateCarbon = Carbon::parse($oldDate);
                $this->recalculateInvoiceForDate($oldCard, $oldDateCarbon);
            }
        }

        return $updated;
    }

    public function delete(Transaction $transaction): bool
    {
        $cardId = $transaction->card_id;
        $transactionDate = $transaction->transaction_date;
        $paymentMethod = $transaction->payment_method;

        // If it's part of an installment group, delete all installments
        if ($transaction->group_uuid) {
            return DB::transaction(function () use ($transaction, $cardId, $paymentMethod) {
                // Get all transactions in the group before deleting
                $groupTransactions = Transaction::where('group_uuid', $transaction->group_uuid)
                    ->where('user_id', $transaction->user_id)
                    ->get();
                
                // Delete all transactions
                Transaction::where('group_uuid', $transaction->group_uuid)
                    ->where('user_id', $transaction->user_id)
                    ->delete();
                
                // Recalculate affected invoices
                if ($paymentMethod === 'CREDIT' && $cardId) {
                    $this->recalculateAffectedInvoices($cardId, $groupTransactions);
                }
                
                return true;
            });
        }

        $deleted = $transaction->delete();
        
        // Recalculate invoice if it was a credit transaction
        if ($paymentMethod === 'CREDIT' && $cardId) {
            $card = Card::find($cardId);
            if ($card) {
                $dateCarbon = Carbon::parse($transactionDate);
                $this->recalculateInvoiceForDate($card, $dateCarbon);
            }
        }

        return $deleted;
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

    /**
     * Update all transactions in a group
     * Updates common fields and adjusts dates for installments
     */
    public function updateGroup(string $groupUuid, int $userId, array $data): Collection
    {
        return DB::transaction(function () use ($groupUuid, $userId, $data) {
            $groupTransactions = $this->getInstallmentGroup($groupUuid, $userId);
            
            if ($groupTransactions->isEmpty()) {
                throw new \Exception('Grupo de transações não encontrado.');
            }

            $firstTransaction = $groupTransactions->first();
            $card = $firstTransaction->card;
            $oldCardId = $firstTransaction->card_id;
            $installmentsTotal = $firstTransaction->installments_total;
            
            // Store old dates and card IDs for recalculation (before updating)
            $oldDates = [];
            $oldCardIds = [];
            foreach ($groupTransactions as $t) {
                $oldDates[] = Carbon::parse($t->transaction_date);
                $oldCardIds[] = $t->card_id;
            }
            
            // Parse the new base date if provided
            $newBaseDate = isset($data['transaction_date']) 
                ? Carbon::parse($data['transaction_date']) 
                : null;

            // Get base description (without installment suffix)
            $baseDescription = isset($data['description']) 
                ? $data['description'] 
                : preg_replace('/\s*-\s*Parcela\s+\d+\/\d+$/', '', $firstTransaction->description);

            // Update card if changed
            if (isset($data['card_id']) && $data['card_id'] != $firstTransaction->card_id) {
                $card = Card::find($data['card_id']);
                if (!$card) {
                    throw new \Exception('Cartão não encontrado.');
                }
            }

            // Ensure future invoices exist if date is being changed
            if ($newBaseDate && $card) {
                for ($i = 1; $i <= $installmentsTotal; $i++) {
                    $installmentDate = $newBaseDate->copy()->addMonths($i - 1);
                    $month = $installmentDate->month;
                    $year = $installmentDate->year;

                    if ($installmentDate->day < $card->closing_day) {
                        $month = $installmentDate->copy()->subMonth()->month;
                        $year = $installmentDate->copy()->subMonth()->year;
                    }

                    $this->invoiceService->getOrCreateInvoice($card, $month, $year);
                }
            }

            // Update each transaction in the group
            foreach ($groupTransactions as $transaction) {
                $updateData = [];
                
                // Update common fields
                if (isset($data['category_id'])) {
                    $updateData['category_id'] = $data['category_id'];
                }
                
                // Update description with installment suffix
                $updateData['description'] = $baseDescription . ' - Parcela ' . $transaction->installment_number . '/' . $installmentsTotal;
                
                if (isset($data['card_description'])) {
                    $updateData['card_description'] = $data['card_description'];
                }
                if (isset($data['debtor_id'])) {
                    $updateData['debtor_id'] = $data['debtor_id'];
                }
                
                // Update date for this installment (add months based on installment number - 1)
                if ($newBaseDate) {
                    $installmentDate = $newBaseDate->copy()->addMonths($transaction->installment_number - 1);
                    $updateData['transaction_date'] = $installmentDate->format('Y-m-d');
                }
                
                // Update card if changed
                if (isset($data['card_id'])) {
                    $updateData['card_id'] = $data['card_id'];
                }
                
                $transaction->update($updateData);
            }

            // Recalculate affected invoices
            // Recalculate old invoices first
            if ($oldCardId) {
                $oldCard = Card::find($oldCardId);
                if ($oldCard) {
                    foreach ($oldDates as $index => $oldDate) {
                        $oldCardForDate = $oldCardIds[$index] ? Card::find($oldCardIds[$index]) : $oldCard;
                        if ($oldCardForDate) {
                            $this->recalculateInvoiceForDate($oldCardForDate, $oldDate);
                        }
                    }
                }
            }
            
            // Recalculate new invoices
            if ($card) {
                $updatedGroup = $this->getInstallmentGroup($groupUuid, $userId);
                foreach ($updatedGroup as $transaction) {
                    $this->recalculateInvoiceForTransaction($transaction);
                }
            }

            return $this->getInstallmentGroup($groupUuid, $userId);
        });
    }

    /**
     * Delete all transactions in a group
     */
    public function deleteGroup(string $groupUuid, int $userId): bool
    {
        return DB::transaction(function () use ($groupUuid, $userId) {
            $groupTransactions = $this->getInstallmentGroup($groupUuid, $userId);
            
            if ($groupTransactions->isEmpty()) {
                throw new \Exception('Grupo de transações não encontrado.');
            }

            $firstTransaction = $groupTransactions->first();
            $cardId = $firstTransaction->card_id;
            $paymentMethod = $firstTransaction->payment_method;

            // Delete all transactions
            Transaction::where('group_uuid', $groupUuid)
                ->where('user_id', $userId)
                ->delete();

            // Recalculate affected invoices
            if ($paymentMethod === 'CREDIT' && $cardId) {
                $this->recalculateAffectedInvoices($cardId, $groupTransactions);
            }

            return true;
        });
    }

    /**
     * Recalculate invoice for a specific transaction
     */
    private function recalculateInvoiceForTransaction(Transaction $transaction): void
    {
        if (!$transaction->card_id) {
            return;
        }

        $card = $transaction->card;
        $dateCarbon = Carbon::parse($transaction->transaction_date);
        $this->recalculateInvoiceForDate($card, $dateCarbon);
    }

    /**
     * Recalculate invoice for a specific date
     */
    private function recalculateInvoiceForDate(Card $card, Carbon $date): void
    {
        $now = Carbon::now();
        $month = $date->month;
        $year = $date->year;

        // Determine which cycle this transaction belongs to
        if ($date->day < $card->closing_day) {
            // Transaction is in the previous cycle
            $month = $date->copy()->subMonth()->month;
            $year = $date->copy()->subMonth()->year;
        }

        $invoice = $this->invoiceService->getOrCreateInvoice($card, $month, $year);
        $this->invoiceService->recalculateInvoice($invoice);
    }

    /**
     * Recalculate all affected invoices for a collection of transactions
     */
    private function recalculateAffectedInvoices(?int $cardId, Collection $transactions): void
    {
        if (!$cardId || $transactions->isEmpty()) {
            return;
        }

        $card = Card::find($cardId);
        if (!$card) {
            return;
        }

        // Get unique months/years from transactions
        $affectedCycles = $transactions->map(function ($transaction) use ($card) {
            $dateCarbon = Carbon::parse($transaction->transaction_date);
            $month = $dateCarbon->month;
            $year = $dateCarbon->year;

            if ($dateCarbon->day < $card->closing_day) {
                $month = $dateCarbon->copy()->subMonth()->month;
                $year = $dateCarbon->copy()->subMonth()->year;
            }

            return ['month' => $month, 'year' => $year];
        })->unique(function ($cycle) {
            return $cycle['month'] . '-' . $cycle['year'];
        });

        // Recalculate each affected invoice
        foreach ($affectedCycles as $cycle) {
            $invoice = $this->invoiceService->getOrCreateInvoice($card, $cycle['month'], $cycle['year']);
            $this->invoiceService->recalculateInvoice($invoice);
        }
    }
}
