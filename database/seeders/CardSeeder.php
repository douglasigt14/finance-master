<?php

namespace Database\Seeders;

use App\Models\Card;
use App\Models\User;
use Illuminate\Database\Seeder;

class CardSeeder extends Seeder
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

        // Card 1: Nubank
        Card::firstOrCreate(
            [
                'user_id' => $user->id,
                'name' => 'Nubank',
            ],
            [
                'user_id' => $user->id,
                'name' => 'Nubank',
                'brand' => 'MASTERCARD',
                'last_four' => '1234',
                'credit_limit' => 5000.00,
                'closing_day' => 10,
                'due_day' => 17,
                'status' => 'active',
            ]
        );

        // Card 2: Itaú
        Card::firstOrCreate(
            [
                'user_id' => $user->id,
                'name' => 'Itaú',
            ],
            [
                'user_id' => $user->id,
                'name' => 'Itaú',
                'brand' => 'VISA',
                'last_four' => '5678',
                'credit_limit' => 8000.00,
                'closing_day' => 5,
                'due_day' => 12,
                'status' => 'active',
            ]
        );
    }
}
