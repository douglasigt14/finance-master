<?php

namespace App\DTOs;

class CreateDebtorDTO
{
    public function __construct(
        public readonly int $userId,
        public readonly string $name,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            userId: $data['user_id'],
            name: $data['name'],
        );
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'name' => $this->name,
        ];
    }
}
