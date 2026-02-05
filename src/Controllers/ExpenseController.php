<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\ExpenseRepository;
use App\Repositories\ExpenseCategoryRepository;
use App\Services\AuditService;

class ExpenseController extends BaseController
{
    private ExpenseRepository $repository;
    private ExpenseCategoryRepository $categoryRepository;
    private AuditService $auditService;

    public function __construct()
    {
        $this->repository = new ExpenseRepository();
        $this->categoryRepository = new ExpenseCategoryRepository();
        $this->auditService = new AuditService();
    }

    public function index(array $query): array
    {
        $plantelId = $this->getPlantelId();
        $conditions = [];

        if ($plantelId !== null) {
            $conditions['plantel_id'] = $plantelId;
        }

        if (isset($query['status'])) {
            $conditions['status'] = $query['status'];
        }

        if (isset($query['category_id'])) {
            $conditions['category_id'] = $query['category_id'];
        }

        // Filtro por rango de fechas
        if (isset($query['start_date']) && isset($query['end_date'])) {
            $expenses = $this->repository->findByDateRange(
                $query['start_date'],
                $query['end_date'],
                $plantelId
            );
            return $this->success($expenses);
        }

        $pagination = $this->getPaginationParams($query);
        $result = $this->repository->paginate(
            $pagination['page'],
            $pagination['per_page'],
            $conditions,
            ['expense_date' => 'DESC']
        );

        return $this->success($result);
    }

    public function show(int $id): array
    {
        $expense = $this->repository->find($id);

        if (!$expense) {
            return $this->notFound('Gasto no encontrado');
        }

        // Verificar acceso al plantel
        $plantelId = $this->getPlantelId();
        if ($plantelId !== null && $expense->plantel_id !== $plantelId) {
            return $this->forbidden('No tiene acceso a este gasto');
        }

        return $this->success($expense);
    }

    public function store(array $body): array
    {
        $errors = $this->validate($body, [
            'category_id' => 'required|integer',
            'description' => 'required|string|min:5|max:500',
            'amount' => 'required|numeric',
            'expense_date' => 'required|date',
            'receipt_number' => 'nullable|string|max:50',
            'vendor' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500'
        ]);

        if (!empty($errors)) {
            return $this->validationError($errors);
        }

        // Verificar categoría
        $category = $this->categoryRepository->find($body['category_id']);
        if (!$category) {
            return $this->validationError([
                'category_id' => ['La categoría no existe']
            ]);
        }

        $plantelId = $this->getPlantelId();
        if ($plantelId === null && !isset($body['plantel_id'])) {
            return $this->validationError([
                'plantel_id' => ['Debe especificar el plantel']
            ]);
        }

        $expense = $this->repository->create([
            'plantel_id' => $plantelId ?? $body['plantel_id'],
            'cycle_id' => $body['cycle_id'] ?? null,
            'category_id' => $body['category_id'],
            'user_id' => $this->getUserId(),
            'description' => $body['description'],
            'amount' => (float) $body['amount'],
            'expense_date' => $body['expense_date'],
            'receipt_number' => $body['receipt_number'] ?? null,
            'vendor' => $body['vendor'] ?? null,
            'notes' => $body['notes'] ?? null,
            'status' => 'pending'
        ]);

        $this->auditService->logCreate('expenses', $expense->getId(), $expense->toArray());

        return $this->created($expense, 'Gasto registrado exitosamente');
    }

    public function update(int $id, array $body): array
    {
        $expense = $this->repository->find($id);

        if (!$expense) {
            return $this->notFound('Gasto no encontrado');
        }

        // Verificar acceso al plantel
        $plantelId = $this->getPlantelId();
        if ($plantelId !== null && $expense->plantel_id !== $plantelId) {
            return $this->forbidden('No tiene acceso a este gasto');
        }

        // No permitir editar gastos aprobados (excepto notas)
        if ($expense->status === 'approved') {
            $body = array_intersect_key($body, ['notes' => true]);
        }

        $errors = $this->validate($body, [
            'category_id' => 'nullable|integer',
            'description' => 'nullable|string|min:5|max:500',
            'amount' => 'nullable|numeric',
            'expense_date' => 'nullable|date',
            'receipt_number' => 'nullable|string|max:50',
            'vendor' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500'
        ]);

        if (!empty($errors)) {
            return $this->validationError($errors);
        }

        $oldValues = $expense->toArray();
        $updated = $this->repository->update($id, $body);

        $this->auditService->logUpdate('expenses', $id, $oldValues, $updated->toArray());

        return $this->success($updated, 'Gasto actualizado exitosamente');
    }

    public function approve(int $id): array
    {
        $expense = $this->repository->find($id);

        if (!$expense) {
            return $this->notFound('Gasto no encontrado');
        }

        // Verificar acceso al plantel
        $plantelId = $this->getPlantelId();
        if ($plantelId !== null && $expense->plantel_id !== $plantelId) {
            return $this->forbidden('No tiene acceso a este gasto');
        }

        if ($expense->status !== 'pending') {
            return $this->error('Solo se pueden aprobar gastos pendientes');
        }

        $oldValues = $expense->toArray();
        $updated = $this->repository->approve($id);

        $this->auditService->logUpdate('expenses', $id, $oldValues, $updated->toArray());

        return $this->success($updated, 'Gasto aprobado exitosamente');
    }

    public function reject(int $id, array $body): array
    {
        $expense = $this->repository->find($id);

        if (!$expense) {
            return $this->notFound('Gasto no encontrado');
        }

        // Verificar acceso al plantel
        $plantelId = $this->getPlantelId();
        if ($plantelId !== null && $expense->plantel_id !== $plantelId) {
            return $this->forbidden('No tiene acceso a este gasto');
        }

        if ($expense->status !== 'pending') {
            return $this->error('Solo se pueden rechazar gastos pendientes');
        }

        $oldValues = $expense->toArray();
        
        // Agregar razón del rechazo a las notas
        $notes = $expense->notes . "\n[RECHAZADO] " . ($body['reason'] ?? 'Sin razón especificada');
        $this->repository->update($id, ['notes' => $notes]);
        
        $updated = $this->repository->reject($id);

        $this->auditService->logUpdate('expenses', $id, $oldValues, $updated->toArray());

        return $this->success($updated, 'Gasto rechazado');
    }

    public function destroy(int $id): array
    {
        $expense = $this->repository->find($id);

        if (!$expense) {
            return $this->notFound('Gasto no encontrado');
        }

        // Verificar acceso al plantel
        $plantelId = $this->getPlantelId();
        if ($plantelId !== null && $expense->plantel_id !== $plantelId) {
            return $this->forbidden('No tiene acceso a este gasto');
        }

        // No permitir eliminar gastos aprobados
        if ($expense->status === 'approved') {
            return $this->error('No se pueden eliminar gastos aprobados');
        }

        $oldValues = $expense->toArray();
        $this->repository->delete($id);
        
        $this->auditService->logDelete('expenses', $id, $oldValues);

        return $this->success(null, 'Gasto eliminado exitosamente');
    }

    public function byCategory(array $query): array
    {
        $startDate = $query['start_date'] ?? date('Y-m-01');
        $endDate = $query['end_date'] ?? date('Y-m-d');
        $plantelId = $this->getPlantelId();

        $data = $this->repository->getByCategory($startDate, $endDate, $plantelId);

        return $this->success([
            'period' => ['start' => $startDate, 'end' => $endDate],
            'data' => $data
        ]);
    }

    public function pending(): array
    {
        $plantelId = $this->getPlantelId();
        $expenses = $this->repository->getPending($plantelId);

        return $this->success($expenses);
    }
}
