<?php

namespace App\Http\Controllers;

use App\DTOs\CreateTransactionDTO;
use App\DTOs\UpdateTransactionDTO;
use App\Http\Requests\StoreTransactionRequest;
use App\Http\Requests\UpdateTransactionRequest;
use App\Models\Card;
use App\Models\Category;
use App\Services\TransactionService;
use Illuminate\Http\Request;

class TransactionsController extends Controller
{
    public function __construct(
        private TransactionService $transactionService
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $filters = $request->only(['type', 'category_id', 'card_id', 'payment_method', 'date_from', 'date_to']);
        $transactions = $this->transactionService->getAllByUser($request->user()->id, $filters);

        // Get filters data
        $categories = Category::where('user_id', $request->user()->id)->orderBy('name')->get();
        $cards = Card::where('user_id', $request->user()->id)->active()->orderBy('name')->get();
        $debtors = \App\Models\Debtor::where('user_id', $request->user()->id)->orderBy('name')->get();

        return view('transactions.index', compact('transactions', 'categories', 'cards', 'debtors', 'filters'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(Request $request)
    {
        $categories = Category::where('user_id', $request->user()->id)->orderBy('name')->get();
        $cards = Card::where('user_id', $request->user()->id)->active()->orderBy('name')->get();
        $debtors = \App\Models\Debtor::where('user_id', $request->user()->id)->orderBy('name')->get();

        return view('transactions.create', compact('categories', 'cards', 'debtors'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreTransactionRequest $request)
    {
        $dto = CreateTransactionDTO::fromArray(array_merge(
            $request->validated(),
            ['user_id' => $request->user()->id]
        ));

        $result = $this->transactionService->create($dto);

        $message = is_iterable($result) && count($result) > 1
            ? 'Transação com ' . count($result) . ' parcelas criada com sucesso.'
            : 'Transação criada com sucesso.';

        if ($request->ajax()) {
            return response()->json([
                'success' => true,
                'message' => $message
            ]);
        }

        return redirect()->route('transactions.index')
            ->with('success', $message);
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $transaction = $this->transactionService->getById((int) $id, $request->user()->id);

        if (!$transaction) {
            abort(404);
        }

        $installmentGroup = null;
        if ($transaction->group_uuid) {
            $installmentGroup = $this->transactionService->getInstallmentGroup(
                $transaction->group_uuid,
                $request->user()->id
            );
        }

        return view('transactions.show', compact('transaction', 'installmentGroup'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, string $id)
    {
        $transaction = $this->transactionService->getById((int) $id, $request->user()->id);

        if (!$transaction) {
            abort(404);
        }

        $categories = Category::where('user_id', $request->user()->id)->orderBy('name')->get();
        $cards = Card::where('user_id', $request->user()->id)->active()->orderBy('name')->get();
        $debtors = \App\Models\Debtor::where('user_id', $request->user()->id)->orderBy('name')->get();

        return view('transactions.edit', compact('transaction', 'categories', 'cards', 'debtors'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateTransactionRequest $request, string $id)
    {
        $transaction = $this->transactionService->getById((int) $id, $request->user()->id);

        if (!$transaction) {
            abort(404);
        }

        $dto = UpdateTransactionDTO::fromArray($request->validated());
        $this->transactionService->update($transaction, $dto);

        return redirect()->route('transactions.index')
            ->with('success', 'Transação atualizada com sucesso.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $transaction = $this->transactionService->getById((int) $id, $request->user()->id);

        if (!$transaction) {
            abort(404);
        }

        $this->transactionService->delete($transaction);

        return redirect()->route('transactions.index')
            ->with('success', 'Transação excluída com sucesso.');
    }

    /**
     * Mark transaction as paid
     */
    public function markAsPaid(Request $request, string $id)
    {
        $transaction = $this->transactionService->getById((int) $id, $request->user()->id);

        if (!$transaction) {
            abort(404);
        }

        $this->transactionService->markAsPaid($transaction);

        return redirect()->back()
            ->with('success', 'Transação marcada como paga.');
    }

    /**
     * Mark transaction as unpaid
     */
    public function markAsUnpaid(Request $request, string $id)
    {
        $transaction = $this->transactionService->getById((int) $id, $request->user()->id);

        if (!$transaction) {
            abort(404);
        }

        $this->transactionService->markAsUnpaid($transaction);

        return redirect()->back()
            ->with('success', 'Transação marcada como não paga.');
    }

    /**
     * Show form to edit a transaction group
     */
    public function editGroup(Request $request, string $id)
    {
        $transaction = $this->transactionService->getById((int) $id, $request->user()->id);

        if (!$transaction || !$transaction->group_uuid) {
            abort(404, 'Transação não encontrada ou não faz parte de um grupo.');
        }

        $groupTransactions = $this->transactionService->getInstallmentGroup(
            $transaction->group_uuid,
            $request->user()->id
        );

        $categories = Category::where('user_id', $request->user()->id)->orderBy('name')->get();
        $cards = Card::where('user_id', $request->user()->id)->active()->orderBy('name')->get();
        $debtors = \App\Models\Debtor::where('user_id', $request->user()->id)->orderBy('name')->get();

        return view('transactions.edit-group', compact('transaction', 'groupTransactions', 'categories', 'cards', 'debtors'));
    }

    /**
     * Update a transaction group
     */
    public function updateGroup(Request $request, string $id)
    {
        $transaction = $this->transactionService->getById((int) $id, $request->user()->id);

        if (!$transaction || !$transaction->group_uuid) {
            abort(404, 'Transação não encontrada ou não faz parte de um grupo.');
        }

        $validated = $request->validate([
            'category_id' => 'required|integer|exists:categories,id',
            'transaction_date' => 'required|date',
            'description' => 'nullable|string|max:1000',
            'card_description' => 'nullable|string|max:1000',
            'card_id' => 'nullable|integer|exists:cards,id',
            'debtor_id' => 'nullable|integer|exists:debtors,id',
        ]);

        try {
            $this->transactionService->updateGroup(
                $transaction->group_uuid,
                $request->user()->id,
                $validated
            );

            return redirect()->route('transactions.index')
                ->with('success', 'Grupo de transações atualizado com sucesso.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->withInput()
                ->with('error', $e->getMessage());
        }
    }

    /**
     * Delete a transaction group
     */
    public function destroyGroup(Request $request, string $id)
    {
        $transaction = $this->transactionService->getById((int) $id, $request->user()->id);

        if (!$transaction || !$transaction->group_uuid) {
            abort(404, 'Transação não encontrada ou não faz parte de um grupo.');
        }

        try {
            $this->transactionService->deleteGroup(
                $transaction->group_uuid,
                $request->user()->id
            );

            return redirect()->route('transactions.index')
                ->with('success', 'Grupo de transações excluído com sucesso.');
        } catch (\Exception $e) {
            return redirect()->back()
                ->with('error', $e->getMessage());
        }
    }
}
