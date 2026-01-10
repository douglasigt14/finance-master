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
     * @param int $month Month of closing date
     * @param int $year Year of closing date
     */
    public function getOrCreateInvoice(Card $card, int $month, int $year): Invoice
    {
        // Calculate dates to get the due date month/year
        $cycleDates = $this->calculateCycleDates($card, $month, $year);
        $dueMonth = $cycleDates['due']->month;
        $dueYear = $cycleDates['due']->year;

        // Search by due date month/year (which is stored in cycle_month/cycle_year)
        $invoice = Invoice::where('card_id', $card->id)
            ->where('cycle_month', $dueMonth)
            ->where('cycle_year', $dueYear)
            ->first();

        if (!$invoice) {
            $invoice = $this->createInvoice($card, $month, $year);
        }

        return $invoice;
    }

    /**
     * Get invoice by due date month/year (cycle_month/cycle_year)
     */
    public function getInvoiceByDueMonth(Card $card, int $dueMonth, int $dueYear): ?Invoice
    {
        return Invoice::where('card_id', $card->id)
            ->where('cycle_month', $dueMonth)
            ->where('cycle_year', $dueYear)
            ->first();
    }

    /**
     * Create invoice for a card and cycle
     */
    public function createInvoice(Card $card, int $month, int $year): Invoice
    {
        $cycleDates = $this->calculateCycleDates($card, $month, $year);

        $totalAmount = $this->calculateInvoiceTotal($card, $cycleDates['start'], $cycleDates['end']);

        // Use due date month/year for cycle identification (not closing date)
        $dueMonth = $cycleDates['due']->month;
        $dueYear = $cycleDates['due']->year;

        return Invoice::create([
            'user_id' => $card->user_id,
            'card_id' => $card->id,
            'cycle_month' => $dueMonth,
            'cycle_year' => $dueYear,
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

        // Start of cycle is closing_day of previous month (not closing_day + 1)
        // We need to calculate this carefully to handle month boundaries
        $previousMonth = $month - 1;
        $previousYear = $year;
        if ($previousMonth < 1) {
            $previousMonth = 12;
            $previousYear -= 1;
        }
        
        // Get the last day of the previous month
        $firstDayOfPreviousMonth = Carbon::create($previousYear, $previousMonth, 1);
        $lastDayOfPreviousMonth = $firstDayOfPreviousMonth->copy()->endOfMonth()->day;
        
        // Calculate the start day: closing_day, but ensure it doesn't exceed the last day of previous month
        $startDay = min($closingDay, $lastDayOfPreviousMonth);
        $startDate = Carbon::create($previousYear, $previousMonth, $startDay);

        // End of cycle is closing_day - 1 of current month (not the closing_day itself)
        $endDate = $closingDate->copy()->subDay();

        // Determine if due date is in the same month or next month
        // If due_day > closing_day, due date can be in the same month (e.g., fecha 07/01, vence 10/01)
        // If due_day <= closing_day, due date is in the next month (e.g., fecha 29/12, vence 08/01)
        $dueMonth = $month;
        $dueYear = $year;
        
        if ($dueDay <= $closingDay) {
            // Due date is in the next month (because if it were same month, it would have already passed)
            $dueMonth = $month + 1;
            $dueYear = $year;
            
            // Handle year rollover
            if ($dueMonth > 12) {
                $dueMonth = 1;
                $dueYear += 1;
            }
        }
        // If dueDay > closingDay, due date is in the same month (dueMonth and dueYear remain unchanged)
        
        // Get last day of due month to validate due_day
        $firstDayOfDueMonth = Carbon::create($dueYear, $dueMonth, 1);
        $lastDayOfDueMonth = $firstDayOfDueMonth->copy()->endOfMonth()->day;
        $actualDueDay = min($dueDay, $lastDayOfDueMonth);
        $dueDate = Carbon::create($dueYear, $dueMonth, $actualDueDay);

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
        
        // Get closing month/year from the invoice's closing_date to recalculate
        $closingDate = Carbon::parse($invoice->closing_date);
        $closingMonth = $closingDate->month;
        $closingYear = $closingDate->year;
        
        $cycleDates = $this->calculateCycleDates($card, $closingMonth, $closingYear);

        $totalAmount = $this->calculateInvoiceTotal($card, $cycleDates['start'], $cycleDates['end']);

        // Update cycle_month and cycle_year based on due date
        $dueMonth = $cycleDates['due']->month;
        $dueYear = $cycleDates['due']->year;

        // Check if another invoice already exists with this cycle_month/cycle_year
        $existingInvoice = Invoice::where('card_id', $card->id)
            ->where('cycle_month', $dueMonth)
            ->where('cycle_year', $dueYear)
            ->where('id', '!=', $invoice->id)
            ->first();

        if ($existingInvoice) {
            // If duplicate exists, recalculate the existing one's total and delete the duplicate
            $existingCycleDates = $this->calculateCycleDates($card, $existingInvoice->closing_date->month, $existingInvoice->closing_date->year);
            $existingTotal = $this->calculateInvoiceTotal($card, $existingCycleDates['start'], $existingCycleDates['end']);
            
            $existingInvoice->update([
                'total_amount' => $existingTotal,
                'closing_date' => $existingCycleDates['closing'],
                'due_date' => $existingCycleDates['due'],
            ]);
            // Delete the duplicate
            $invoice->delete();
            return $existingInvoice->fresh();
        }

        $invoice->update([
            'cycle_month' => $dueMonth,
            'cycle_year' => $dueYear,
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

        // Determine current cycle (based on closing day)
        if ($now->day < $card->closing_day) {
            $currentMonth = $now->copy()->subMonth()->month;
            $currentYear = $now->copy()->subMonth()->year;
        }

        // Ensure current invoice exists (this will create with due date month/year)
        $this->getOrCreateInvoice($card, $currentMonth, $currentYear);

        // Create future invoices (next 3 months from current closing month)
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

        // Return all invoices ordered by due date (oldest first)
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
