<?php

namespace App\Http\Controllers;

use App\DTOs\CreateDebtorDTO;
use App\DTOs\UpdateDebtorDTO;
use App\Http\Requests\StoreDebtorRequest;
use App\Http\Requests\UpdateDebtorRequest;
use App\Models\Card;
use App\Models\Transaction;
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
        
        // Get selected month/year from request, or default to next month
        $selectedMonth = $request->query('month');
        $selectedYear = $request->query('year');
        
        $now = \Carbon\Carbon::now();
        
        if ($selectedMonth && $selectedYear) {
            $targetMonth = (int) $selectedMonth;
            $targetYear = (int) $selectedYear;
        } else {
            // Default to next month
            $nextMonth = $now->copy()->addMonth();
            $targetMonth = $nextMonth->month;
            $targetYear = $nextMonth->year;
        }
        
        // Get cycle transactions for all cards, grouped by debtor
        $debtorTransactions = collect();
        $cycleInfo = collect();
        
        // Collect all cycle date ranges to determine overall period for transactions without card
        $allCycleStarts = collect();
        $allCycleEnds = collect();
        
        foreach ($cards as $card) {
            // Find invoice with due date in the target month/year
            $targetInvoice = $this->invoiceService->getInvoiceByDueMonth($card, $targetMonth, $targetYear);
            
            if ($targetInvoice) {
                // Use the existing invoice
                $closingDate = \Carbon\Carbon::parse($targetInvoice->closing_date);
                $cycleDates = $this->invoiceService->calculateCycleDates($card, $closingDate->month, $closingDate->year);
            } else {
                // Invoice doesn't exist, try to create it
                // We need to find the closing month that would result in due date in target month/year
                // Try using previous month as closing month (common case)
                $prevMonth = $targetMonth - 1;
                $prevYear = $targetYear;
                if ($prevMonth < 1) {
                    $prevMonth = 12;
                    $prevYear -= 1;
                }
                
                // Calculate cycle dates with previous month as closing
                $cycleDates = $this->invoiceService->calculateCycleDates($card, $prevMonth, $prevYear);
                
                // Check if calculated due date matches target
                if ($cycleDates['due']->month !== $targetMonth || $cycleDates['due']->year !== $targetYear) {
                    // Doesn't match, try using target month as closing (for cards where due > closing)
                    $cycleDates = $this->invoiceService->calculateCycleDates($card, $targetMonth, $targetYear);
                    
                    // If still doesn't match, use the dates anyway (edge case)
                }
                
                // Create the invoice if it doesn't exist
                $this->invoiceService->getOrCreateInvoice($card, $cycleDates['closing']->month, $cycleDates['closing']->year);
            }
            
            // Store cycle info for display
            $cycleInfo->push([
                'card' => $card->name,
                'start' => $cycleDates['start']->format('d/m/Y'),
                'end' => $cycleDates['end']->format('d/m/Y'),
            ]);
            
            // Collect cycle dates for overall period calculation
            $allCycleStarts->push($cycleDates['start']);
            $allCycleEnds->push($cycleDates['end']);
            
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
        
        // Get transactions without card (PIX, DINHEIRO, DÉBITO, etc.)
        // Since these transactions don't have a billing cycle, use the same period as the selected cycle
        // Use the overall period from all cards, or default to target month if no cards exist
        if ($allCycleStarts->isNotEmpty() && $allCycleEnds->isNotEmpty()) {
            $overallStart = $allCycleStarts->min();
            $overallEnd = $allCycleEnds->max();
        } else {
            // Default to target month if no cards
            $overallStart = \Carbon\Carbon::create($targetYear, $targetMonth, 1)->startOfMonth();
            $overallEnd = \Carbon\Carbon::create($targetYear, $targetMonth, 1)->endOfMonth();
        }
        
        // Get transactions without card in the same period as the next cycle
        $transactionsWithoutCard = Transaction::where('user_id', $request->user()->id)
            ->whereNull('card_id')
            ->where('type', 'EXPENSE')
            ->whereBetween('transaction_date', [
                $overallStart->format('Y-m-d'),
                $overallEnd->format('Y-m-d')
            ])
            ->with(['category', 'debtor', 'card'])
            ->orderBy('transaction_date')
            ->get();
        
        $debtorTransactions = $debtorTransactions->merge($transactionsWithoutCard);
        
        // Group transactions by debtor (including null for transactions without debtor)
        $transactionsByDebtor = $debtorTransactions->groupBy(function ($transaction) {
            return $transaction->debtor_id ?? 'sem_devedor';
        });
        
        // Prepare chart data for "Meu" (transactions without debtor)
        $meuTransactions = $transactionsByDebtor->get('sem_devedor', collect());
        $chartDataByCard = collect();
        $chartDataByCategory = collect();
        $chartDataByInstallmentStatus = collect();
        
        if ($meuTransactions && $meuTransactions->isNotEmpty()) {
            // Group by card
            $byCard = $meuTransactions->groupBy(function ($transaction) {
                if ($transaction->card) {
                    return $transaction->card->name;
                }
                return $transaction->payment_method === 'PIX' ? 'PIX' : 
                       ($transaction->payment_method === 'CASH' ? 'Dinheiro' : 
                       ($transaction->payment_method === 'DEBIT' ? 'Débito' : 'Sem Cartão'));
            });
            
            foreach ($byCard as $cardName => $transactions) {
                $firstTransaction = $transactions->first();
                $color = '#0d6efd'; // Default color
                
                if ($firstTransaction->card && $firstTransaction->card->color) {
                    $color = $firstTransaction->card->color;
                } elseif ($cardName === 'PIX') {
                    $color = '#6c757d'; // Gray for PIX
                } elseif ($cardName === 'Dinheiro') {
                    $color = '#28a745'; // Green for Cash
                } elseif ($cardName === 'Débito') {
                    $color = '#17a2b8'; // Cyan for Debit
                }
                
                $chartDataByCard->push([
                    'label' => $cardName,
                    'amount' => $transactions->sum('amount'),
                    'color' => $color
                ]);
            }
            
            // Group by category
            $byCategory = $meuTransactions->groupBy(function ($transaction) {
                return $transaction->category->name ?? 'Sem Categoria';
            });
            
            foreach ($byCategory as $categoryName => $transactions) {
                $chartDataByCategory->push([
                    'label' => $categoryName,
                    'amount' => $transactions->sum('amount'),
                    'color' => $transactions->first()->category->color ?? '#6c757d'
                ]);
            }
            
            // Group by installment status
            $byInstallmentStatus = $meuTransactions->groupBy(function ($transaction) {
                if ($transaction->installments_total <= 1) {
                    return 'Compras à Vista';
                }
                
                $remaining = $transaction->installments_total - $transaction->installment_number;
                
                if ($remaining === 0) {
                    return 'Última Parcela';
                } elseif ($remaining === 1) {
                    return 'Penúltima Parcela';
                } elseif ($remaining === 2) {
                    return 'Antepenúltima Parcela';
                } else {
                    return 'Faltam mais de 4 Parcelas';
                }
            });
            
            $statusColors = [
                'Compras à Vista' => '#28a745',
                'Última Parcela' => '#ffc107',
                'Penúltima Parcela' => '#fd7e14',
                'Antepenúltima Parcela' => '#dc3545',
                'Faltam mais de 4 Parcelas' => '#6c757d'
            ];
            
            foreach ($byInstallmentStatus as $status => $transactions) {
                $chartDataByInstallmentStatus->push([
                    'label' => $status,
                    'amount' => $transactions->sum('amount'),
                    'color' => $statusColors[$status] ?? '#6c757d'
                ]);
            }
            
            // Sort by specific order: Compras à Vista, Última, Penúltima, Antepenúltima, Faltam mais de 4
            $order = [
                'Compras à Vista' => 1,
                'Última Parcela' => 2,
                'Penúltima Parcela' => 3,
                'Antepenúltima Parcela' => 4,
                'Faltam mais de 4 Parcelas' => 5
            ];
            
            $chartDataByInstallmentStatus = $chartDataByInstallmentStatus->sortBy(function ($item) use ($order) {
                return $order[$item['label']] ?? 999;
            })->values();
        }
        
        // Prepare chart data for debtors (pie chart showing total value per debtor)
        $chartDataByDebtor = collect();
        
        foreach ($transactionsByDebtor as $debtorId => $transactions) {
            if ($transactions->isNotEmpty()) {
                $totalAmount = $transactions->sum('amount');
                
                if ($debtorId === 'sem_devedor') {
                    $chartDataByDebtor->push([
                        'label' => 'Meu',
                        'amount' => $totalAmount,
                        'color' => '#0d6efd' // Default blue
                    ]);
                } else {
                    // Get debtor name
                    $debtor = $debtors->firstWhere('id', $debtorId);
                    if ($debtor) {
                        // Generate a color based on debtor name (consistent color per debtor)
                        $hash = md5($debtor->name);
                        $color = '#' . substr($hash, 0, 6);
                        // Ensure color is not too light
                        $r = hexdec(substr($color, 1, 2));
                        $g = hexdec(substr($color, 3, 2));
                        $b = hexdec(substr($color, 5, 2));
                        // If too light, darken it
                        if ($r + $g + $b > 600) {
                            $r = max(0, $r - 50);
                            $g = max(0, $g - 50);
                            $b = max(0, $b - 50);
                            $color = sprintf('#%02x%02x%02x', $r, $g, $b);
                        }
                        
                        $chartDataByDebtor->push([
                            'label' => $debtor->name,
                            'amount' => $totalAmount,
                            'color' => $color
                        ]);
                    }
                }
            }
        }
        
        // Sort by amount descending
        $chartDataByDebtor = $chartDataByDebtor->sortByDesc('amount')->values();
        
        return view('debtors.index', compact('debtors', 'transactionsByDebtor', 'cycleInfo', 'chartDataByCard', 'chartDataByCategory', 'chartDataByInstallmentStatus', 'chartDataByDebtor', 'targetMonth', 'targetYear'));
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
