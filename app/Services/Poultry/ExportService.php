<?php

namespace App\Services\Poultry;

use App\Models\Poultry\Batch;
use Illuminate\Support\Facades\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Csv;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExportService
{
    protected static function ensureSpreadsheetAvailable(): void
    {
        if (! class_exists(Spreadsheet::class)) {
            throw new \RuntimeException('Spreadsheet export library is not installed. Run: composer require phpoffice/phpspreadsheet');
        }
    }

    /**
     * Normalize a caller-supplied format string into ['writerClass', 'extension', 'contentType'].
     * Only 'excel' (xlsx) and 'csv' are supported.
     */
    protected static function resolveFormat(?string $format): array
    {
        return match ($format) {
            'csv' => [
                'writer'      => Csv::class,
                'extension'   => 'csv',
                'contentType' => 'text/csv; charset=utf-8',
            ],
            default => [
                'writer'      => Xlsx::class,
                'extension'   => 'xlsx',
                'contentType' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ],
        };
    }

    public static function exportBatchToExcel(Batch $batch, ?string $reportTemplate = 'farm-overview', ?string $format = 'excel')
    {
        self::ensureSpreadsheetAvailable();

        $spreadsheet = new Spreadsheet();
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

        $filenameBase = "batch_{$batch->batch_id}_{$reportTemplate}";

        return self::streamSpreadsheet($spreadsheet, $filenameBase, $format);
    }

    public static function exportDatabaseTemplate(?string $reportTemplate = 'farm-overview', ?int $batchId = null, ?string $format = 'excel')
    {
        self::ensureSpreadsheetAvailable();

        $spreadsheet = new Spreadsheet();
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

        $filenameBase = "database_{$reportTemplate}";

        return self::streamSpreadsheet($spreadsheet, $filenameBase, $format);
    }

    public static function exportAnalyticsReport(?string $reportTemplate = 'performance', ?int $batchId = null, ?string $format = 'excel')
    {
        return self::exportDatabaseTemplate($reportTemplate, $batchId, $format);
    }

    public static function exportFinancialReport(?string $reportTemplate = 'financial-summary', ?int $batchId = null, ?string $format = 'excel')
    {
        return self::exportDatabaseTemplate($reportTemplate, $batchId, $format);
    }

    /**
     * Write the spreadsheet to a stream and return a download response.
     * The filename is built from $filenameBase + the correct extension.
     */
    protected static function streamSpreadsheet(Spreadsheet $spreadsheet, string $filenameBase, ?string $format = 'excel')
    {
        $resolved = self::resolveFormat($format);
        $writerClass = $resolved['writer'];

        /** @var \PhpOffice\PhpSpreadsheet\Writer\IWriter $writer */
        $writer = new $writerClass($spreadsheet);

        $filename = $filenameBase . '.' . $resolved['extension'];

        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean();

        // Free memory held by the spreadsheet object.
        $spreadsheet->disconnectWorksheets();
        unset($spreadsheet);

        return Response::make($content, 200, [
            'Content-Type'        => $resolved['contentType'],
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
            'Content-Length'      => strlen($content),
            'Cache-Control'       => 'no-store, no-cache, must-revalidate',
            'Pragma'              => 'no-cache',
        ]);
    }
}