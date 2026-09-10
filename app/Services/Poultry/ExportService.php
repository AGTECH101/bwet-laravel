<?php

namespace App\Services\Poultry;

use App\Models\Poultry\Batch;
use Illuminate\Support\Facades\Response;

class ExportService
{
    protected static function ensureSpreadsheetAvailable(): void
    {
        if (! class_exists(\PhpOffice\PhpSpreadsheet\Spreadsheet::class)) {
            throw new \RuntimeException('Spreadsheet export library is not installed. Run: composer require phpoffice/phpspreadsheet');
        }
    }

    public static function exportBatchToExcel(Batch $batch, ?string $reportTemplate = 'farm-overview', ?string $format = 'excel')
    {
        self::ensureSpreadsheetAvailable();
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'Batch ID');
        $sheet->setCellValue('B1', $batch->batch_id);
        $sheet->setCellValue('A2', 'Batch Name');
        $sheet->setCellValue('B2', $batch->name ?? '');
        $sheet->setCellValue('A3', 'Report Template');
        $sheet->setCellValue('B3', $reportTemplate);
        $sheet->setCellValue('A4', 'Remaining Flock');
        $sheet->setCellValue('B4', $batch->remaining_flock ?? 0);
        $sheet->setCellValue('A5', 'Current Age (Days)');
        $sheet->setCellValue('B5', $batch->current_age_days ?? 0);
        $sheet->setCellValue('A6', 'Cost Per Bird');
        $sheet->setCellValue('B6', $batch->getCostPerBird());
        $sheet->setCellValue('A7', 'Selling Price Per Kg');
        $sheet->setCellValue('B7', $batch->getCalculatedSellingPricePerKg());

        return self::streamSpreadsheet($spreadsheet, "batch_{$batch->batch_id}_{$reportTemplate}.{$format}", $format);
    }

    public static function exportDatabaseTemplate(?string $reportTemplate = 'farm-overview', ?int $batchId = null, ?string $format = 'excel')
    {
        self::ensureSpreadsheetAvailable();
        $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        $sheet->setCellValue('A1', 'Report Template');
        $sheet->setCellValue('B1', $reportTemplate ?? 'farm-overview');
        $sheet->setCellValue('A2', 'Generated At');
        $sheet->setCellValue('B2', now()->toDateTimeString());

        $row = 5;
        $sheet->setCellValue('A' . $row, 'Batch ID');
        $sheet->setCellValue('B' . $row, 'Batch Name');
        $sheet->setCellValue('C' . $row, 'Remaining Flock');
        $sheet->setCellValue('D' . $row, 'Cost / Bird');
        $sheet->setCellValue('E' . $row, 'Selling Price / Kg');
        $row++;

        $query = Batch::query();
        if ($batchId) {
            $query->where('id', $batchId);
        }

        foreach ($query->orderBy('created_at', 'desc')->get() as $batch) {
            $sheet->setCellValue('A' . $row, $batch->batch_id);
            $sheet->setCellValue('B' . $row, $batch->name ?? '');
            $sheet->setCellValue('C' . $row, $batch->remaining_flock ?? 0);
            $sheet->setCellValue('D' . $row, $batch->getCostPerBird());
            $sheet->setCellValue('E' . $row, $batch->getCalculatedSellingPricePerKg());
            $row++;
        }

        return self::streamSpreadsheet($spreadsheet, "database_{$reportTemplate}.{$format}", $format);
    }

    public static function exportAnalyticsReport(?string $reportTemplate = 'performance', ?int $batchId = null, ?string $format = 'excel')
    {
        return self::exportDatabaseTemplate($reportTemplate, $batchId, $format);
    }

    public static function exportFinancialReport(?string $reportTemplate = 'financial-summary', ?int $batchId = null, ?string $format = 'excel')
    {
        return self::exportDatabaseTemplate($reportTemplate, $batchId, $format);
    }

    protected static function streamSpreadsheet(\PhpOffice\PhpSpreadsheet\Spreadsheet $spreadsheet, string $filename, ?string $format = 'excel')
    {
        $extension = $format === 'csv' ? 'csv' : 'xlsx';
        $writer = $format === 'csv' ? new \PhpOffice\PhpSpreadsheet\Writer\Csv($spreadsheet) : new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);

        $content = null;
        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean();

        return Response::make($content, 200, [
            'Content-Type' => $format === 'csv'
                ? 'text/csv; charset=utf-8'
                : 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => 'attachment; filename=' . $filename,
        ]);
    }
}