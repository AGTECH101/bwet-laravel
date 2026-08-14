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
        $batches = Batch::where('status', 'active')->get();
        return view('general.export.index', compact('batches'));
    }

    public function export(Request $request)
    {
        Gate::authorize('export');

        $request->validate([
            'export_type' => 'required|in:batch,database,analytics,financial',
            'format' => 'required|in:excel,csv,pdf',
            'batch_id' => 'required_if:export_type,batch|exists:poultry_batches,id',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
        ]);

        if ($request->export_type === 'batch') {
            $batch = Batch::findOrFail($request->batch_id);
            return ExportService::exportBatchToExcel($batch);
        }

        // Other export types...
        return redirect()->back()->with('info', 'Export feature for this type coming soon.');
    }
}