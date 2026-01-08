<?php

namespace App\Services;

use App\DTOs\CreateCategoryDTO;
use App\DTOs\UpdateCategoryDTO;
use App\Models\Category;
use Illuminate\Database\Eloquent\Collection;

class CategoryService
{
    public function getAllByUser(int $userId): Collection
    {
        return Category::where('user_id', $userId)
            ->orderBy('type')
            ->orderBy('name')
            ->get();
    }

    public function getById(int $categoryId, int $userId): ?Category
    {
        return Category::where('id', $categoryId)
            ->where('user_id', $userId)
            ->first();
    }

    public function create(CreateCategoryDTO $dto): Category
    {
        return Category::create($dto->toArray());
    }

    public function update(Category $category, UpdateCategoryDTO $dto): Category
    {
        $category->update($dto->toArray());
        return $category->fresh();
    }

    public function delete(Category $category): bool
    {
        return $category->delete();
    }

    public function getByType(int $userId, string $type): Collection
    {
        return Category::where('user_id', $userId)
            ->where('type', $type)
            ->orderBy('name')
            ->get();
    }
}
