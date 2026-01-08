<?php

namespace Database\Seeders;

use App\Models\Card;
use App\Models\Category;
use App\Models\Transaction;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class TransactionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $user = User::where('email', 'demo@finance.com')->first();

        if (!$user) {
            $this->command->warn('User not found. Please run UserSeeder first.');
            return;
        }

        $cards = Card::where('user_id', $user->id)->get();
        $categories = Category::where('user_id', $user->id)->get();

        if ($cards->isEmpty() || $categories->isEmpty()) {
            $this->command->warn('Cards or Categories not found. Please run CardSeeder and CategorySeeder first.');
            return;
        }

        $incomeCategories = $categories->where('type', 'INCOME');
        $expenseCategories = $categories->where('type', 'EXPENSE');

        // Create some income transactions
        $this->createIncomeTransactions($user, $incomeCategories);

        // Create expense transactions (cash, pix, debit)
        $this->createExpenseTransactions($user, $expenseCategories);

        // Create credit card transactions with installments
        $this->createCreditTransactions($user, $expenseCategories, $cards);
    }

    private function createIncomeTransactions(User $user, $categories): void
    {
        $now = now();

        // Salary - monthly
        Transaction::create([
            'user_id' => $user->id,
            'category_id' => $categories->where('name', 'Salary')->first()->id,
            'card_id' => null,
            'type' => 'INCOME',
            'payment_method' => null,
            'amount' => 5000.00,
            'description' => 'Monthly salary',
            'transaction_date' => $now->copy()->startOfMonth(),
            'installments_total' => 1,
            'installment_number' => 1,
            'group_uuid' => null,
            'is_paid' => true,
        ]);

        // Freelance - this month
        Transaction::create([
            'user_id' => $user->id,
            'category_id' => $categories->where('name', 'Freelance')->first()->id,
            'card_id' => null,
            'type' => 'INCOME',
            'payment_method' => null,
            'amount' => 1200.00,
            'description' => 'Freelance project payment',
            'transaction_date' => $now->copy()->subDays(5),
            'installments_total' => 1,
            'installment_number' => 1,
            'group_uuid' => null,
            'is_paid' => true,
        ]);
    }

    private function createExpenseTransactions(User $user, $categories): void
    {
        $now = now();

        // Food - PIX
        Transaction::create([
            'user_id' => $user->id,
            'category_id' => $categories->where('name', 'Food')->first()->id,
            'card_id' => null,
            'type' => 'EXPENSE',
            'payment_method' => 'PIX',
            'amount' => 85.50,
            'description' => 'Supermarket shopping',
            'transaction_date' => $now->copy()->subDays(2),
            'installments_total' => 1,
            'installment_number' => 1,
            'group_uuid' => null,
            'is_paid' => true,
        ]);

        // Transport - Cash
        Transaction::create([
            'user_id' => $user->id,
            'category_id' => $categories->where('name', 'Transport')->first()->id,
            'card_id' => null,
            'type' => 'EXPENSE',
            'payment_method' => 'CASH',
            'amount' => 45.00,
            'description' => 'Uber ride',
            'transaction_date' => $now->copy()->subDays(1),
            'installments_total' => 1,
            'installment_number' => 1,
            'group_uuid' => null,
            'is_paid' => true,
        ]);

        // Bills - DEBIT
        Transaction::create([
            'user_id' => $user->id,
            'category_id' => $categories->where('name', 'Bills')->first()->id,
            'card_id' => null,
            'type' => 'EXPENSE',
            'payment_method' => 'DEBIT',
            'amount' => 350.00,
            'description' => 'Electricity bill',
            'transaction_date' => $now->copy()->subDays(3),
            'installments_total' => 1,
            'installment_number' => 1,
            'group_uuid' => null,
            'is_paid' => true,
        ]);
    }

    private function createCreditTransactions(User $user, $categories, $cards): void
    {
        $now = now();
        $nubankCard = $cards->where('name', 'Nubank')->first();
        $itauCard = $cards->where('name', 'Itaú')->first();

        // Shopping - 3 installments on Nubank
        $groupUuid1 = Str::uuid()->toString();
        $installmentAmount1 = 600.00 / 3; // 200.00 per installment

        for ($i = 1; $i <= 3; $i++) {
            Transaction::create([
                'user_id' => $user->id,
                'category_id' => $categories->where('name', 'Shopping')->first()->id,
                'card_id' => $nubankCard->id,
                'type' => 'EXPENSE',
                'payment_method' => 'CREDIT',
                'amount' => $installmentAmount1,
                'description' => 'Electronics purchase - Installment ' . $i . '/3',
                'transaction_date' => $now->copy()->subDays(10)->addMonths($i - 1),
                'installments_total' => 3,
                'installment_number' => $i,
                'group_uuid' => $groupUuid1,
                'is_paid' => $i === 1, // First installment paid
            ]);
        }

        // Health - 6 installments on Itaú
        $groupUuid2 = Str::uuid()->toString();
        $installmentAmount2 = 1200.00 / 6; // 200.00 per installment

        for ($i = 1; $i <= 6; $i++) {
            Transaction::create([
                'user_id' => $user->id,
                'category_id' => $categories->where('name', 'Health')->first()->id,
                'card_id' => $itauCard->id,
                'type' => 'EXPENSE',
                'payment_method' => 'CREDIT',
                'amount' => $installmentAmount2,
                'description' => 'Dental treatment - Installment ' . $i . '/6',
                'transaction_date' => $now->copy()->subDays(15)->addMonths($i - 1),
                'installments_total' => 6,
                'installment_number' => $i,
                'group_uuid' => $groupUuid2,
                'is_paid' => $i <= 2, // First 2 installments paid
            ]);
        }

        // Entertainment - single payment on Nubank
        Transaction::create([
            'user_id' => $user->id,
            'category_id' => $categories->where('name', 'Entertainment')->first()->id,
            'card_id' => $nubankCard->id,
            'type' => 'EXPENSE',
            'payment_method' => 'CREDIT',
            'amount' => 150.00,
            'description' => 'Concert tickets',
            'transaction_date' => $now->copy()->subDays(7),
            'installments_total' => 1,
            'installment_number' => 1,
            'group_uuid' => null,
            'is_paid' => false,
        ]);

        // Education - 12 installments on Itaú
        $groupUuid3 = Str::uuid()->toString();
        $installmentAmount3 = 2400.00 / 12; // 200.00 per installment

        for ($i = 1; $i <= 12; $i++) {
            Transaction::create([
                'user_id' => $user->id,
                'category_id' => $categories->where('name', 'Education')->first()->id,
                'card_id' => $itauCard->id,
                'type' => 'EXPENSE',
                'payment_method' => 'CREDIT',
                'amount' => $installmentAmount3,
                'description' => 'Online course - Installment ' . $i . '/12',
                'transaction_date' => $now->copy()->subDays(20)->addMonths($i - 1),
                'installments_total' => 12,
                'installment_number' => $i,
                'group_uuid' => $groupUuid3,
                'is_paid' => false, // None paid yet
            ]);
        }
    }
}
