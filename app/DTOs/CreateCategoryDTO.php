<?php

namespace App\DTOs;

class CreateCategoryDTO
{
    public function __construct(
        public readonly int $userId,
        public readonly string $name,
        public readonly string $type,
        public readonly ?string $color = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            userId: $data['user_id'],
            name: $data['name'],
            type: $data['type'],
            color: $data['color'] ?? null,
        );
    }

    public function toArray(): array
    {
        return [
            'user_id' => $this->userId,
            'name' => $this->name,
            'type' => $this->type,
            'color' => $this->color,
        ];
    }
}
