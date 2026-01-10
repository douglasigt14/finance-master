<?php

namespace App\DTOs;

class CreateRecurringTransactionDTO
{
    public function __construct(
        public readonly int $userId,
        public readonly int $categoryId,
        public readonly string $type,
        public readonly float $amount,
        public readonly string $frequency,
        public readonly int $dayOfMonth,
        public readonly string $startDate,
        public readonly ?int $cardId = null,
        public readonly ?string $paymentMethod = null,
        public readonly ?string $description = null,
        public readonly ?string $cardDescription = null,
        public readonly ?int $debtorId = null,
        public readonly ?string $endDate = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            userId: $data['user_id'],
            categoryId: $data['category_id'],
            type: $data['type'],
            amount: (float) $data['amount'],
            frequency: $data['frequency'],
            dayOfMonth: (int) $data['day_of_month'],
            startDate: $data['start_date'],
            cardId: $data['card_id'] ?? null,
            paymentMethod: $data['payment_method'] ?? null,
            description: $data['description'] ?? null,
            cardDescription: $data['card_description'] ?? null,
            debtorId: isset($data['debtor_id']) && $data['debtor_id'] ? (int) $data['debtor_id'] : null,
            endDate: $data['end_date'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'category_id' => $this->categoryId,
            'type' => $this->type,
            'amount' => $this->amount,
            'frequency' => $this->frequency,
            'day_of_month' => $this->dayOfMonth,
            'start_date' => $this->startDate,
            'card_id' => $this->cardId,
            'payment_method' => $this->paymentMethod,
            'description' => $this->description,
            'card_description' => $this->cardDescription,
            'debtor_id' => $this->debtorId,
            'end_date' => $this->endDate,
        ];
    }
}
