<?php

namespace App\Http\Controllers;

use App\Models\Card;
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
            // Use invoice from query parameters
            $selectedInvoice = $this->invoiceService->getOrCreateInvoice($selectedCard, (int) $selectedMonth, (int) $selectedYear);
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
        
        $cycleDates = $this->invoiceService->calculateCycleDates($selectedCard, $selectedInvoice->cycle_month, $selectedInvoice->cycle_year);
        
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

        return view('invoices.index', compact(
            'cards',
            'selectedCard',
            'invoices',
            'currentInvoice',
            'availableCredit',
            'selectedInvoice',
            'transactions',
            'cycleDates'
        ));
    }

    /**
     * Show invoice details
     */
    public function show(Request $request, string $cardId, int $month, int $year)
    {
        $card = $this->cardService->getById((int) $cardId, $request->user()->id);

        if (!$card) {
            abort(404);
        }

        $invoice = $this->invoiceService->getOrCreateInvoice($card, $month, $year);

        // Get transactions for this invoice cycle
        $cycleDates = $this->invoiceService->calculateCycleDates($card, $month, $year);
        
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
     */
    public function markAsPaid(Request $request, string $cardId, int $month, int $year)
    {
        $card = $this->cardService->getById((int) $cardId, $request->user()->id);

        if (!$card) {
            abort(404);
        }

        $invoice = $this->invoiceService->getOrCreateInvoice($card, $month, $year);
        $this->invoiceService->markAsPaid($invoice);

        return redirect()->back()
            ->with('success', 'Fatura marcada como paga.');
    }

    /**
     * Mark invoice as unpaid
     */
    public function markAsUnpaid(Request $request, string $cardId, int $month, int $year)
    {
        $card = $this->cardService->getById((int) $cardId, $request->user()->id);

        if (!$card) {
            abort(404);
        }

        $invoice = $this->invoiceService->getOrCreateInvoice($card, $month, $year);
        $this->invoiceService->markAsUnpaid($invoice);

        return redirect()->back()
            ->with('success', 'Fatura marcada como não paga.');
    }

    /**
     * Recalculate invoice
     */
    public function recalculate(Request $request, string $cardId, int $month, int $year)
    {
        $card = $this->cardService->getById((int) $cardId, $request->user()->id);

        if (!$card) {
            abort(404);
        }

        $invoice = $this->invoiceService->getOrCreateInvoice($card, $month, $year);
        $this->invoiceService->recalculateInvoice($invoice);

        return redirect()->back()
            ->with('success', 'Fatura recalculada com sucesso.');
    }
}
