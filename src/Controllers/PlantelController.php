<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\PlantelRepository;
use App\Services\AuditService;

class PlantelController extends BaseController
{
    private PlantelRepository $repository;
    private AuditService $auditService;

    public function __construct()
    {
        $this->repository = new PlantelRepository();
        $this->auditService = new AuditService();
    }

    public function index(array $query): array
    {
        if (isset($query['search']) && !empty($query['search'])) {
            $planteles = $this->repository->search($query['search']);
            return $this->success($planteles);
        }

        $pagination = $this->getPaginationParams($query);
        $result = $this->repository->paginate(
            $pagination['page'],
            $pagination['per_page'],
            ['status' => 'active'],
            ['name' => 'ASC']
        );

        return $this->success($result);
    }

    public function show(int $id): array
    {
        $plantel = $this->repository->find($id);

        if (!$plantel) {
            return $this->notFound('Plantel no encontrado');
        }

        return $this->success($plantel);
    }

    public function store(array $body): array
    {
        $errors = $this->validate($body, [
            'name' => 'required|string|min:3|max:100',
            'code' => 'required|string|min:2|max:20',
            'address' => 'required|string',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'director_name' => 'nullable|string|max:100'
        ]);

        if (!empty($errors)) {
            return $this->validationError($errors);
        }

        // Verificar código único
        if ($this->repository->findByCode($body['code'])) {
            return $this->validationError([
                'code' => ['El código ya está en uso']
            ]);
        }

        $plantel = $this->repository->create([
            'name' => $body['name'],
            'code' => strtoupper($body['code']),
            'address' => $body['address'],
            'phone' => $body['phone'] ?? null,
            'email' => $body['email'] ?? null,
            'director_name' => $body['director_name'] ?? null,
            'status' => 'active'
        ]);

        $this->auditService->logCreate('planteles', $plantel->getId(), $plantel->toArray());

        return $this->created($plantel, 'Plantel creado exitosamente');
    }

    public function update(int $id, array $body): array
    {
        $plantel = $this->repository->find($id);

        if (!$plantel) {
            return $this->notFound('Plantel no encontrado');
        }

        $errors = $this->validate($body, [
            'name' => 'nullable|string|min:3|max:100',
            'code' => 'nullable|string|min:2|max:20',
            'address' => 'nullable|string',
            'phone' => 'nullable|string|max:20',
            'email' => 'nullable|email',
            'director_name' => 'nullable|string|max:100',
            'status' => 'nullable|in:active,inactive'
        ]);

        if (!empty($errors)) {
            return $this->validationError($errors);
        }

        // Verificar código único si se está cambiando
        if (isset($body['code']) && $body['code'] !== $plantel->code) {
            if ($this->repository->findByCode($body['code'])) {
                return $this->validationError([
                    'code' => ['El código ya está en uso']
                ]);
            }
            $body['code'] = strtoupper($body['code']);
        }

        $oldValues = $plantel->toArray();
        $updated = $this->repository->update($id, $body);

        $this->auditService->logUpdate('planteles', $id, $oldValues, $updated->toArray());

        return $this->success($updated, 'Plantel actualizado exitosamente');
    }

    public function destroy(int $id): array
    {
        $plantel = $this->repository->find($id);

        if (!$plantel) {
            return $this->notFound('Plantel no encontrado');
        }

        $oldValues = $plantel->toArray();
        $this->repository->softDelete($id);
        
        $this->auditService->logDelete('planteles', $id, $oldValues);

        return $this->success(null, 'Plantel eliminado exitosamente');
    }
}
