<?php

namespace App\DTOs;

class UpdateCategoryDTO
{
    public function __construct(
        public readonly ?string $name = null,
        public readonly ?string $type = null,
        public readonly ?string $color = null,
    ) {
    }

    public static function fromArray(array $data): self
    {
        return new self(
            name: $data['name'] ?? null,
            type: $data['type'] ?? null,
            color: $data['color'] ?? null,
        );
    }

    public function toArray(): array
    {
        $array = [];

        if ($this->name !== null) {
            $array['name'] = $this->name;
        }
        if ($this->type !== null) {
            $array['type'] = $this->type;
        }
        if ($this->color !== null) {
            $array['color'] = $this->color;
        }

        return $array;
    }
}
