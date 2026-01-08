<?php

namespace App\DTOs;

class UpdateTransactionDTO
{
    public function __construct(
        public readonly ?int $categoryId = null,
        public readonly ?string $type = null,
        public readonly ?float $amount = null,
        public readonly ?string $transactionDate = null,
        public readonly ?int $cardId = null,
        public readonly ?string $paymentMethod = null,
        public readonly ?string $description = null,
        public readonly ?bool $isPaid = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            categoryId: isset($data['category_id']) ? (int) $data['category_id'] : null,
            type: $data['type'] ?? null,
            amount: isset($data['amount']) ? (float) $data['amount'] : null,
            transactionDate: $data['transaction_date'] ?? null,
            cardId: isset($data['card_id']) ? (int) $data['card_id'] : null,
            paymentMethod: $data['payment_method'] ?? null,
            description: $data['description'] ?? null,
            isPaid: isset($data['is_paid']) ? (bool) $data['is_paid'] : null,
        );
    }

    public function toArray(): array
    {
        $array = [];

        if ($this->categoryId !== null) {
            $array['category_id'] = $this->categoryId;
        }
        if ($this->type !== null) {
            $array['type'] = $this->type;
        }
        if ($this->amount !== null) {
            $array['amount'] = $this->amount;
        }
        if ($this->transactionDate !== null) {
            $array['transaction_date'] = $this->transactionDate;
        }
        if ($this->cardId !== null) {
            $array['card_id'] = $this->cardId;
        }
        if ($this->paymentMethod !== null) {
            $array['payment_method'] = $this->paymentMethod;
        }
        if ($this->description !== null) {
            $array['description'] = $this->description;
        }
        if ($this->isPaid !== null) {
            $array['is_paid'] = $this->isPaid;
        }

        return $array;
    }
}
