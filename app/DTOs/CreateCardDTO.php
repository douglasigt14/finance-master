<?php

namespace App\DTOs;

class CreateCardDTO
{
    public function __construct(
        public readonly int $userId,
        public readonly string $name,
        public readonly ?string $brand,
        public readonly ?string $lastFour,
        public readonly float $creditLimit,
        public readonly int $closingDay,
        public readonly int $dueDay,
        public readonly string $status = 'active',
        public readonly ?string $color = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            userId: $data['user_id'],
            name: $data['name'],
            brand: $data['brand'] ?? null,
            lastFour: $data['last_four'] ?? null,
            creditLimit: (float) $data['credit_limit'],
            closingDay: (int) $data['closing_day'],
            dueDay: (int) $data['due_day'],
            status: $data['status'] ?? 'active',
            color: $data['color'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'name' => $this->name,
            'brand' => $this->brand,
            'last_four' => $this->lastFour,
            'credit_limit' => $this->creditLimit,
            'closing_day' => $this->closingDay,
            'due_day' => $this->dueDay,
            'status' => $this->status,
            'color' => $this->color,
        ];
    }
}
