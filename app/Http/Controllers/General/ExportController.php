<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Models\Poultry\Batch;
use App\Services\Poultry\ExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ExportController extends Controller
{
    public function index()
    {
        Gate::authorize('export');
        $batches = Batch::where('status', 'active')->orderBy('created_at', 'desc')->get();

        return view('general.export.index', compact('batches'));
    }

    public function export(Request $request)
    {
        Gate::authorize('export');

        $request->validate([
            'export_type' => 'required|in:batch,database,analytics,financial',
            'report_template' => 'required|string',
            'format' => 'required|in:excel,csv,pdf',
            'batch_id' => 'nullable|exists:poultry_batches,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'quick' => 'nullable|string|in:all_batches,current_month,performance,financial,inventory',
        ]);

        $reportTemplate = $request->input('report_template', $request->input('quick') ?: 'farm-overview');

        if ($request->filled('quick')) {
            $reportTemplate = match ($request->quick) {
                'all_batches' => 'farm-overview',
                'current_month' => 'monthly-operations',
                'performance' => 'performance',
                'financial' => 'financial-summary',
                'inventory' => 'inventory-summary',
                default => $reportTemplate,
            };
        }

        if ($request->export_type === 'batch') {
            if (empty($request->batch_id)) {
                return redirect()->back()->withErrors(['batch_id' => 'Please choose a batch to export.']);
            }

            $batch = Batch::findOrFail($request->batch_id);

            return ExportService::exportBatchToExcel($batch, $reportTemplate, $request->format);
        }

        if ($request->export_type === 'database') {
            return ExportService::exportDatabaseTemplate($reportTemplate, $request->batch_id, $request->format);
        }

        if ($request->export_type === 'analytics') {
            return ExportService::exportAnalyticsReport($reportTemplate, $request->batch_id, $request->format);
        }

        if ($request->export_type === 'financial') {
            return ExportService::exportFinancialReport($reportTemplate, $request->batch_id, $request->format);
        }

        return redirect()->back()->with('info', 'Export feature for this type coming soon.');
    }
}