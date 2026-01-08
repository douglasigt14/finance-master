<?php

namespace App\Console\Commands;

use App\Models\Invoice;
use App\Services\InvoiceService;
use Illuminate\Console\Command;

class RecalculateInvoices extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'invoices:recalculate {--card-id= : Recalculate invoices for a specific card ID}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Recalculate all invoices with correct dates and amounts';

    /**
     * Execute the console command.
     */
    public function handle(InvoiceService $invoiceService): int
    {
        $cardId = $this->option('card-id');

        $query = Invoice::query();
        if ($cardId) {
            $query->where('card_id', $cardId);
        }

        $invoices = $query->get();

        if ($invoices->isEmpty()) {
            $this->info('No invoices found to recalculate.');
            return Command::SUCCESS;
        }

        $this->info("Recalculating {$invoices->count()} invoice(s)...");

        $bar = $this->output->createProgressBar($invoices->count());
        $bar->start();

        foreach ($invoices as $invoice) {
            try {
                $invoiceService->recalculateInvoice($invoice);
                $bar->advance();
            } catch (\Exception $e) {
                $this->newLine();
                $this->error("Error recalculating invoice {$invoice->id}: {$e->getMessage()}");
            }
        }

        $bar->finish();
        $this->newLine();
        $this->info('All invoices recalculated successfully!');

        return Command::SUCCESS;
    }
}
