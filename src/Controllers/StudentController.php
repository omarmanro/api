<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\StudentRepository;
use App\Services\AuditService;

class StudentController extends BaseController
{
    private StudentRepository $repository;
    private AuditService $auditService;

    public function __construct()
    {
        $this->repository = new StudentRepository();
        $this->auditService = new AuditService();
    }

    public function index(array $query): array
    {
        $plantelId = $this->getPlantelId();

        if (isset($query['search']) && !empty($query['search'])) {
            $students = $this->repository->search($query['search'], $plantelId);
            return $this->success($students);
        }

        $conditions = [];
        if ($plantelId !== null) {
            $conditions['plantel_id'] = $plantelId;
        }

        if (isset($query['status'])) {
            $conditions['status'] = $query['status'];
        }

        $pagination = $this->getPaginationParams($query);
        $result = $this->repository->paginate(
            $pagination['page'],
            $pagination['per_page'],
            $conditions,
            ['last_name' => 'ASC', 'first_name' => 'ASC']
        );

        return $this->success($result);
    }

    public function show(int $id): array
    {
        $student = $this->repository->getWithAcademicInfo($id);

        if (!$student) {
            return $this->notFound('Estudiante no encontrado');
        }

        // Verificar acceso al plantel
        $plantelId = $this->getPlantelId();
        if ($plantelId !== null && $student['plantel_id'] !== $plantelId) {
            return $this->forbidden('No tiene acceso a este estudiante');
        }

        return $this->success($student);
    }

    public function store(array $body): array
    {
        $errors = $this->validate($body, [
            'student_id' => 'required|string|max:20',
            'first_name' => 'required|string|min:2|max:50',
            'last_name' => 'required|string|min:2|max:50',
            'email' => 'required|email',
            'phone' => 'nullable|string|max:20',
            'birth_date' => 'nullable|date',
            'curp' => 'nullable|string|min:18|max:18',
            'gender' => 'nullable|in:M,F,O',
            'blood_type' => 'nullable|string|max:5'
        ]);

        if (!empty($errors)) {
            return $this->validationError($errors);
        }

        // Verificar matrícula única
        if ($this->repository->findByStudentId($body['student_id'])) {
            return $this->validationError([
                'student_id' => ['La matrícula ya está en uso']
            ]);
        }

        // Verificar CURP única si se proporciona
        if (!empty($body['curp']) && $this->repository->findByCurp($body['curp'])) {
            return $this->validationError([
                'curp' => ['El CURP ya está registrado']
            ]);
        }

        $plantelId = $this->getPlantelId();
        if ($plantelId === null && !isset($body['plantel_id'])) {
            return $this->validationError([
                'plantel_id' => ['Debe especificar el plantel']
            ]);
        }

        $student = $this->repository->create([
            'plantel_id' => $plantelId ?? $body['plantel_id'],
            'student_id' => strtoupper($body['student_id']),
            'first_name' => $body['first_name'],
            'last_name' => $body['last_name'],
            'email' => $body['email'],
            'phone' => $body['phone'] ?? null,
            'birth_date' => $body['birth_date'] ?? null,
            'curp' => isset($body['curp']) ? strtoupper($body['curp']) : null,
            'gender' => $body['gender'] ?? null,
            'blood_type' => $body['blood_type'] ?? null,
            'status' => 'active'
        ]);

        $this->auditService->logCreate('students', $student->getId(), $student->toArray());

        return $this->created($student, 'Estudiante registrado exitosamente');
    }

    public function update(int $id, array $body): array
    {
        $student = $this->repository->find($id);

        if (!$student) {
            return $this->notFound('Estudiante no encontrado');
        }

        // Verificar acceso al plantel
        $plantelId = $this->getPlantelId();
        if ($plantelId !== null && $student->plantel_id !== $plantelId) {
            return $this->forbidden('No tiene acceso a este estudiante');
        }

        $errors = $this->validate($body, [
            'first_name' => 'nullable|string|min:2|max:50',
            'last_name' => 'nullable|string|min:2|max:50',
            'email' => 'nullable|email',
            'phone' => 'nullable|string|max:20',
            'birth_date' => 'nullable|date',
            'curp' => 'nullable|string|min:18|max:18',
            'gender' => 'nullable|in:M,F,O',
            'blood_type' => 'nullable|string|max:5',
            'status' => 'nullable|in:active,inactive,graduated,suspended'
        ]);

        if (!empty($errors)) {
            return $this->validationError($errors);
        }

        // Verificar CURP única si se está cambiando
        if (!empty($body['curp']) && strtoupper($body['curp']) !== $student->curp) {
            if ($this->repository->findByCurp($body['curp'])) {
                return $this->validationError([
                    'curp' => ['El CURP ya está registrado']
                ]);
            }
            $body['curp'] = strtoupper($body['curp']);
        }

        $oldValues = $student->toArray();
        $updated = $this->repository->update($id, $body);

        $this->auditService->logUpdate('students', $id, $oldValues, $updated->toArray());

        return $this->success($updated, 'Estudiante actualizado exitosamente');
    }

    public function destroy(int $id): array
    {
        $student = $this->repository->find($id);

        if (!$student) {
            return $this->notFound('Estudiante no encontrado');
        }

        // Verificar acceso al plantel
        $plantelId = $this->getPlantelId();
        if ($plantelId !== null && $student->plantel_id !== $plantelId) {
            return $this->forbidden('No tiene acceso a este estudiante');
        }

        $oldValues = $student->toArray();
        $this->repository->softDelete($id);
        
        $this->auditService->logDelete('students', $id, $oldValues);

        return $this->success(null, 'Estudiante eliminado exitosamente');
    }

    public function payments(int $id): array
    {
        $student = $this->repository->find($id);

        if (!$student) {
            return $this->notFound('Estudiante no encontrado');
        }

        $paymentRepo = new \App\Repositories\PaymentRepository();
        $payments = $paymentRepo->findByStudent($id);

        return $this->success([
            'student' => $student,
            'payments' => $payments
        ]);
    }
}
