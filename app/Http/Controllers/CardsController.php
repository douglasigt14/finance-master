<?php

namespace App\Http\Controllers;

use App\DTOs\CreateCardDTO;
use App\DTOs\UpdateCardDTO;
use App\Http\Requests\StoreCardRequest;
use App\Http\Requests\UpdateCardRequest;
use App\Services\CardService;
use Illuminate\Http\Request;

class CardsController extends Controller
{
    public function __construct(
        private CardService $cardService
    ) {
    }

    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        $cards = $this->cardService->getAllByUser($request->user()->id);
        return view('cards.index', compact('cards'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        return view('cards.create');
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCardRequest $request)
    {
        $dto = CreateCardDTO::fromArray(array_merge(
            $request->validated(),
            ['user_id' => $request->user()->id]
        ));

        $card = $this->cardService->create($dto);

        return redirect()->route('cards.index')
            ->with('success', 'Cartão criado com sucesso.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Request $request, string $id)
    {
        $card = $this->cardService->getById((int) $id, $request->user()->id);

        if (!$card) {
            abort(404);
        }

        return view('cards.show', compact('card'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Request $request, string $id)
    {
        $card = $this->cardService->getById((int) $id, $request->user()->id);

        if (!$card) {
            abort(404);
        }

        return view('cards.edit', compact('card'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCardRequest $request, string $id)
    {
        $card = $this->cardService->getById((int) $id, $request->user()->id);

        if (!$card) {
            abort(404);
        }

        $dto = UpdateCardDTO::fromArray($request->validated());
        $this->cardService->update($card, $dto);

        return redirect()->route('cards.index')
            ->with('success', 'Cartão atualizado com sucesso.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Request $request, string $id)
    {
        $card = $this->cardService->getById((int) $id, $request->user()->id);

        if (!$card) {
            abort(404);
        }

        $this->cardService->delete($card);

        return redirect()->route('cards.index')
            ->with('success', 'Cartão excluído com sucesso.');
    }
}
