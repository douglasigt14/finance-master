<?php

namespace App\DTOs;

class UpdateCardDTO
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $brand = null,
        public readonly ?string $lastFour = null,
        public readonly ?float $creditLimit = null,
        public readonly ?int $closingDay = null,
        public readonly ?int $dueDay = null,
        public readonly ?string $status = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? null,
            brand: $data['brand'] ?? null,
            lastFour: $data['last_four'] ?? null,
            creditLimit: isset($data['credit_limit']) ? (float) $data['credit_limit'] : null,
            closingDay: isset($data['closing_day']) ? (int) $data['closing_day'] : null,
            dueDay: isset($data['due_day']) ? (int) $data['due_day'] : null,
            status: $data['status'] ?? null,
        );
    }

    public function toArray(): array
    {
        $array = [];

        if ($this->name !== null) {
            $array['name'] = $this->name;
        }
        if ($this->brand !== null) {
            $array['brand'] = $this->brand;
        }
        if ($this->lastFour !== null) {
            $array['last_four'] = $this->lastFour;
        }
        if ($this->creditLimit !== null) {
            $array['credit_limit'] = $this->creditLimit;
        }
        if ($this->closingDay !== null) {
            $array['closing_day'] = $this->closingDay;
        }
        if ($this->dueDay !== null) {
            $array['due_day'] = $this->dueDay;
        }
        if ($this->status !== null) {
            $array['status'] = $this->status;
        }

        return $array;
    }
}
