<?php

namespace App\Services;

use App\DTOs\CreateCardDTO;
use App\DTOs\UpdateCardDTO;
use App\Models\Card;
use Illuminate\Database\Eloquent\Collection;

class CardService
{
    public function getAllByUser(int $userId): Collection
    {
        return Card::where('user_id', $userId)
            ->orderBy('name')
            ->get();
    }

    public function getActiveByUser(int $userId): Collection
    {
        return Card::where('user_id', $userId)
            ->active()
            ->orderBy('name')
            ->get();
    }

    public function getById(int $cardId, int $userId): ?Card
    {
        return Card::where('id', $cardId)
            ->where('user_id', $userId)
            ->first();
    }

    public function create(CreateCardDTO $dto): Card
    {
        return Card::create($dto->toArray());
    }

    public function update(Card $card, UpdateCardDTO $dto): Card
    {
        $card->update($dto->toArray());
        return $card->fresh();
    }

    public function delete(Card $card): bool
    {
        return $card->delete();
    }
}
