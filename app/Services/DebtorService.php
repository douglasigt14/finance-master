<?php

namespace App\Services;

use App\DTOs\CreateDebtorDTO;
use App\DTOs\UpdateDebtorDTO;
use App\Models\Debtor;
use Illuminate\Database\Eloquent\Collection;

class DebtorService
{
    public function getAllByUser(int $userId): Collection
    {
        return Debtor::where('user_id', $userId)
            ->withCount('transactions')
            ->orderBy('name')
            ->get();
    }

    public function getById(int $debtorId, int $userId): ?Debtor
    {
        return Debtor::where('id', $debtorId)
            ->where('user_id', $userId)
            ->with('transactions')
            ->first();
    }

    public function create(CreateDebtorDTO $dto): Debtor
    {
        return Debtor::create($dto->toArray());
    }

    public function update(Debtor $debtor, UpdateDebtorDTO $dto): Debtor
    {
        $debtor->update($dto->toArray());
        return $debtor->fresh();
    }

    public function delete(Debtor $debtor): bool
    {
        return $debtor->delete();
    }
}
