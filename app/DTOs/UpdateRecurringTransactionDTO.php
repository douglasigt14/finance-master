<?php

namespace App\DTOs;

class UpdateRecurringTransactionDTO
{
    public function __construct(
        public readonly ?int $categoryId = null,
        public readonly ?string $type = null,
        public readonly ?float $amount = null,
        public readonly ?string $frequency = null,
        public readonly ?int $dayOfMonth = null,
        public readonly ?string $startDate = null,
        public readonly ?int $cardId = null,
        public readonly ?string $paymentMethod = null,
        public readonly ?string $description = null,
        public readonly ?string $cardDescription = null,
        public readonly ?int $debtorId = null,
        public readonly ?string $endDate = null,
        public readonly ?bool $isActive = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            categoryId: isset($data['category_id']) ? (int) $data['category_id'] : null,
            type: $data['type'] ?? null,
            amount: isset($data['amount']) ? (float) $data['amount'] : null,
            frequency: $data['frequency'] ?? null,
            dayOfMonth: isset($data['day_of_month']) ? (int) $data['day_of_month'] : null,
            startDate: $data['start_date'] ?? null,
            cardId: isset($data['card_id']) && $data['card_id'] ? (int) $data['card_id'] : null,
            paymentMethod: $data['payment_method'] ?? null,
            description: $data['description'] ?? null,
            cardDescription: $data['card_description'] ?? null,
            debtorId: isset($data['debtor_id']) && $data['debtor_id'] ? (int) $data['debtor_id'] : null,
            endDate: $data['end_date'] ?? null,
            isActive: isset($data['is_active']) ? (bool) $data['is_active'] : null,
        );
    }

    public function toArray(): array
    {
        $array = [];
        
        if ($this->categoryId !== null) $array['category_id'] = $this->categoryId;
        if ($this->type !== null) $array['type'] = $this->type;
        if ($this->amount !== null) $array['amount'] = $this->amount;
        if ($this->frequency !== null) $array['frequency'] = $this->frequency;
        if ($this->dayOfMonth !== null) $array['day_of_month'] = $this->dayOfMonth;
        if ($this->startDate !== null) $array['start_date'] = $this->startDate;
        if ($this->cardId !== null) $array['card_id'] = $this->cardId;
        if ($this->paymentMethod !== null) $array['payment_method'] = $this->paymentMethod;
        if ($this->description !== null) $array['description'] = $this->description;
        if ($this->cardDescription !== null) $array['card_description'] = $this->cardDescription;
        if ($this->debtorId !== null) $array['debtor_id'] = $this->debtorId;
        if ($this->endDate !== null) $array['end_date'] = $this->endDate;
        if ($this->isActive !== null) $array['is_active'] = $this->isActive;

        return $array;
    }
}
