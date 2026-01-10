<?php

namespace App\Services;

use App\DTOs\CreateRecurringTransactionDTO;
use App\DTOs\CreateTransactionDTO;
use App\DTOs\UpdateRecurringTransactionDTO;
use App\Models\RecurringTransaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

class RecurringTransactionService
{
    public function __construct(
        private TransactionService $transactionService
    ) {
    }

    public function getAllByUser(int $userId, ?string $search = null): Collection
    {
        $query = RecurringTransaction::where('user_id', $userId)
            ->with(['category', 'card', 'debtor']);

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('description', 'like', "%{$search}%")
                    ->orWhere('card_description', 'like', "%{$search}%")
                    ->orWhereHas('category', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    })
                    ->orWhereHas('card', function ($q) use ($search) {
                        $q->where('name', 'like', "%{$search}%");
                    });
            });
        }

        return $query->orderBy('next_execution_date', 'asc')->get();
    }

    public function getById(int $recurringTransactionId, int $userId): ?RecurringTransaction
    {
        return RecurringTransaction::where('id', $recurringTransactionId)
            ->where('user_id', $userId)
            ->with(['category', 'card', 'debtor'])
            ->first();
    }

    public function create(CreateRecurringTransactionDTO $dto): RecurringTransaction
    {
        $startDate = Carbon::parse($dto->startDate)->startOfDay();
        $now = Carbon::now()->startOfDay();
        
        // Calculate next execution date from start date
        $nextExecutionDate = $this->calculateNextExecutionDate(
            $dto->startDate,
            $dto->frequency,
            $dto->dayOfMonth
        );

        $recurringTransaction = RecurringTransaction::create([
            'user_id' => $dto->userId,
            'category_id' => $dto->categoryId,
            'card_id' => $dto->cardId,
            'debtor_id' => $dto->debtorId,
            'type' => $dto->type,
            'payment_method' => $dto->paymentMethod,
            'amount' => $dto->amount,
            'description' => $dto->description,
            'card_description' => $dto->cardDescription,
            'frequency' => $dto->frequency,
            'day_of_month' => $dto->dayOfMonth,
            'start_date' => $dto->startDate,
            'end_date' => $dto->endDate,
            'next_execution_date' => $nextExecutionDate,
            'is_active' => true,
        ]);

        // If the start date is today or in the past, generate the first transaction immediately
        // Use the start date as the transaction date, not the calculated next execution date
        if ($startDate->lte($now)) {
            try {
                DB::transaction(function () use ($recurringTransaction, $startDate) {
                    // Create transaction from recurring template using start date
                    $transactionDTO = CreateTransactionDTO::fromArray([
                        'user_id' => $recurringTransaction->user_id,
                        'category_id' => $recurringTransaction->category_id,
                        'type' => $recurringTransaction->type,
                        'amount' => $recurringTransaction->amount,
                        'transaction_date' => $startDate->format('Y-m-d'),
                        'card_id' => $recurringTransaction->card_id,
                        'payment_method' => $recurringTransaction->payment_method,
                        'description' => $recurringTransaction->description,
                        'card_description' => $recurringTransaction->card_description,
                        'debtor_id' => $recurringTransaction->debtor_id,
                        'installments_total' => 1,
                    ]);

                    $this->transactionService->create($transactionDTO);

                    // Calculate next execution date by adding one period to the start date
                    $nextExecutionDate = $this->calculateNextExecutionDateFromDate(
                        $startDate,
                        $recurringTransaction->frequency,
                        $recurringTransaction->day_of_month
                    );
                    $recurringTransaction->next_execution_date = $nextExecutionDate;
                    $recurringTransaction->save();
                });
            } catch (\Exception $e) {
                \Log::error("Failed to generate first transaction for recurring transaction {$recurringTransaction->id}: " . $e->getMessage());
            }
        }

        return $recurringTransaction->fresh();
    }

    public function update(RecurringTransaction $recurringTransaction, UpdateRecurringTransactionDTO $dto): RecurringTransaction
    {
        $updateData = $dto->toArray();

        // If frequency, day_of_month, or start_date changed, recalculate next_execution_date
        if (isset($updateData['frequency']) || isset($updateData['day_of_month']) || isset($updateData['start_date'])) {
            $frequency = $updateData['frequency'] ?? $recurringTransaction->frequency;
            $dayOfMonth = $updateData['day_of_month'] ?? $recurringTransaction->day_of_month;
            $startDate = $updateData['start_date'] ?? $recurringTransaction->start_date;

            $updateData['next_execution_date'] = $this->calculateNextExecutionDate(
                $startDate,
                $frequency,
                $dayOfMonth
            );
        }

        $recurringTransaction->update($updateData);

        return $recurringTransaction->fresh();
    }

    public function delete(RecurringTransaction $recurringTransaction): void
    {
        $recurringTransaction->delete();
    }

    public function generateTransactions(): int
    {
        // Get transactions ready to execute (normal flow)
        $recurringTransactions = RecurringTransaction::readyToExecute()->get();
        
        // Also get transactions that should have generated their first transaction
        // (start_date in the past but next_execution_date in the future)
        $pendingFirstExecution = RecurringTransaction::pendingFirstExecution()->get();
        
        // Merge and remove duplicates
        $allRecurringTransactions = $recurringTransactions->merge($pendingFirstExecution)->unique('id');
        
        $generatedCount = 0;

        foreach ($allRecurringTransactions as $recurring) {
            try {
                DB::transaction(function () use ($recurring, &$generatedCount) {
                    $startDate = Carbon::parse($recurring->start_date)->startOfDay();
                    $now = Carbon::now()->startOfDay();
                    $nextExecutionDate = Carbon::parse($recurring->next_execution_date)->startOfDay();
                    
                    // Determine which date to use for the transaction
                    // If start_date is in the past and next_execution_date is in the future,
                    // this is the first transaction, so use start_date
                    $transactionDate = $startDate;
                    if ($startDate->lte($now) && $nextExecutionDate->gt($now)) {
                        // First transaction - use start_date
                        $transactionDate = $startDate;
                    } else {
                        // Normal flow - use next_execution_date
                        $transactionDate = $nextExecutionDate;
                    }
                    
                    // Check if transaction already exists to avoid duplicates
                    $existingTransaction = \App\Models\Transaction::where('user_id', $recurring->user_id)
                        ->where('transaction_date', $transactionDate->format('Y-m-d'))
                        ->where('card_id', $recurring->card_id)
                        ->where('amount', $recurring->amount)
                        ->where('type', $recurring->type)
                        ->where('category_id', $recurring->category_id)
                        ->where(function ($query) use ($recurring) {
                            if ($recurring->card_description) {
                                $query->where('card_description', $recurring->card_description);
                            } else {
                                $query->whereNull('card_description');
                            }
                        })
                        ->where(function ($query) use ($recurring) {
                            if ($recurring->description) {
                                $query->where('description', $recurring->description);
                            } else {
                                $query->whereNull('description');
                            }
                        })
                        ->first();
                    
                    // Skip if transaction already exists
                    if ($existingTransaction) {
                        // Update next execution date even if transaction already exists
                        // to prevent getting stuck
                        if ($startDate->lte($now) && $nextExecutionDate->gt($now)) {
                            $recurring->next_execution_date = $this->calculateNextExecutionDateFromDate(
                                $startDate,
                                $recurring->frequency,
                                $recurring->day_of_month
                            );
                        } else {
                            $recurring->next_execution_date = $this->calculateNextExecutionDateFromDate(
                                $nextExecutionDate,
                                $recurring->frequency,
                                $recurring->day_of_month
                            );
                        }
                        $recurring->save();
                        return; // Skip creating duplicate
                    }
                    
                    // Create transaction from recurring template
                    $transactionDTO = CreateTransactionDTO::fromArray([
                        'user_id' => $recurring->user_id,
                        'category_id' => $recurring->category_id,
                        'type' => $recurring->type,
                        'amount' => $recurring->amount,
                        'transaction_date' => $transactionDate->format('Y-m-d'),
                        'card_id' => $recurring->card_id,
                        'payment_method' => $recurring->payment_method,
                        'description' => $recurring->description,
                        'card_description' => $recurring->card_description,
                        'debtor_id' => $recurring->debtor_id,
                        'installments_total' => 1,
                    ]);

                    $this->transactionService->create($transactionDTO);

                    // Update next execution date
                    if ($startDate->lte($now) && $nextExecutionDate->gt($now)) {
                        // First transaction - calculate next from start_date
                        $recurring->next_execution_date = $this->calculateNextExecutionDateFromDate(
                            $startDate,
                            $recurring->frequency,
                            $recurring->day_of_month
                        );
                    } else {
                        // Normal flow - add one period to current execution date
                        $recurring->next_execution_date = $this->calculateNextExecutionDateFromDate(
                            $nextExecutionDate,
                            $recurring->frequency,
                            $recurring->day_of_month
                        );
                    }

                    // Deactivate if end_date reached
                    if ($recurring->end_date && $recurring->next_execution_date->gt($recurring->end_date)) {
                        $recurring->is_active = false;
                    }

                    $recurring->save();
                    $generatedCount++;
                });
            } catch (\Exception $e) {
                // Log error but continue with other recurring transactions
                \Log::error("Failed to generate transaction for recurring transaction {$recurring->id}: " . $e->getMessage());
            }
        }

        return $generatedCount;
    }

    private function calculateNextExecutionDate(string $startDate, string $frequency, int $dayOfMonth): Carbon
    {
        $start = Carbon::parse($startDate)->startOfDay();
        $now = Carbon::now()->startOfDay();

        switch ($frequency) {
            case 'WEEKLY':
                // For weekly, use the day of week from start_date
                $next = $now->copy();
                while ($next->dayOfWeek !== $start->dayOfWeek || $next->lte($now)) {
                    $next->addDay();
                }
                return $next->startOfDay();

            case 'MONTHLY':
                // For monthly, use day_of_month
                $next = $now->copy();
                $targetDay = min($dayOfMonth, $next->daysInMonth);
                $next->day($targetDay);

                // If the day has passed this month, move to next month
                if ($next->lte($now)) {
                    $next->addMonth();
                    $targetDay = min($dayOfMonth, $next->daysInMonth);
                    $next->day($targetDay);
                }

                return $next->startOfDay();

            case 'YEARLY':
                // For yearly, use the same month and day as start_date
                $next = $now->copy();
                $next->month($start->month);
                $targetDay = min($start->day, $next->daysInMonth);
                $next->day($targetDay);

                // If the date has passed this year, move to next year
                if ($next->lte($now)) {
                    $next->addYear();
                    $targetDay = min($start->day, $next->daysInMonth);
                    $next->day($targetDay);
                }

                return $next->startOfDay();

            default:
                return $now->copy()->addDay()->startOfDay();
        }
    }

    /**
     * Calculate next execution date by adding one period to a given date
     * Used when generating the first transaction immediately
     */
    private function calculateNextExecutionDateFromDate(Carbon $date, string $frequency, int $dayOfMonth): Carbon
    {
        $next = $date->copy();

        switch ($frequency) {
            case 'WEEKLY':
                $next->addWeek();
                return $next->startOfDay();

            case 'MONTHLY':
                $next->addMonth();
                $targetDay = min($dayOfMonth, $next->daysInMonth);
                $next->day($targetDay);
                return $next->startOfDay();

            case 'YEARLY':
                $next->addYear();
                $targetDay = min($date->day, $next->daysInMonth);
                $next->day($targetDay);
                return $next->startOfDay();

            default:
                return $next->addDay()->startOfDay();
        }
    }
}
