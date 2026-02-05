<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Repositories\PaymentRepository;
use App\Repositories\StudentRepository;
use App\Services\AuditService;

class PaymentController extends BaseController
{
    private PaymentRepository $repository;
    private StudentRepository $studentRepository;
    private AuditService $auditService;

    public function __construct()
    {
        $this->repository = new PaymentRepository();
        $this->studentRepository = new StudentRepository();
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

        if (isset($query['concept_type'])) {
            $conditions['concept_type'] = $query['concept_type'];
        }

        if (isset($query['payment_method'])) {
            $conditions['payment_method'] = $query['payment_method'];
        }

        // Filtro por rango de fechas
        if (isset($query['start_date']) && isset($query['end_date'])) {
            $payments = $this->repository->findByDateRange(
                $query['start_date'],
                $query['end_date'],
                $plantelId
            );
            return $this->success($payments);
        }

        $pagination = $this->getPaginationParams($query);
        $result = $this->repository->paginate(
            $pagination['page'],
            $pagination['per_page'],
            $conditions,
            ['payment_date' => 'DESC']
        );

        return $this->success($result);
    }

    public function show(int $id): array
    {
        $payment = $this->repository->find($id);

        if (!$payment) {
            return $this->notFound('Pago no encontrado');
        }

        // Verificar acceso al plantel
        $plantelId = $this->getPlantelId();
        if ($plantelId !== null && $payment->plantel_id !== $plantelId) {
            return $this->forbidden('No tiene acceso a este pago');
        }

        return $this->success($payment);
    }

    public function store(array $body): array
    {
        $errors = $this->validate($body, [
            'student_id' => 'required|integer',
            'concept_type' => 'required|in:monthly,enrollment,material,other',
            'amount' => 'required|numeric',
            'payment_method' => 'required|in:cash,card,transfer,check',
            'payment_date' => 'nullable|date',
            'discount' => 'nullable|numeric',
            'surcharge' => 'nullable|numeric',
            'concept_id' => 'nullable|integer',
            'notes' => 'nullable|string|max:500'
        ]);

        if (!empty($errors)) {
            return $this->validationError($errors);
        }

        // Verificar estudiante
        $student = $this->studentRepository->find($body['student_id']);
        if (!$student) {
            return $this->validationError([
                'student_id' => ['El estudiante no existe']
            ]);
        }

        $plantelId = $this->getPlantelId() ?? $student->plantel_id;

        // Calcular total
        $amount = (float) $body['amount'];
        $discount = (float) ($body['discount'] ?? 0);
        $surcharge = (float) ($body['surcharge'] ?? 0);
        $total = $amount - $discount + $surcharge;

        $payment = $this->repository->create([
            'plantel_id' => $plantelId,
            'student_id' => $body['student_id'],
            'cycle_id' => $body['cycle_id'] ?? null,
            'user_id' => $this->getUserId(),
            'concept_type' => $body['concept_type'],
            'concept_id' => $body['concept_id'] ?? null,
            'reference_number' => $this->repository->generateReferenceNumber(),
            'amount' => $amount,
            'discount' => $discount,
            'surcharge' => $surcharge,
            'total' => $total,
            'payment_method' => $body['payment_method'],
            'payment_date' => $body['payment_date'] ?? date('Y-m-d H:i:s'),
            'notes' => $body['notes'] ?? null,
            'status' => 'completed'
        ]);

        $this->auditService->logCreate('payments', $payment->getId(), $payment->toArray());

        return $this->created($payment, 'Pago registrado exitosamente');
    }

    public function update(int $id, array $body): array
    {
        $payment = $this->repository->find($id);

        if (!$payment) {
            return $this->notFound('Pago no encontrado');
        }

        // Verificar acceso al plantel
        $plantelId = $this->getPlantelId();
        if ($plantelId !== null && $payment->plantel_id !== $plantelId) {
            return $this->forbidden('No tiene acceso a este pago');
        }

        // Solo permitir actualizar notas y estado en pagos completados
        if ($payment->status === 'completed') {
            $allowedFields = ['notes', 'status'];
            $body = array_intersect_key($body, array_flip($allowedFields));
        }

        $errors = $this->validate($body, [
            'notes' => 'nullable|string|max:500',
            'status' => 'nullable|in:completed,pending,cancelled,refunded'
        ]);

        if (!empty($errors)) {
            return $this->validationError($errors);
        }

        $oldValues = $payment->toArray();
        $updated = $this->repository->update($id, $body);

        $this->auditService->logUpdate('payments', $id, $oldValues, $updated->toArray());

        return $this->success($updated, 'Pago actualizado exitosamente');
    }

    public function cancel(int $id, array $body): array
    {
        $payment = $this->repository->find($id);

        if (!$payment) {
            return $this->notFound('Pago no encontrado');
        }

        // Verificar acceso al plantel
        $plantelId = $this->getPlantelId();
        if ($plantelId !== null && $payment->plantel_id !== $plantelId) {
            return $this->forbidden('No tiene acceso a este pago');
        }

        if ($payment->status === 'cancelled') {
            return $this->error('El pago ya está cancelado');
        }

        $oldValues = $payment->toArray();
        $updated = $this->repository->update($id, [
            'status' => 'cancelled',
            'notes' => $payment->notes . "\n[CANCELADO] " . ($body['reason'] ?? 'Sin razón especificada')
        ]);

        $this->auditService->logUpdate('payments', $id, $oldValues, $updated->toArray());

        return $this->success($updated, 'Pago cancelado exitosamente');
    }

    public function byMethod(array $query): array
    {
        $startDate = $query['start_date'] ?? date('Y-m-01');
        $endDate = $query['end_date'] ?? date('Y-m-d');
        $plantelId = $this->getPlantelId();

        $data = $this->repository->getByPaymentMethod($startDate, $endDate, $plantelId);

        return $this->success([
            'period' => ['start' => $startDate, 'end' => $endDate],
            'data' => $data
        ]);
    }

    public function daily(array $query): array
    {
        $startDate = $query['start_date'] ?? date('Y-m-01');
        $endDate = $query['end_date'] ?? date('Y-m-d');
        $plantelId = $this->getPlantelId();

        $data = $this->repository->getDailyTotals($startDate, $endDate, $plantelId);

        return $this->success([
            'period' => ['start' => $startDate, 'end' => $endDate],
            'data' => $data
        ]);
    }
}
