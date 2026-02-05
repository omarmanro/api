<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\SchoolCycleRepository;
use App\Services\AuditService;

class SchoolCycleController extends BaseController
{
    private SchoolCycleRepository $repository;
    private AuditService $auditService;

    public function __construct()
    {
        $this->repository = new SchoolCycleRepository();
        $this->auditService = new AuditService();
    }

    public function index(array $query): array
    {
        $pagination = $this->getPaginationParams($query);
        $result = $this->repository->paginate(
            $pagination['page'],
            $pagination['per_page'],
            [],
            ['start_date' => 'DESC']
        );

        return $this->success($result);
    }

    public function active(): array
    {
        $cycle = $this->repository->getActive();

        if (!$cycle) {
            return $this->notFound('No hay ciclo activo');
        }

        return $this->success($cycle);
    }

    public function show(int $id): array
    {
        $cycle = $this->repository->find($id);

        if (!$cycle) {
            return $this->notFound('Ciclo escolar no encontrado');
        }

        return $this->success($cycle);
    }

    public function store(array $body): array
    {
        $errors = $this->validate($body, [
            'name' => 'required|string|min:4|max:20',
            'description' => 'nullable|string|max:200',
            'start_date' => 'required|date',
            'end_date' => 'required|date'
        ]);

        if (!empty($errors)) {
            return $this->validationError($errors);
        }

        // Verificar nombre único
        if ($this->repository->findByName($body['name'])) {
            return $this->validationError([
                'name' => ['El ciclo ya existe']
            ]);
        }

        // Verificar que end_date > start_date
        if (strtotime($body['end_date']) <= strtotime($body['start_date'])) {
            return $this->validationError([
                'end_date' => ['La fecha de fin debe ser posterior a la fecha de inicio']
            ]);
        }

        $cycle = $this->repository->create([
            'name' => $body['name'],
            'description' => $body['description'] ?? null,
            'start_date' => $body['start_date'],
            'end_date' => $body['end_date'],
            'status' => 'upcoming'
        ]);

        $this->auditService->logCreate('school_cycles', $cycle->getId(), $cycle->toArray());

        return $this->created($cycle, 'Ciclo escolar creado exitosamente');
    }

    public function update(int $id, array $body): array
    {
        $cycle = $this->repository->find($id);

        if (!$cycle) {
            return $this->notFound('Ciclo escolar no encontrado');
        }

        $errors = $this->validate($body, [
            'name' => 'nullable|string|min:4|max:20',
            'description' => 'nullable|string|max:200',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date',
            'status' => 'nullable|in:active,closed,upcoming'
        ]);

        if (!empty($errors)) {
            return $this->validationError($errors);
        }

        // Verificar nombre único si se está cambiando
        if (isset($body['name']) && $body['name'] !== $cycle->name) {
            if ($this->repository->findByName($body['name'])) {
                return $this->validationError([
                    'name' => ['El ciclo ya existe']
                ]);
            }
        }

        $oldValues = $cycle->toArray();
        $updated = $this->repository->update($id, $body);

        $this->auditService->logUpdate('school_cycles', $id, $oldValues, $updated->toArray());

        return $this->success($updated, 'Ciclo escolar actualizado exitosamente');
    }

    public function activate(int $id): array
    {
        $cycle = $this->repository->find($id);

        if (!$cycle) {
            return $this->notFound('Ciclo escolar no encontrado');
        }

        if ($cycle->isActive()) {
            return $this->error('El ciclo ya está activo');
        }

        $oldValues = $cycle->toArray();
        $updated = $this->repository->activate($id);

        $this->auditService->logUpdate('school_cycles', $id, $oldValues, $updated->toArray());

        return $this->success($updated, 'Ciclo escolar activado exitosamente');
    }

    public function destroy(int $id): array
    {
        $cycle = $this->repository->find($id);

        if (!$cycle) {
            return $this->notFound('Ciclo escolar no encontrado');
        }

        // No permitir eliminar ciclo activo
        if ($cycle->isActive()) {
            return $this->error('No se puede eliminar un ciclo activo');
        }

        $oldValues = $cycle->toArray();
        $this->repository->delete($id);
        
        $this->auditService->logDelete('school_cycles', $id, $oldValues);

        return $this->success(null, 'Ciclo escolar eliminado exitosamente');
    }
}
