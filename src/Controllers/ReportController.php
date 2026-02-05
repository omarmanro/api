<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Services\ReportService;
use App\Services\ExcelExporter;

class ReportController extends BaseController
{
    private ReportService $reportService;
    private ExcelExporter $excelExporter;

    public function __construct()
    {
        $this->reportService = new ReportService();
        $this->excelExporter = new ExcelExporter();
    }

    public function daily(array $query): array
    {
        $date = $query['date'] ?? date('Y-m-d');
        $plantelId = $this->getPlantelId();

        $report = $this->reportService->getDailyReport($date, $plantelId);

        return $this->success($report);
    }

    public function weekly(array $query): array
    {
        $startDate = $query['start_date'] ?? date('Y-m-d');
        $plantelId = $this->getPlantelId();

        $report = $this->reportService->getWeeklyReport($startDate, $plantelId);

        return $this->success($report);
    }

    public function monthly(array $query): array
    {
        $year = (int) ($query['year'] ?? date('Y'));
        $month = (int) ($query['month'] ?? date('m'));
        $plantelId = $this->getPlantelId();

        $report = $this->reportService->getMonthlyReport($year, $month, $plantelId);

        return $this->success($report);
    }

    public function custom(array $query): array
    {
        $errors = $this->validate($query, [
            'start_date' => 'required|date',
            'end_date' => 'required|date'
        ]);

        if (!empty($errors)) {
            return $this->validationError($errors);
        }

        $plantelId = $this->getPlantelId();

        $report = $this->reportService->getCustomReport(
            $query['start_date'],
            $query['end_date'],
            $plantelId
        );

        return $this->success($report);
    }

    public function consolidated(array $query): array
    {
        // Solo admin puede ver el consolidado
        $user = $this->getUser();
        if ($user['role'] !== 'admin') {
            return $this->forbidden('Solo el administrador puede ver el reporte consolidado');
        }

        $errors = $this->validate($query, [
            'start_date' => 'required|date',
            'end_date' => 'required|date'
        ]);

        if (!empty($errors)) {
            return $this->validationError($errors);
        }

        $report = $this->reportService->getConsolidatedReport(
            $query['start_date'],
            $query['end_date']
        );

        return $this->success($report);
    }

    public function incomeByType(array $query): array
    {
        $startDate = $query['start_date'] ?? date('Y-m-01');
        $endDate = $query['end_date'] ?? date('Y-m-d');
        $plantelId = $this->getPlantelId();

        $data = $this->reportService->getIncomeByConceptType($startDate, $endDate, $plantelId);

        return $this->success([
            'period' => ['start' => $startDate, 'end' => $endDate],
            'data' => $data
        ]);
    }

    public function exportDaily(array $query): array
    {
        $date = $query['date'] ?? date('Y-m-d');
        $plantelId = $this->getPlantelId();

        $report = $this->reportService->getDailyReport($date, $plantelId);
        $filepath = $this->excelExporter->exportReport($report, "Reporte Diario - {$date}");

        return $this->success([
            'file' => basename($filepath),
            'download_url' => '/api/reports/download/' . basename($filepath)
        ], 'Reporte exportado exitosamente');
    }

    public function exportWeekly(array $query): array
    {
        $startDate = $query['start_date'] ?? date('Y-m-d');
        $plantelId = $this->getPlantelId();

        $report = $this->reportService->getWeeklyReport($startDate, $plantelId);
        $filepath = $this->excelExporter->exportReport($report, "Reporte Semanal");

        return $this->success([
            'file' => basename($filepath),
            'download_url' => '/api/reports/download/' . basename($filepath)
        ], 'Reporte exportado exitosamente');
    }

    public function exportMonthly(array $query): array
    {
        $year = (int) ($query['year'] ?? date('Y'));
        $month = (int) ($query['month'] ?? date('m'));
        $plantelId = $this->getPlantelId();

        $report = $this->reportService->getMonthlyReport($year, $month, $plantelId);
        $monthName = date('F', mktime(0, 0, 0, $month, 1));
        $filepath = $this->excelExporter->exportReport($report, "Reporte Mensual - {$monthName} {$year}");

        return $this->success([
            'file' => basename($filepath),
            'download_url' => '/api/reports/download/' . basename($filepath)
        ], 'Reporte exportado exitosamente');
    }

    public function exportCustom(array $query): array
    {
        $errors = $this->validate($query, [
            'start_date' => 'required|date',
            'end_date' => 'required|date'
        ]);

        if (!empty($errors)) {
            return $this->validationError($errors);
        }

        $plantelId = $this->getPlantelId();

        $report = $this->reportService->getCustomReport(
            $query['start_date'],
            $query['end_date'],
            $plantelId
        );

        $filepath = $this->excelExporter->exportReport(
            $report, 
            "Reporte {$query['start_date']} - {$query['end_date']}"
        );

        return $this->success([
            'file' => basename($filepath),
            'download_url' => '/api/reports/download/' . basename($filepath)
        ], 'Reporte exportado exitosamente');
    }

    public function download(string $filename): void
    {
        $filepath = $this->excelExporter->getExportPath() . DIRECTORY_SEPARATOR . $filename;

        if (!file_exists($filepath)) {
            http_response_code(404);
            echo json_encode(['error' => 'Archivo no encontrado']);
            return;
        }

        header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        header('Content-Length: ' . filesize($filepath));
        header('Cache-Control: no-cache, no-store, must-revalidate');
        
        readfile($filepath);
        
        // Eliminar archivo después de descarga
        unlink($filepath);
        exit;
    }
}
