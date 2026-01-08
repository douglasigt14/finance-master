<?php

namespace App\Http\Controllers;

use App\Models\Transaction;
use App\Services\CardService;
use App\Services\InvoiceService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct(
        private CardService $cardService,
        private InvoiceService $invoiceService
    ) {
    }

    public function index(Request $request)
    {
        $user = $request->user();
        $now = Carbon::now();

        // Get current month transactions
        $startOfMonth = $now->copy()->startOfMonth();
        $endOfMonth = $now->copy()->endOfMonth();

        $transactions = Transaction::where('user_id', $user->id)
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->get();

        // Calculate totals
        $totalIncome = $transactions->where('type', 'INCOME')->sum('amount');
        $totalExpense = $transactions->where('type', 'EXPENSE')->sum('amount');
        $balance = $totalIncome - $totalExpense;

        // Expenses by category
        $expensesByCategory = Transaction::where('user_id', $user->id)
            ->where('type', 'EXPENSE')
            ->whereBetween('transaction_date', [$startOfMonth, $endOfMonth])
            ->select('category_id', DB::raw('SUM(amount) as total'))
            ->groupBy('category_id')
            ->with('category')
            ->get()
            ->map(function ($item) {
                return [
                    'category' => $item->category->name,
                    'color' => $item->category->color,
                    'total' => $item->total,
                ];
            });

        // Cards summary
        $cards = $this->cardService->getActiveByUser($user->id);
        $cardsSummary = $cards->map(function ($card) {
            $currentInvoice = $this->invoiceService->getCurrentInvoice($card);
            $availableCredit = $this->invoiceService->getAvailableCredit($card);

            return [
                'id' => $card->id,
                'name' => $card->name,
                'credit_limit' => $card->credit_limit,
                'used' => $currentInvoice->total_amount,
                'available' => $availableCredit,
                'percentage' => $card->credit_limit > 0 
                    ? ($currentInvoice->total_amount / $card->credit_limit) * 100 
                    : 0,
            ];
        });

        // Recent transactions
        $recentTransactions = Transaction::where('user_id', $user->id)
            ->with(['category', 'card'])
            ->orderBy('transaction_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('dashboard.index', compact(
            'totalIncome',
            'totalExpense',
            'balance',
            'expensesByCategory',
            'cardsSummary',
            'recentTransactions'
        ));
    }
}
