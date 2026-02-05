<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\PaymentRepository;
use App\Repositories\ExpenseRepository;
use App\Repositories\StudentRepository;
use App\Repositories\PlantelRepository;
use App\Repositories\MaterialRepository;
use App\Repositories\SchoolCycleRepository;

class DashboardService
{
    private PaymentRepository $paymentRepository;
    private ExpenseRepository $expenseRepository;
    private StudentRepository $studentRepository;
    private PlantelRepository $plantelRepository;
    private MaterialRepository $materialRepository;
    private SchoolCycleRepository $cycleRepository;

    public function __construct()
    {
        $this->paymentRepository = new PaymentRepository();
        $this->expenseRepository = new ExpenseRepository();
        $this->studentRepository = new StudentRepository();
        $this->plantelRepository = new PlantelRepository();
        $this->materialRepository = new MaterialRepository();
        $this->cycleRepository = new SchoolCycleRepository();
    }

    public function getDashboardData(?int $plantelId = null): array
    {
        $today = date('Y-m-d');
        $startOfMonth = date('Y-m-01');
        $endOfMonth = date('Y-m-t');
        $startOfYear = date('Y-01-01');

        return [
            'summary' => $this->getSummaryCards($plantelId, $startOfMonth, $endOfMonth),
            'charts' => [
                'income_vs_expenses' => $this->getIncomeVsExpensesChart($plantelId, $startOfYear, $today),
                'income_by_concept' => $this->getIncomeByConceptChart($plantelId, $startOfMonth, $endOfMonth),
                'expenses_by_category' => $this->getExpensesByCategoryChart($plantelId, $startOfMonth, $endOfMonth),
                'payment_methods' => $this->getPaymentMethodsChart($plantelId, $startOfMonth, $endOfMonth),
                'daily_income' => $this->getDailyIncomeChart($plantelId, $startOfMonth, $endOfMonth)
            ],
            'recent' => [
                'payments' => $this->getRecentPayments($plantelId, 5),
                'expenses' => $this->getRecentExpenses($plantelId, 5)
            ],
            'alerts' => $this->getAlerts($plantelId),
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }

    private function getSummaryCards(?int $plantelId, string $startDate, string $endDate): array
    {
        $todayIncome = $this->paymentRepository->getTotalByDateRange(date('Y-m-d'), date('Y-m-d'), $plantelId);
        $todayExpenses = $this->expenseRepository->getTotalByDateRange(date('Y-m-d'), date('Y-m-d'), $plantelId);
        
        $monthIncome = $this->paymentRepository->getTotalByDateRange($startDate, $endDate, $plantelId);
        $monthExpenses = $this->expenseRepository->getTotalByDateRange($startDate, $endDate, $plantelId);

        // Comparar con mes anterior
        $prevMonthStart = date('Y-m-01', strtotime('-1 month'));
        $prevMonthEnd = date('Y-m-t', strtotime('-1 month'));
        $prevMonthIncome = $this->paymentRepository->getTotalByDateRange($prevMonthStart, $prevMonthEnd, $plantelId);
        $prevMonthExpenses = $this->expenseRepository->getTotalByDateRange($prevMonthStart, $prevMonthEnd, $plantelId);

        $incomeChange = $prevMonthIncome > 0 
            ? round((($monthIncome - $prevMonthIncome) / $prevMonthIncome) * 100, 1) 
            : 0;
        $expenseChange = $prevMonthExpenses > 0 
            ? round((($monthExpenses - $prevMonthExpenses) / $prevMonthExpenses) * 100, 1) 
            : 0;

        // Conteo de estudiantes
        $activeStudents = $plantelId 
            ? $this->studentRepository->countByPlantel($plantelId, 'active')
            : $this->studentRepository->count(['status' => 'active']);

        // Gastos pendientes
        $pendingExpenses = count($this->expenseRepository->getPending($plantelId));

        return [
            'today' => [
                'income' => round($todayIncome, 2),
                'expenses' => round($todayExpenses, 2),
                'balance' => round($todayIncome - $todayExpenses, 2)
            ],
            'month' => [
                'income' => round($monthIncome, 2),
                'income_change' => $incomeChange,
                'expenses' => round($monthExpenses, 2),
                'expenses_change' => $expenseChange,
                'balance' => round($monthIncome - $monthExpenses, 2)
            ],
            'students' => [
                'active' => $activeStudents
            ],
            'pending_expenses' => $pendingExpenses
        ];
    }

    private function getIncomeVsExpensesChart(?int $plantelId, string $startDate, string $endDate): array
    {
        // Agrupar por mes
        $db = \App\Database::getInstance();
        
        $incomeSql = "SELECT DATE_FORMAT(payment_date, '%Y-%m') as month, 
                             SUM(total) as total
                      FROM payments
                      WHERE payment_date BETWEEN :start AND :end
                      AND status = 'completed'";
        
        $expenseSql = "SELECT DATE_FORMAT(expense_date, '%Y-%m') as month,
                              SUM(amount) as total
                       FROM expenses
                       WHERE expense_date BETWEEN :start AND :end
                       AND status = 'approved'";
        
        $params = ['start' => $startDate, 'end' => $endDate];

        if ($plantelId !== null) {
            $incomeSql .= " AND plantel_id = :plantel_id";
            $expenseSql .= " AND plantel_id = :plantel_id";
            $params['plantel_id'] = $plantelId;
        }

        $incomeSql .= " GROUP BY month ORDER BY month";
        $expenseSql .= " GROUP BY month ORDER BY month";

        $incomeData = $db->fetchAll($incomeSql, $params);
        $expenseData = $db->fetchAll($expenseSql, $params);

        // Combinar datos
        $months = [];
        foreach ($incomeData as $row) {
            $months[$row['month']]['income'] = (float) $row['total'];
        }
        foreach ($expenseData as $row) {
            $months[$row['month']]['expenses'] = (float) $row['total'];
        }

        ksort($months);

        $labels = [];
        $income = [];
        $expenses = [];

        foreach ($months as $month => $data) {
            $labels[] = $month;
            $income[] = round($data['income'] ?? 0, 2);
            $expenses[] = round($data['expenses'] ?? 0, 2);
        }

        return [
            'type' => 'line',
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Ingresos',
                    'data' => $income,
                    'borderColor' => '#10B981',
                    'backgroundColor' => 'rgba(16, 185, 129, 0.1)'
                ],
                [
                    'label' => 'Gastos',
                    'data' => $expenses,
                    'borderColor' => '#EF4444',
                    'backgroundColor' => 'rgba(239, 68, 68, 0.1)'
                ]
            ]
        ];
    }

    private function getIncomeByConceptChart(?int $plantelId, string $startDate, string $endDate): array
    {
        $db = \App\Database::getInstance();
        
        $sql = "SELECT concept_type, SUM(total) as total
                FROM payments
                WHERE payment_date BETWEEN :start AND :end
                AND status = 'completed'";
        
        $params = ['start' => $startDate, 'end' => $endDate . ' 23:59:59'];

        if ($plantelId !== null) {
            $sql .= " AND plantel_id = :plantel_id";
            $params['plantel_id'] = $plantelId;
        }

        $sql .= " GROUP BY concept_type ORDER BY total DESC";

        $data = $db->fetchAll($sql, $params);

        $conceptLabels = [
            'monthly' => 'Mensualidades',
            'enrollment' => 'Inscripciones',
            'material' => 'Materiales',
            'other' => 'Otros'
        ];

        $colors = [
            'monthly' => '#3B82F6',
            'enrollment' => '#10B981',
            'material' => '#F59E0B',
            'other' => '#6B7280'
        ];

        $labels = [];
        $values = [];
        $bgColors = [];

        foreach ($data as $row) {
            $labels[] = $conceptLabels[$row['concept_type']] ?? $row['concept_type'];
            $values[] = round((float) $row['total'], 2);
            $bgColors[] = $colors[$row['concept_type']] ?? '#6B7280';
        }

        return [
            'type' => 'doughnut',
            'labels' => $labels,
            'datasets' => [
                [
                    'data' => $values,
                    'backgroundColor' => $bgColors
                ]
            ]
        ];
    }

    private function getExpensesByCategoryChart(?int $plantelId, string $startDate, string $endDate): array
    {
        $data = $this->expenseRepository->getByCategory($startDate, $endDate, $plantelId);

        $labels = [];
        $values = [];
        $bgColors = [];

        foreach ($data as $row) {
            $labels[] = $row['name'];
            $values[] = round((float) $row['total'], 2);
            $bgColors[] = $row['color'] ?? '#6B7280';
        }

        return [
            'type' => 'pie',
            'labels' => $labels,
            'datasets' => [
                [
                    'data' => $values,
                    'backgroundColor' => $bgColors
                ]
            ]
        ];
    }

    private function getPaymentMethodsChart(?int $plantelId, string $startDate, string $endDate): array
    {
        $data = $this->paymentRepository->getByPaymentMethod($startDate, $endDate, $plantelId);

        $methodLabels = [
            'cash' => 'Efectivo',
            'card' => 'Tarjeta',
            'transfer' => 'Transferencia',
            'check' => 'Cheque'
        ];

        $colors = [
            'cash' => '#10B981',
            'card' => '#3B82F6',
            'transfer' => '#8B5CF6',
            'check' => '#F59E0B'
        ];

        $labels = [];
        $values = [];
        $bgColors = [];

        foreach ($data as $row) {
            $labels[] = $methodLabels[$row['payment_method']] ?? $row['payment_method'];
            $values[] = round((float) $row['total'], 2);
            $bgColors[] = $colors[$row['payment_method']] ?? '#6B7280';
        }

        return [
            'type' => 'bar',
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Total por método',
                    'data' => $values,
                    'backgroundColor' => $bgColors
                ]
            ]
        ];
    }

    private function getDailyIncomeChart(?int $plantelId, string $startDate, string $endDate): array
    {
        $data = $this->paymentRepository->getDailyTotals($startDate, $endDate, $plantelId);

        $labels = [];
        $values = [];

        foreach ($data as $row) {
            $labels[] = date('d M', strtotime($row['date']));
            $values[] = round((float) $row['total'], 2);
        }

        return [
            'type' => 'bar',
            'labels' => $labels,
            'datasets' => [
                [
                    'label' => 'Ingresos diarios',
                    'data' => $values,
                    'backgroundColor' => '#3B82F6'
                ]
            ]
        ];
    }

    private function getRecentPayments(?int $plantelId, int $limit): array
    {
        $conditions = ['status' => 'completed'];
        if ($plantelId !== null) {
            $conditions['plantel_id'] = $plantelId;
        }

        return $this->paymentRepository->all($conditions, ['payment_date' => 'DESC'], $limit);
    }

    private function getRecentExpenses(?int $plantelId, int $limit): array
    {
        $conditions = ['status' => 'approved'];
        if ($plantelId !== null) {
            $conditions['plantel_id'] = $plantelId;
        }

        return $this->expenseRepository->all($conditions, ['expense_date' => 'DESC'], $limit);
    }

    private function getAlerts(?int $plantelId): array
    {
        $alerts = [];

        // Materiales con stock bajo
        if ($plantelId !== null) {
            $lowStock = $this->materialRepository->getLowStock($plantelId);
            foreach ($lowStock as $material) {
                $alerts[] = [
                    'type' => 'warning',
                    'icon' => 'package',
                    'message' => "Stock bajo: {$material->name} ({$material->stock} unidades)",
                    'action' => "/materials/{$material->getId()}"
                ];
            }
        }

        // Gastos pendientes de aprobar
        $pendingExpenses = $this->expenseRepository->getPending($plantelId);
        if (count($pendingExpenses) > 0) {
            $alerts[] = [
                'type' => 'info',
                'icon' => 'clock',
                'message' => count($pendingExpenses) . " gasto(s) pendiente(s) de aprobar",
                'action' => "/expenses?status=pending"
            ];
        }

        return $alerts;
    }
}
