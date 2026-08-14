<?php

namespace App\Services\Poultry;

use App\Models\Poultry\Batch;
use Illuminate\Support\Facades\Response;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class ExportService
{
    public static function exportBatchToExcel(Batch $batch)
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();

        // Summary sheet
        $sheet->setCellValue('A1', 'Batch ID');
        $sheet->setCellValue('B1', $batch->batch_id);
        // ... fill other data

        // Create writer and output
        $writer = new Xlsx($spreadsheet);
        ob_start();
        $writer->save('php://output');
        $content = ob_get_clean();

        return Response::make($content, 200, [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            'Content-Disposition' => "attachment; filename=batch_{$batch->batch_id}.xlsx",
        ]);
    }
}