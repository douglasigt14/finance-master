<?php

namespace App\Http\Controllers;

use App\Models\Card;
use App\Models\Category;
use App\Services\CardService;
use App\Services\InvoiceService;
use Illuminate\Http\Request;

class InvoicesController extends Controller
{
    public function __construct(
        private CardService $cardService,
        private InvoiceService $invoiceService
    ) {
    }

    /**
     * Display invoices for a card
     */
    public function index(Request $request, ?string $cardId = null)
    {
        $user = $request->user();
        $cards = $this->cardService->getAllByUser($user->id);

        if ($cards->isEmpty()) {
            return redirect()->route('cards.create')
                ->with('info', 'Por favor, crie um cartão primeiro para visualizar as faturas.');
        }

        // Get selected card from query or route parameter or first card
        $requestCardId = $request->query('card_id') ?? $cardId;
        $selectedCard = $requestCardId 
            ? $this->cardService->getById((int) $requestCardId, $user->id)
            : $cards->first();

        if (!$selectedCard) {
            $selectedCard = $cards->first();
        }

        // Get all invoices for the card
        $invoices = $this->invoiceService->getInvoicesByCard($selectedCard);

        // Get current invoice (for summary calculations)
        $currentInvoice = $this->invoiceService->getCurrentInvoice($selectedCard);
        $availableCredit = $this->invoiceService->getAvailableCredit($selectedCard);

        // Get selected invoice from query or use first unpaid invoice from list (oldest)
        $selectedMonth = $request->query('month');
        $selectedYear = $request->query('year');
        
        if ($selectedMonth && $selectedYear) {
            // Query parameters are now due date month/year (cycle_month/cycle_year)
            $selectedInvoice = $this->invoiceService->getInvoiceByDueMonth($selectedCard, (int) $selectedMonth, (int) $selectedYear);
            
            if (!$selectedInvoice) {
                // If not found, try to find by closing month (for backward compatibility)
                $selectedInvoice = $this->invoiceService->getOrCreateInvoice($selectedCard, (int) $selectedMonth, (int) $selectedYear);
            }
        } else {
            // Use first unpaid invoice from list (oldest, which is first in the ordered list)
            $selectedInvoice = $invoices->where('is_paid', false)->first();
            
            // If no unpaid invoices exist, use first invoice from list
            if (!$selectedInvoice) {
                $selectedInvoice = $invoices->first();
            }
            
            // If still no invoices exist, fallback to current invoice
            if (!$selectedInvoice) {
                $selectedInvoice = $currentInvoice;
            }
        }
        
        // Use closing_date to calculate cycle dates (not cycle_month/cycle_year which is now based on due date)
        $closingDate = \Carbon\Carbon::parse($selectedInvoice->closing_date);
        $cycleDates = $this->invoiceService->calculateCycleDates($selectedCard, $closingDate->month, $closingDate->year);
        
        $transactions = $selectedCard->transactions()
            ->where('payment_method', 'CREDIT')
            ->where('type', 'EXPENSE')
            ->whereBetween('transaction_date', [
                $cycleDates['start']->format('Y-m-d'),
                $cycleDates['end']->format('Y-m-d')
            ])
            ->with(['category', 'debtor'])
            ->orderBy('transaction_date')
            ->get();

        // Get data for transaction modal
        $categories = Category::where('user_id', $user->id)->orderBy('name')->get();
        $allCards = Card::where('user_id', $user->id)->active()->orderBy('name')->get();
        $debtors = \App\Models\Debtor::where('user_id', $user->id)->orderBy('name')->get();

        return view('invoices.index', compact(
            'cards',
            'selectedCard',
            'invoices',
            'currentInvoice',
            'availableCredit',
            'selectedInvoice',
            'transactions',
            'cycleDates',
            'categories',
            'allCards',
            'debtors'
        ));
    }

    /**
     * Show invoice details
     * @param int $month Month of due date (cycle_month)
     * @param int $year Year of due date (cycle_year)
     */
    public function show(Request $request, string $cardId, int $month, int $year)
    {
        $card = $this->cardService->getById((int) $cardId, $request->user()->id);

        if (!$card) {
            abort(404);
        }

        // Get invoice by due date month/year (cycle_month/cycle_year)
        $invoice = $this->invoiceService->getInvoiceByDueMonth($card, $month, $year);

        if (!$invoice) {
            abort(404, 'Fatura não encontrada.');
        }

        // Get transactions for this invoice cycle using closing_date
        $closingDate = \Carbon\Carbon::parse($invoice->closing_date);
        $cycleDates = $this->invoiceService->calculateCycleDates($card, $closingDate->month, $closingDate->year);
        
        $transactions = $card->transactions()
            ->where('payment_method', 'CREDIT')
            ->where('type', 'EXPENSE')
            ->whereBetween('transaction_date', [
                $cycleDates['start']->format('Y-m-d'),
                $cycleDates['end']->format('Y-m-d')
            ])
            ->with(['category', 'debtor'])
            ->orderBy('transaction_date')
            ->get();

        // If it's an AJAX request, return only the details partial
        if ($request->ajax()) {
            return view('invoices.partials.details', compact('card', 'invoice', 'transactions', 'cycleDates'))->render();
        }

        return view('invoices.show', compact('card', 'invoice', 'transactions', 'cycleDates'));
    }

    /**
     * Mark invoice as paid
     * @param int $month Month of due date (cycle_month)
     * @param int $year Year of due date (cycle_year)
     */
    public function markAsPaid(Request $request, string $cardId, int $month, int $year)
    {
        $card = $this->cardService->getById((int) $cardId, $request->user()->id);

        if (!$card) {
            abort(404);
        }

        $invoice = $this->invoiceService->getInvoiceByDueMonth($card, $month, $year);

        if (!$invoice) {
            abort(404, 'Fatura não encontrada.');
        }

        $this->invoiceService->markAsPaid($invoice);

        return redirect()->back()
            ->with('success', 'Fatura marcada como paga.');
    }

    /**
     * Mark invoice as unpaid
     * @param int $month Month of due date (cycle_month)
     * @param int $year Year of due date (cycle_year)
     */
    public function markAsUnpaid(Request $request, string $cardId, int $month, int $year)
    {
        $card = $this->cardService->getById((int) $cardId, $request->user()->id);

        if (!$card) {
            abort(404);
        }

        $invoice = $this->invoiceService->getInvoiceByDueMonth($card, $month, $year);

        if (!$invoice) {
            abort(404, 'Fatura não encontrada.');
        }

        $this->invoiceService->markAsUnpaid($invoice);

        return redirect()->back()
            ->with('success', 'Fatura marcada como não paga.');
    }

    /**
     * Recalculate invoice
     * @param int $month Month of due date (cycle_month)
     * @param int $year Year of due date (cycle_year)
     */
    public function recalculate(Request $request, string $cardId, int $month, int $year)
    {
        $card = $this->cardService->getById((int) $cardId, $request->user()->id);

        if (!$card) {
            abort(404);
        }

        $invoice = $this->invoiceService->getInvoiceByDueMonth($card, $month, $year);

        if (!$invoice) {
            abort(404, 'Fatura não encontrada.');
        }

        $this->invoiceService->recalculateInvoice($invoice);

        return redirect()->back()
            ->with('success', 'Fatura recalculada com sucesso.');
    }
}
