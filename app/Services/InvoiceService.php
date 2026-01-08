<?php

namespace App\Services;

use App\Models\Card;
use App\Models\Invoice;
use App\Models\Transaction;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Collection;

class InvoiceService
{
    /**
     * Get or create invoice for a card and cycle
     */
    public function getOrCreateInvoice(Card $card, int $month, int $year): Invoice
    {
        $invoice = Invoice::where('card_id', $card->id)
            ->where('cycle_month', $month)
            ->where('cycle_year', $year)
            ->first();

        if (!$invoice) {
            $invoice = $this->createInvoice($card, $month, $year);
        }

        return $invoice;
    }

    /**
     * Create invoice for a card and cycle
     */
    public function createInvoice(Card $card, int $month, int $year): Invoice
    {
        $cycleDates = $this->calculateCycleDates($card, $month, $year);

        $totalAmount = $this->calculateInvoiceTotal($card, $cycleDates['start'], $cycleDates['end']);

        return Invoice::create([
            'user_id' => $card->user_id,
            'card_id' => $card->id,
            'cycle_month' => $month,
            'cycle_year' => $year,
            'closing_date' => $cycleDates['closing'],
            'due_date' => $cycleDates['due'],
            'total_amount' => $totalAmount,
            'paid_amount' => 0,
            'is_paid' => false,
        ]);
    }

    /**
     * Calculate cycle dates based on card's closing_day
     */
    public function calculateCycleDates(Card $card, int $month, int $year): array
    {
        $closingDay = $card->closing_day;
        $dueDay = $card->due_day;

        // Create the first day of the month to check last day
        $firstDayOfMonth = Carbon::create($year, $month, 1);
        $lastDayOfMonth = $firstDayOfMonth->copy()->endOfMonth()->day;
        
        // If closing_day doesn't exist in this month, use last day of month
        $actualClosingDay = min($closingDay, $lastDayOfMonth);
        
        // Closing date is the closing_day of the month (or last day if closing_day is too high)
        $closingDate = Carbon::create($year, $month, $actualClosingDay);

        // Start of cycle is closing_day + 1 of previous month
        $startDate = $closingDate->copy()->subMonth()->addDay();

        // End of cycle is closing_day of current month
        $endDate = $closingDate->copy();

        // Due date is due_day of next month (month after closing)
        // Calculate next month directly to avoid date overflow issues
        $nextMonth = $month + 1;
        $nextYear = $year;
        
        // Handle year rollover
        if ($nextMonth > 12) {
            $nextMonth = 1;
            $nextYear += 1;
        }
        
        // Get last day of next month to validate due_day
        $firstDayOfNextMonth = Carbon::create($nextYear, $nextMonth, 1);
        $lastDayOfNextMonth = $firstDayOfNextMonth->copy()->endOfMonth()->day;
        $actualDueDay = min($dueDay, $lastDayOfNextMonth);
        $dueDate = Carbon::create($nextYear, $nextMonth, $actualDueDay);

        return [
            'start' => $startDate,
            'end' => $endDate,
            'closing' => $closingDate,
            'due' => $dueDate,
        ];
    }

    /**
     * Calculate total amount for invoice based on transactions in cycle
     */
    public function calculateInvoiceTotal(Card $card, Carbon $startDate, Carbon $endDate): float
    {
        $total = Transaction::where('card_id', $card->id)
            ->where('payment_method', 'CREDIT')
            ->where('type', 'EXPENSE')
            ->whereBetween('transaction_date', [$startDate->format('Y-m-d'), $endDate->format('Y-m-d')])
            ->sum('amount');

        return (float) $total;
    }

    /**
     * Recalculate invoice total
     */
    public function recalculateInvoice(Invoice $invoice): Invoice
    {
        $card = $invoice->card;
        $cycleDates = $this->calculateCycleDates($card, $invoice->cycle_month, $invoice->cycle_year);

        $totalAmount = $this->calculateInvoiceTotal($card, $cycleDates['start'], $cycleDates['end']);

        $invoice->update([
            'total_amount' => $totalAmount,
            'closing_date' => $cycleDates['closing'],
            'due_date' => $cycleDates['due'],
        ]);

        return $invoice->fresh();
    }

    /**
     * Get invoices for a card (including future invoices up to 3 months ahead)
     */
    public function getInvoicesByCard(Card $card): Collection
    {
        $now = Carbon::now();
        $currentMonth = $now->month;
        $currentYear = $now->year;

        // Determine current cycle
        if ($now->day < $card->closing_day) {
            $currentMonth = $now->copy()->subMonth()->month;
            $currentYear = $now->copy()->subMonth()->year;
        }

        // Ensure current invoice exists
        $this->getOrCreateInvoice($card, $currentMonth, $currentYear);

        // Create future invoices (next 3 months from current cycle)
        // Calculate future months/years directly to avoid date overflow issues
        for ($i = 1; $i <= 3; $i++) {
            $futureMonth = $currentMonth + $i;
            $futureYear = $currentYear;
            
            // Handle year rollover
            while ($futureMonth > 12) {
                $futureMonth -= 12;
                $futureYear += 1;
            }
            
            $this->getOrCreateInvoice($card, $futureMonth, $futureYear);
        }

        // Return all invoices ordered by date (oldest first)
        return Invoice::where('card_id', $card->id)
            ->orderBy('cycle_year', 'asc')
            ->orderBy('cycle_month', 'asc')
            ->get();
    }

    /**
     * Get current cycle invoice for a card
     */
    public function getCurrentInvoice(Card $card): ?Invoice
    {
        $now = Carbon::now();
        $month = $now->month;
        $year = $now->year;

        // Check if we're before or after closing day
        if ($now->day < $card->closing_day) {
            // Still in previous cycle
            $month = $now->copy()->subMonth()->month;
            $year = $now->copy()->subMonth()->year;
        }

        return $this->getOrCreateInvoice($card, $month, $year);
    }

    /**
     * Mark invoice as paid
     */
    public function markAsPaid(Invoice $invoice, float $paidAmount = null): Invoice
    {
        $paidAmount = $paidAmount ?? $invoice->total_amount;

        $invoice->update([
            'is_paid' => true,
            'paid_amount' => $paidAmount,
            'paid_at' => now(),
        ]);

        return $invoice->fresh();
    }

    /**
     * Mark invoice as unpaid
     */
    public function markAsUnpaid(Invoice $invoice): Invoice
    {
        $invoice->update([
            'is_paid' => false,
            'paid_amount' => 0,
            'paid_at' => null,
        ]);

        return $invoice->fresh();
    }

    /**
     * Get available credit for a card (limit - current cycle expenses)
     */
    public function getAvailableCredit(Card $card): float
    {
        $currentInvoice = $this->getCurrentInvoice($card);
        $used = $currentInvoice->total_amount;

        return max(0, $card->credit_limit - $used);
    }
}
