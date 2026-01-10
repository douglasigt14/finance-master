<?php

namespace App\Http\Controllers;

use App\DTOs\CreateRecurringTransactionDTO;
use App\DTOs\UpdateRecurringTransactionDTO;
use App\Http\Requests\StoreRecurringTransactionRequest;
use App\Http\Requests\UpdateRecurringTransactionRequest;
use App\Models\Card;
use App\Models\Category;
use App\Services\RecurringTransactionService;
use Illuminate\Http\Request;

class RecurringTransactionsController extends Controller
{
    public function __construct(
        private RecurringTransactionService $recurringTransactionService
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $search = $request->query('search');
        $recurringTransactions = $this->recurringTransactionService->getAllByUser($request->user()->id, $search);

        return view('recurring-transactions.index', compact('recurringTransactions'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $categories = Category::where('user_id', $request->user()->id)->orderBy('name')->get();
        $cards = Card::where('user_id', $request->user()->id)->active()->orderBy('name')->get();
        $debtors = \App\Models\Debtor::where('user_id', $request->user()->id)->orderBy('name')->get();

        return view('recurring-transactions.create', compact('categories', 'cards', 'debtors'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreRecurringTransactionRequest $request)
    {
        $dto = CreateRecurringTransactionDTO::fromArray(array_merge(
            $request->validated(),
            ['user_id' => $request->user()->id]
        ));

        $this->recurringTransactionService->create($dto);

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => 'Transação recorrente criada com sucesso.'
            ]);
        }

        return redirect()->route('recurring-transactions.index')
            ->with('success', 'Transação recorrente criada com sucesso.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $recurringTransaction = $this->recurringTransactionService->getById((int) $id, $request->user()->id);

        if (!$recurringTransaction) {
            abort(404);
        }

        return view('recurring-transactions.show', compact('recurringTransaction'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, string $id)
    {
        $recurringTransaction = $this->recurringTransactionService->getById((int) $id, $request->user()->id);

        if (!$recurringTransaction) {
            abort(404);
        }

        $categories = Category::where('user_id', $request->user()->id)->orderBy('name')->get();
        $cards = Card::where('user_id', $request->user()->id)->active()->orderBy('name')->get();
        $debtors = \App\Models\Debtor::where('user_id', $request->user()->id)->orderBy('name')->get();

        return view('recurring-transactions.edit', compact('recurringTransaction', 'categories', 'cards', 'debtors'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateRecurringTransactionRequest $request, string $id)
    {
        $recurringTransaction = $this->recurringTransactionService->getById((int) $id, $request->user()->id);

        if (!$recurringTransaction) {
            abort(404);
        }

        $dto = UpdateRecurringTransactionDTO::fromArray($request->validated());
        $this->recurringTransactionService->update($recurringTransaction, $dto);

        return redirect()->route('recurring-transactions.index')
            ->with('success', 'Transação recorrente atualizada com sucesso.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $recurringTransaction = $this->recurringTransactionService->getById((int) $id, $request->user()->id);

        if (!$recurringTransaction) {
            abort(404);
        }

        $this->recurringTransactionService->delete($recurringTransaction);

        return redirect()->route('recurring-transactions.index')
            ->with('success', 'Transação recorrente excluída com sucesso.');
    }

    /**
     * Generate transactions manually
     */
    public function generate(Request $request)
    {
        $count = $this->recurringTransactionService->generateTransactions();

        return redirect()->back()
            ->with('success', "{$count} transação(ões) gerada(s) com sucesso.");
    }
}
