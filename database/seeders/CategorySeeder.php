<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\User;
use Illuminate\Database\Seeder;

class CategorySeeder extends Seeder
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

        // Income Categories
        $incomeCategories = [
            ['name' => 'Salary', 'type' => 'INCOME', 'color' => '#10b981'],
            ['name' => 'Freelance', 'type' => 'INCOME', 'color' => '#3b82f6'],
            ['name' => 'Investment', 'type' => 'INCOME', 'color' => '#8b5cf6'],
            ['name' => 'Bonus', 'type' => 'INCOME', 'color' => '#06b6d4'],
        ];

        // Expense Categories
        $expenseCategories = [
            ['name' => 'Food', 'type' => 'EXPENSE', 'color' => '#ef4444'],
            ['name' => 'Transport', 'type' => 'EXPENSE', 'color' => '#f59e0b'],
            ['name' => 'Shopping', 'type' => 'EXPENSE', 'color' => '#ec4899'],
            ['name' => 'Bills', 'type' => 'EXPENSE', 'color' => '#6366f1'],
            ['name' => 'Entertainment', 'type' => 'EXPENSE', 'color' => '#14b8a6'],
            ['name' => 'Health', 'type' => 'EXPENSE', 'color' => '#f97316'],
            ['name' => 'Education', 'type' => 'EXPENSE', 'color' => '#06b6d4'],
        ];

        foreach ($incomeCategories as $category) {
            Category::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'name' => $category['name'],
                    'type' => $category['type'],
                ],
                array_merge($category, ['user_id' => $user->id])
            );
        }

        foreach ($expenseCategories as $category) {
            Category::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'name' => $category['name'],
                    'type' => $category['type'],
                ],
                array_merge($category, ['user_id' => $user->id])
            );
        }
    }
}
