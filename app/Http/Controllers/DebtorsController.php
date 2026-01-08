<?php

namespace App\Http\Controllers;

use App\DTOs\CreateDebtorDTO;
use App\DTOs\UpdateDebtorDTO;
use App\Http\Requests\StoreDebtorRequest;
use App\Http\Requests\UpdateDebtorRequest;
use App\Services\DebtorService;
use Illuminate\Http\Request;

class DebtorsController extends Controller
{
    public function __construct(
        private DebtorService $debtorService
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $debtors = $this->debtorService->getAllByUser($request->user()->id);
        return view('debtors.index', compact('debtors'));
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
