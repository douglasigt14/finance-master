<?php

namespace App\Http\Controllers;

use App\DTOs\CreateDebtorDTO;
use App\DTOs\UpdateDebtorDTO;
use App\Http\Requests\StoreDebtorRequest;
use App\Http\Requests\UpdateDebtorRequest;
use App\Models\Card;
use App\Services\DebtorService;
use App\Services\InvoiceService;
use Illuminate\Http\Request;

class DebtorsController extends Controller
{
    public function __construct(
        private DebtorService $debtorService,
        private InvoiceService $invoiceService
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $debtors = $this->debtorService->getAllByUser($request->user()->id);
        
        // Get all active cards for the user
        $cards = Card::where('user_id', $request->user()->id)
            ->active()
            ->get();
        
        // Get next cycle transactions for all cards, grouped by debtor
        $debtorTransactions = collect();
        $cycleInfo = collect();
        
        foreach ($cards as $card) {
            $currentInvoice = $this->invoiceService->getCurrentInvoice($card);
            if ($currentInvoice) {
                // Get next cycle (add 1 month to closing date)
                $currentClosingDate = \Carbon\Carbon::parse($currentInvoice->closing_date);
                $nextClosingMonth = $currentClosingDate->copy()->addMonth()->month;
                $nextClosingYear = $currentClosingDate->copy()->addMonth()->year;
                
                // Handle year rollover
                if ($nextClosingMonth > 12) {
                    $nextClosingMonth = 1;
                    $nextClosingYear += 1;
                }
                
                // Get or create next invoice
                $nextInvoice = $this->invoiceService->getOrCreateInvoice($card, $nextClosingMonth, $nextClosingYear);
                
                if ($nextInvoice) {
                    $nextClosingDate = \Carbon\Carbon::parse($nextInvoice->closing_date);
                    $cycleDates = $this->invoiceService->calculateCycleDates($card, $nextClosingDate->month, $nextClosingDate->year);
                    
                    // Store cycle info for display
                    $cycleInfo->push([
                        'card' => $card->name,
                        'start' => $cycleDates['start']->format('d/m/Y'),
                        'end' => $cycleDates['end']->format('d/m/Y'),
                    ]);
                    
                    // Get transactions from THIS SPECIFIC CARD in its cycle date range
                    $transactions = $card->transactions()
                        ->where('payment_method', 'CREDIT')
                        ->where('type', 'EXPENSE')
                        ->whereBetween('transaction_date', [
                            $cycleDates['start']->format('Y-m-d'),
                            $cycleDates['end']->format('Y-m-d')
                        ])
                        ->with(['category', 'debtor', 'card'])
                        ->orderBy('transaction_date')
                        ->get();
                    
                    $debtorTransactions = $debtorTransactions->merge($transactions);
                }
            }
        }
        
        // Group transactions by debtor (including null for transactions without debtor)
        $transactionsByDebtor = $debtorTransactions->groupBy(function ($transaction) {
            return $transaction->debtor_id ?? 'sem_devedor';
        });
        
        return view('debtors.index', compact('debtors', 'transactionsByDebtor', 'cycleInfo'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('debtors.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreDebtorRequest $request)
    {
        $dto = CreateDebtorDTO::fromArray(array_merge(
            $request->validated(),
            ['user_id' => $request->user()->id]
        ));

        $debtor = $this->debtorService->create($dto);

        return redirect()->route('debtors.index')
            ->with('success', 'Devedor criado com sucesso.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $debtor = $this->debtorService->getById((int) $id, $request->user()->id);

        if (!$debtor) {
            abort(404);
        }

        return view('debtors.show', compact('debtor'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, string $id)
    {
        $debtor = $this->debtorService->getById((int) $id, $request->user()->id);

        if (!$debtor) {
            abort(404);
        }

        return view('debtors.edit', compact('debtor'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateDebtorRequest $request, string $id)
    {
        $debtor = $this->debtorService->getById((int) $id, $request->user()->id);

        if (!$debtor) {
            abort(404);
        }

        $dto = UpdateDebtorDTO::fromArray($request->validated());
        $this->debtorService->update($debtor, $dto);

        return redirect()->route('debtors.index')
            ->with('success', 'Devedor atualizado com sucesso.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $debtor = $this->debtorService->getById((int) $id, $request->user()->id);

        if (!$debtor) {
            abort(404);
        }

        $this->debtorService->delete($debtor);

        return redirect()->route('debtors.index')
            ->with('success', 'Devedor excluído com sucesso.');
    }
}
