<?php

declare(strict_types=1);

namespace App\Services;

use App\Repositories\PaymentRepository;
use App\Repositories\ExpenseRepository;
use App\Repositories\PlantelRepository;

class ReportService
{
    private PaymentRepository $paymentRepository;
    private ExpenseRepository $expenseRepository;
    private PlantelRepository $plantelRepository;

    public function __construct()
    {
        $this->paymentRepository = new PaymentRepository();
        $this->expenseRepository = new ExpenseRepository();
        $this->plantelRepository = new PlantelRepository();
    }

    public function getDailyReport(string $date, ?int $plantelId = null): array
    {
        $startDate = $date;
        $endDate = $date;

        return $this->generateReport($startDate, $endDate, $plantelId, 'daily');
    }

    public function getWeeklyReport(string $startDate, ?int $plantelId = null): array
    {
        $start = new \DateTime($startDate);
        $start->modify('monday this week');
        $end = clone $start;
        $end->modify('sunday this week');

        return $this->generateReport(
            $start->format('Y-m-d'),
            $end->format('Y-m-d'),
            $plantelId,
            'weekly'
        );
    }

    public function getMonthlyReport(int $year, int $month, ?int $plantelId = null): array
    {
        $startDate = sprintf('%d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));

        return $this->generateReport($startDate, $endDate, $plantelId, 'monthly');
    }

    public function getCustomReport(string $startDate, string $endDate, ?int $plantelId = null): array
    {
        return $this->generateReport($startDate, $endDate, $plantelId, 'custom');
    }

    private function generateReport(string $startDate, string $endDate, ?int $plantelId, string $type): array
    {
        // Obtener ingresos
        $payments = $this->paymentRepository->findByDateRange($startDate, $endDate, $plantelId);
        $totalIncome = $this->paymentRepository->getTotalByDateRange($startDate, $endDate, $plantelId);
        $incomeByMethod = $this->paymentRepository->getByPaymentMethod($startDate, $endDate, $plantelId);
        $dailyIncome = $this->paymentRepository->getDailyTotals($startDate, $endDate, $plantelId);

        // Obtener gastos
        $expenses = $this->expenseRepository->findByDateRange($startDate, $endDate, $plantelId);
        $totalExpenses = $this->expenseRepository->getTotalByDateRange($startDate, $endDate, $plantelId);
        $expensesByCategory = $this->expenseRepository->getByCategory($startDate, $endDate, $plantelId);
        $dailyExpenses = $this->expenseRepository->getDailyTotals($startDate, $endDate, $plantelId);

        // Calcular balance
        $balance = $totalIncome - $totalExpenses;

        // Información del plantel si aplica
        $plantelInfo = null;
        if ($plantelId !== null) {
            $plantel = $this->plantelRepository->find($plantelId);
            $plantelInfo = $plantel?->toArray();
        }

        return [
            'report_type' => $type,
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate
            ],
            'plantel' => $plantelInfo,
            'summary' => [
                'total_income' => round($totalIncome, 2),
                'total_expenses' => round($totalExpenses, 2),
                'balance' => round($balance, 2),
                'payment_count' => count($payments),
                'expense_count' => count($expenses)
            ],
            'income' => [
                'total' => round($totalIncome, 2),
                'by_payment_method' => $incomeByMethod,
                'daily' => $dailyIncome,
                'transactions' => $payments
            ],
            'expenses' => [
                'total' => round($totalExpenses, 2),
                'by_category' => $expensesByCategory,
                'daily' => $dailyExpenses,
                'transactions' => $expenses
            ],
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }

    public function getConsolidatedReport(string $startDate, string $endDate): array
    {
        $planteles = $this->plantelRepository->getActive();
        $consolidatedData = [];
        $totals = [
            'total_income' => 0,
            'total_expenses' => 0,
            'balance' => 0
        ];

        foreach ($planteles as $plantel) {
            $report = $this->generateReport($startDate, $endDate, $plantel->getId(), 'consolidated');
            
            $consolidatedData[] = [
                'plantel_id' => $plantel->getId(),
                'plantel_name' => $plantel->name,
                'plantel_code' => $plantel->code,
                'income' => $report['summary']['total_income'],
                'expenses' => $report['summary']['total_expenses'],
                'balance' => $report['summary']['balance'],
                'payment_count' => $report['summary']['payment_count'],
                'expense_count' => $report['summary']['expense_count']
            ];

            $totals['total_income'] += $report['summary']['total_income'];
            $totals['total_expenses'] += $report['summary']['total_expenses'];
        }

        $totals['balance'] = $totals['total_income'] - $totals['total_expenses'];

        return [
            'report_type' => 'consolidated',
            'period' => [
                'start_date' => $startDate,
                'end_date' => $endDate
            ],
            'totals' => [
                'total_income' => round($totals['total_income'], 2),
                'total_expenses' => round($totals['total_expenses'], 2),
                'balance' => round($totals['balance'], 2)
            ],
            'by_plantel' => $consolidatedData,
            'generated_at' => date('Y-m-d H:i:s')
        ];
    }

    public function getIncomeByConceptType(string $startDate, string $endDate, ?int $plantelId = null): array
    {
        $sql = "SELECT concept_type, 
                       COUNT(*) as count,
                       SUM(total) as total
                FROM payments
                WHERE payment_date BETWEEN :start_date AND :end_date
                AND status = 'completed'";
        
        $params = [
            'start_date' => $startDate,
            'end_date' => $endDate . ' 23:59:59'
        ];

        if ($plantelId !== null) {
            $sql .= " AND plantel_id = :plantel_id";
            $params['plantel_id'] = $plantelId;
        }

        $sql .= " GROUP BY concept_type ORDER BY total DESC";

        $db = \App\Database::getInstance();
        return $db->fetchAll($sql, $params);
    }
}
