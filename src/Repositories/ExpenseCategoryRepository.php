<?php

declare(strict_types=1);

namespace App\Repositories;

use App\Models\ExpenseCategory;

class ExpenseCategoryRepository extends BaseRepository
{
    protected string $model = ExpenseCategory::class;

    public function getActive(): array
    {
        return $this->all(['status' => ExpenseCategory::STATUS_ACTIVE], ['name' => 'ASC']);
    }

    public function findByName(string $name): ?ExpenseCategory
    {
        return $this->findBy('name', $name);
    }
}
