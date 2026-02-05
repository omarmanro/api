<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\ExpenseCategoryRepository;
use App\Services\AuditService;

class ExpenseCategoryController extends BaseController
{
    private ExpenseCategoryRepository $repository;
    private AuditService $auditService;

    public function __construct()
    {
        $this->repository = new ExpenseCategoryRepository();
        $this->auditService = new AuditService();
    }

    public function index(): array
    {
        $categories = $this->repository->getActive();
        return $this->success($categories);
    }

    public function show(int $id): array
    {
        $category = $this->repository->find($id);

        if (!$category) {
            return $this->notFound('Categoría no encontrada');
        }

        return $this->success($category);
    }

    public function store(array $body): array
    {
        $errors = $this->validate($body, [
            'name' => 'required|string|min:2|max:50',
            'description' => 'nullable|string|max:200',
            'color' => 'nullable|string|max:7',
            'icon' => 'nullable|string|max:30'
        ]);

        if (!empty($errors)) {
            return $this->validationError($errors);
        }

        // Verificar nombre único
        if ($this->repository->findByName($body['name'])) {
            return $this->validationError([
                'name' => ['La categoría ya existe']
            ]);
        }

        $category = $this->repository->create([
            'name' => $body['name'],
            'description' => $body['description'] ?? null,
            'color' => $body['color'] ?? '#6B7280',
            'icon' => $body['icon'] ?? 'folder',
            'status' => 'active'
        ]);

        $this->auditService->logCreate('expense_categories', $category->getId(), $category->toArray());

        return $this->created($category, 'Categoría creada exitosamente');
    }

    public function update(int $id, array $body): array
    {
        $category = $this->repository->find($id);

        if (!$category) {
            return $this->notFound('Categoría no encontrada');
        }

        $errors = $this->validate($body, [
            'name' => 'nullable|string|min:2|max:50',
            'description' => 'nullable|string|max:200',
            'color' => 'nullable|string|max:7',
            'icon' => 'nullable|string|max:30',
            'status' => 'nullable|in:active,inactive'
        ]);

        if (!empty($errors)) {
            return $this->validationError($errors);
        }

        // Verificar nombre único si se está cambiando
        if (isset($body['name']) && $body['name'] !== $category->name) {
            if ($this->repository->findByName($body['name'])) {
                return $this->validationError([
                    'name' => ['La categoría ya existe']
                ]);
            }
        }

        $oldValues = $category->toArray();
        $updated = $this->repository->update($id, $body);

        $this->auditService->logUpdate('expense_categories', $id, $oldValues, $updated->toArray());

        return $this->success($updated, 'Categoría actualizada exitosamente');
    }

    public function destroy(int $id): array
    {
        $category = $this->repository->find($id);

        if (!$category) {
            return $this->notFound('Categoría no encontrada');
        }

        $oldValues = $category->toArray();
        $this->repository->softDelete($id);
        
        $this->auditService->logDelete('expense_categories', $id, $oldValues);

        return $this->success(null, 'Categoría eliminada exitosamente');
    }
}
