<?php

namespace App\Console\Commands;

use App\Services\RecurringTransactionService;
use Illuminate\Console\Command;

class GenerateRecurringTransactions extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'recurring-transactions:generate';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Generate transactions from active recurring transactions';

    /**
     * Execute the console command.
     */
    public function handle(RecurringTransactionService $service): int
    {
        $this->info('Generating recurring transactions...');

        $count = $service->generateTransactions();

        if ($count > 0) {
            $this->info("Successfully generated {$count} transaction(s).");
        } else {
            $this->info('No transactions to generate.');
        }

        return Command::SUCCESS;
    }
}
