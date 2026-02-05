<?php

declare(strict_types=1);

namespace App\Services;

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;

class ExcelExporter
{
    private string $exportPath;

    public function __construct()
    {
        $this->exportPath = $_ENV['EXPORT_PATH'] ?? __DIR__ . '/../../storage/exports';
        
        if (!is_dir($this->exportPath)) {
            mkdir($this->exportPath, 0755, true);
        }
    }

    public function exportReport(array $reportData, string $title = 'Reporte'): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Resumen');

        // Título del reporte
        $sheet->setCellValue('A1', $title);
        $sheet->mergeCells('A1:F1');
        $this->applyTitleStyle($sheet, 'A1:F1');

        // Período
        $sheet->setCellValue('A2', 'Período: ' . $reportData['period']['start_date'] . ' - ' . $reportData['period']['end_date']);
        $sheet->mergeCells('A2:F2');

        // Resumen
        $row = 4;
        $sheet->setCellValue('A' . $row, 'RESUMEN FINANCIERO');
        $sheet->mergeCells('A' . $row . ':B' . $row);
        $this->applyHeaderStyle($sheet, 'A' . $row . ':B' . $row);
        
        $row++;
        $sheet->setCellValue('A' . $row, 'Total Ingresos');
        $sheet->setCellValue('B' . $row, $reportData['summary']['total_income']);
        $this->applyCurrencyFormat($sheet, 'B' . $row);

        $row++;
        $sheet->setCellValue('A' . $row, 'Total Gastos');
        $sheet->setCellValue('B' . $row, $reportData['summary']['total_expenses']);
        $this->applyCurrencyFormat($sheet, 'B' . $row);

        $row++;
        $sheet->setCellValue('A' . $row, 'Balance');
        $sheet->setCellValue('B' . $row, $reportData['summary']['balance']);
        $this->applyCurrencyFormat($sheet, 'B' . $row);
        if ($reportData['summary']['balance'] >= 0) {
            $sheet->getStyle('B' . $row)->getFont()->getColor()->setRGB('10B981');
        } else {
            $sheet->getStyle('B' . $row)->getFont()->getColor()->setRGB('EF4444');
        }

        // Ingresos por método de pago
        $row += 2;
        $sheet->setCellValue('A' . $row, 'INGRESOS POR MÉTODO DE PAGO');
        $sheet->mergeCells('A' . $row . ':C' . $row);
        $this->applyHeaderStyle($sheet, 'A' . $row . ':C' . $row);

        $row++;
        $sheet->setCellValue('A' . $row, 'Método');
        $sheet->setCellValue('B' . $row, 'Cantidad');
        $sheet->setCellValue('C' . $row, 'Total');
        $this->applySubHeaderStyle($sheet, 'A' . $row . ':C' . $row);

        foreach ($reportData['income']['by_payment_method'] as $method) {
            $row++;
            $sheet->setCellValue('A' . $row, ucfirst($method['payment_method']));
            $sheet->setCellValue('B' . $row, $method['count']);
            $sheet->setCellValue('C' . $row, $method['total']);
            $this->applyCurrencyFormat($sheet, 'C' . $row);
        }

        // Gastos por categoría
        $row += 2;
        $sheet->setCellValue('A' . $row, 'GASTOS POR CATEGORÍA');
        $sheet->mergeCells('A' . $row . ':C' . $row);
        $this->applyHeaderStyle($sheet, 'A' . $row . ':C' . $row);

        $row++;
        $sheet->setCellValue('A' . $row, 'Categoría');
        $sheet->setCellValue('B' . $row, 'Cantidad');
        $sheet->setCellValue('C' . $row, 'Total');
        $this->applySubHeaderStyle($sheet, 'A' . $row . ':C' . $row);

        foreach ($reportData['expenses']['by_category'] as $category) {
            $row++;
            $sheet->setCellValue('A' . $row, $category['name']);
            $sheet->setCellValue('B' . $row, $category['count']);
            $sheet->setCellValue('C' . $row, $category['total']);
            $this->applyCurrencyFormat($sheet, 'C' . $row);
        }

        // Hoja de Ingresos detallados
        $this->addIncomeSheet($spreadsheet, $reportData['income']['transactions']);

        // Hoja de Gastos detallados
        $this->addExpenseSheet($spreadsheet, $reportData['expenses']['transactions']);

        // Auto-ajustar columnas
        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Guardar archivo
        $filename = sprintf('reporte_%s_%s.xlsx', 
            $reportData['report_type'],
            date('Y-m-d_His')
        );
        $filepath = $this->exportPath . DIRECTORY_SEPARATOR . $filename;

        $writer = new Xlsx($spreadsheet);
        $writer->save($filepath);

        return $filepath;
    }

    private function addIncomeSheet(Spreadsheet $spreadsheet, array $payments): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Ingresos');

        // Encabezados
        $headers = ['Fecha', 'Referencia', 'Estudiante', 'Concepto', 'Método', 'Monto', 'Registrado por'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }
        $this->applySubHeaderStyle($sheet, 'A1:G1');

        // Datos
        $row = 2;
        foreach ($payments as $payment) {
            $sheet->setCellValue('A' . $row, $payment['payment_date'] ?? '');
            $sheet->setCellValue('B' . $row, $payment['reference_number'] ?? '');
            $sheet->setCellValue('C' . $row, ($payment['first_name'] ?? '') . ' ' . ($payment['last_name'] ?? ''));
            $sheet->setCellValue('D' . $row, $payment['concept_type'] ?? '');
            $sheet->setCellValue('E' . $row, $payment['payment_method'] ?? '');
            $sheet->setCellValue('F' . $row, $payment['total'] ?? 0);
            $sheet->setCellValue('G' . $row, $payment['registered_by'] ?? '');
            $this->applyCurrencyFormat($sheet, 'F' . $row);
            $row++;
        }

        // Auto-ajustar columnas
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    private function addExpenseSheet(Spreadsheet $spreadsheet, array $expenses): void
    {
        $sheet = $spreadsheet->createSheet();
        $sheet->setTitle('Gastos');

        // Encabezados
        $headers = ['Fecha', 'Categoría', 'Descripción', 'Proveedor', 'Recibo', 'Monto', 'Registrado por'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }
        $this->applySubHeaderStyle($sheet, 'A1:G1');

        // Datos
        $row = 2;
        foreach ($expenses as $expense) {
            $sheet->setCellValue('A' . $row, $expense['expense_date'] ?? '');
            $sheet->setCellValue('B' . $row, $expense['category_name'] ?? '');
            $sheet->setCellValue('C' . $row, $expense['description'] ?? '');
            $sheet->setCellValue('D' . $row, $expense['vendor'] ?? '');
            $sheet->setCellValue('E' . $row, $expense['receipt_number'] ?? '');
            $sheet->setCellValue('F' . $row, $expense['amount'] ?? 0);
            $sheet->setCellValue('G' . $row, $expense['registered_by'] ?? '');
            $this->applyCurrencyFormat($sheet, 'F' . $row);
            $row++;
        }

        // Auto-ajustar columnas
        foreach (range('A', 'G') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
    }

    private function applyTitleStyle($sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 16,
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_CENTER,
            ],
        ]);
    }

    private function applyHeaderStyle($sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => [
                'bold' => true,
                'size' => 12,
                'color' => ['rgb' => 'FFFFFF'],
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => '3B82F6'],
            ],
            'alignment' => [
                'horizontal' => Alignment::HORIZONTAL_LEFT,
            ],
        ]);
    }

    private function applySubHeaderStyle($sheet, string $range): void
    {
        $sheet->getStyle($range)->applyFromArray([
            'font' => [
                'bold' => true,
            ],
            'fill' => [
                'fillType' => Fill::FILL_SOLID,
                'startColor' => ['rgb' => 'E5E7EB'],
            ],
            'borders' => [
                'allBorders' => [
                    'borderStyle' => Border::BORDER_THIN,
                ],
            ],
        ]);
    }

    private function applyCurrencyFormat($sheet, string $cell): void
    {
        $sheet->getStyle($cell)->getNumberFormat()->setFormatCode('$#,##0.00');
    }

    public function exportStudents(array $students): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Estudiantes');

        // Encabezados
        $headers = ['ID', 'Matrícula', 'Nombre', 'Apellido', 'Email', 'Teléfono', 'CURP', 'Estado', 'Plantel'];
        $col = 'A';
        foreach ($headers as $header) {
            $sheet->setCellValue($col . '1', $header);
            $col++;
        }
        $this->applySubHeaderStyle($sheet, 'A1:I1');

        // Datos
        $row = 2;
        foreach ($students as $student) {
            $data = is_array($student) ? $student : $student->toArray();
            $sheet->setCellValue('A' . $row, $data['id'] ?? '');
            $sheet->setCellValue('B' . $row, $data['student_id'] ?? '');
            $sheet->setCellValue('C' . $row, $data['first_name'] ?? '');
            $sheet->setCellValue('D' . $row, $data['last_name'] ?? '');
            $sheet->setCellValue('E' . $row, $data['email'] ?? '');
            $sheet->setCellValue('F' . $row, $data['phone'] ?? '');
            $sheet->setCellValue('G' . $row, $data['curp'] ?? '');
            $sheet->setCellValue('H' . $row, $data['status'] ?? '');
            $sheet->setCellValue('I' . $row, $data['plantel_name'] ?? '');
            $row++;
        }

        // Auto-ajustar columnas
        foreach (range('A', 'I') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }

        // Guardar archivo
        $filename = sprintf('estudiantes_%s.xlsx', date('Y-m-d_His'));
        $filepath = $this->exportPath . DIRECTORY_SEPARATOR . $filename;

        $writer = new Xlsx($spreadsheet);
        $writer->save($filepath);

        return $filepath;
    }

    public function getExportPath(): string
    {
        return $this->exportPath;
    }
}
