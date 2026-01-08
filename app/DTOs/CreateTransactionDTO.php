<?php

namespace App\DTOs;

class CreateTransactionDTO
{
    public function __construct(
        public readonly int $userId,
        public readonly int $categoryId,
        public readonly string $type,
        public readonly float $amount,
        public readonly string $transactionDate,
        public readonly ?int $cardId = null,
        public readonly ?string $paymentMethod = null,
        public readonly ?string $description = null,
        public readonly int $installmentsTotal = 1,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            userId: $data['user_id'],
            categoryId: $data['category_id'],
            type: $data['type'],
            amount: (float) $data['amount'],
            transactionDate: $data['transaction_date'],
            cardId: $data['card_id'] ?? null,
            paymentMethod: $data['payment_method'] ?? null,
            description: $data['description'] ?? null,
            installmentsTotal: (int) ($data['installments_total'] ?? 1),
        );
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'category_id' => $this->categoryId,
            'type' => $this->type,
            'amount' => $this->amount,
            'transaction_date' => $this->transactionDate,
            'card_id' => $this->cardId,
            'payment_method' => $this->paymentMethod,
            'description' => $this->description,
            'installments_total' => $this->installmentsTotal,
        ];
    }
}
