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

        // Get selected card or first card
        $selectedCard = $cardId 
            ? $this->cardService->getById((int) $cardId, $user->id)
            : $cards->first();

        if (!$selectedCard) {
            $selectedCard = $cards->first();
        }

        // Get all invoices for the card
        $invoices = $this->invoiceService->getInvoicesByCard($selectedCard);

        // Get current invoice
        $currentInvoice = $this->invoiceService->getCurrentInvoice($selectedCard);
        $availableCredit = $this->invoiceService->getAvailableCredit($selectedCard);

        return view('invoices.index', compact(
            'cards',
            'selectedCard',
            'invoices',
            'currentInvoice',
            'availableCredit'
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
            ->with('category')
            ->orderBy('transaction_date')
            ->get();

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
