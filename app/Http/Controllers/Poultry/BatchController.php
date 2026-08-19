<?php

namespace App\Http\Controllers\Poultry;

use App\Http\Controllers\Controller;
use App\Http\Requests\Poultry\BatchRequest;
use App\Models\Poultry\Batch;
use App\Models\Poultry\Pen;
use App\Services\Poultry\BatchCalculationService;
use App\Services\Poultry\BatchTriggerService;
use App\Services\Poultry\ExportService;
use App\Services\SystemService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BatchController extends Controller
{
    /**
     * Display a listing of batches.
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Batch::class);

        $query = Batch::with('pen', 'createdBy')
            ->where('sector_id', sector_id('poultry'))
            ->orderBy('created_at', 'desc');

        // If show_closed is true, show only closed/completed; else show only active
        if ($request->boolean('show_closed')) {
            $query->whereIn('status', ['closed', 'completed']);
        } else {
            $query->where('status', 'active');
        }

        // Apply status filter (if provided, overrides the above)
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Apply search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('batch_id', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $batches = $query->paginate(20);

        // Calculate summary statistics (only for active batches)
        $activeBatches = Batch::where('status', 'active')->where('sector_id', sector_id('poultry'));
        $totalStarting = $activeBatches->sum('starting_flock');
        $totalRemaining = $activeBatches->sum('remaining_flock');

        return view('sectors.poultry.batches.index', compact(
            'batches',
            'totalStarting',
            'totalRemaining'
        ));
    }

    /**
     * Show the form for creating a new batch.
     */
    public function create()
    {
        Gate::authorize('create', Batch::class);

        $availablePens = Pen::available()->get();

        return view('sectors.poultry.batches.create', compact('availablePens'));
    }

    /**
     * Store a newly created batch in storage.
     */
    public function store(BatchRequest $request)
    {
        Gate::authorize('create', Batch::class);

        $validated = $request->validated();

        // Set remaining flock equal to starting flock
        $validated['remaining_flock'] = $validated['starting_flock'];

        // Auto-generate batch_id if not provided
        if (empty($validated['batch_id'])) {
            // We'll generate after save
        }

        $batch = DB::transaction(function () use ($validated, $request) {
            $batch = Batch::create([
                'batch_id' => $validated['batch_id'] ?? null, // will be generated later
                'name' => $validated['name'],
                'hatchery' => $validated['hatchery'] ?? null,
                'start_date' => $validated['start_date'],
                'starting_flock' => $validated['starting_flock'],
                'remaining_flock' => $validated['starting_flock'],
                'phase' => $validated['phase'],
                'initial_chicken_cost' => $validated['initial_chicken_cost'] ?? 0,
                'sector_id' => sector_id('poultry'),
                'created_by_id' => auth()->id(),
                'status' => 'active',
            ]);

            // Auto-generate batch_id if not provided
            if (empty($batch->batch_id)) {
                $batch->batch_id = 'B' . str_pad($batch->id, 4, '0', STR_PAD_LEFT);
                $batch->save();
            }

            // Assign pen if phase is batch and pen exists
            if ($batch->phase === 'batch') {
                $pen = Pen::available()->first();
                if ($pen) {
                    $pen->occupy($batch);
                    $batch->pen_id = $pen->id;
                    $batch->save();
                } else {
                    // Warn but allow creation
                    session()->flash('warning', 'No available pen for batch phase. Batch created without pen assignment.');
                }
            }

            // Update calculated selling price (based on profit margin)
            $batch->updateCachedMetrics();

            return $batch;
        });

        return redirect()->route('poultry.batches.show', $batch)
            ->with('success', "Batch {$batch->batch_id} created successfully.");
    }

    /**
     * Display the specified batch.
     */
    public function show(Batch $batch)
    {
        Gate::authorize('view', $batch);

        // Auto-refresh metrics
        $batch->updateCachedMetrics();

        // Get financial metrics
        $financialMetrics = $batch->getFinancialMetrics();

        // Get slaughter triggers
        $slaughterTriggers = $batch->checkSlaughterTriggers();

        // Get recent records for tabs
        $recentWeight = $batch->weightRecords()->with('recordedBy')->latest('date')->limit(10)->get();
        $recentFeed = $batch->feedRecords()->with('recordedBy', 'inventoryItem')->latest('date')->limit(10)->get();
        $recentExpenses = $batch->expenses()->with('recordedBy')->latest('date')->limit(10)->get();
        $recentFlock = $batch->flockRecords()->with('recordedBy')->latest('date')->limit(10)->get();

        // Chart data for the batch (last 30 days)
        $chartData = \App\Services\Poultry\BatchAnalyticsService::getBatchChartData($batch, 30);

        return view('sectors.poultry.batches.show', compact(
            'batch',
            'financialMetrics',
            'slaughterTriggers',
            'recentWeight',
            'recentFeed',
            'recentExpenses',
            'recentFlock',
            'chartData'
        ));
    }

    /**
     * Show the form for editing the specified batch.
     */
    public function edit(Batch $batch)
    {
        Gate::authorize('update', $batch);

        $availablePens = Pen::available()->get();

        return view('sectors.poultry.batches.edit', compact('batch', 'availablePens'));
    }

    /**
     * Update the specified batch in storage.
     */
    public function update(BatchRequest $request, Batch $batch)
    {
        Gate::authorize('update', $batch);

        $validated = $request->validated();

        // Prevent changing batch_id or starting_flock once set? We'll allow but warn.
        // We'll update only allowed fields
        $batch->fill($validated);

        // If phase changes to batch and no pen assigned, assign automatically
        if ($batch->phase === 'batch' && is_null($batch->pen_id)) {
            $pen = Pen::available()->first();
            if ($pen) {
                $pen->occupy($batch);
                $batch->pen_id = $pen->id;
            } else {
                session()->flash('warning', 'No available pen for batch phase. Batch remains without pen assignment.');
            }
        }

        $batch->save();
        $batch->updateCachedMetrics();

        return redirect()->route('poultry.batches.show', $batch)
            ->with('success', 'Batch updated successfully.');
    }

    /**
     * Remove the specified batch from storage.
     */
    public function destroy(Batch $batch)
    {
        Gate::authorize('delete', $batch);

        // Release pen if occupied
        if ($batch->pen) {
            $batch->pen->vacate();
        }

        $batchId = $batch->batch_id;
        $batch->delete();

        return redirect()->route('poultry.batches.index')
            ->with('success', "Batch {$batchId} deleted.");
    }

    /**
     * Export batch data to Excel.
     */
    public function export(Batch $batch)
    {
        Gate::authorize('export', $batch);

        try {
            return ExportService::exportBatchToExcel($batch);
        } catch (\Exception $e) {
            Log::error('Batch export failed: ' . $e->getMessage());
            return redirect()->back()->with('error', 'Export failed: ' . $e->getMessage());
        }
    }

    /**
     * Toggle manual mode for the batch.
     */
    public function toggleManualMode(Request $request, Batch $batch)
    {
        Gate::authorize('update', $batch);

        if ($batch->is_manual_mode) {
            // Switch to auto mode
            $batch->is_manual_mode = false;
            $batch->manual_mode_reason = null;
            $batch->manual_mode_enabled_by_id = null;
            $batch->manual_mode_enabled_at = null;
            $batch->save();

            // Create notification
            \App\Models\Notification::createGlobal(
                'system',
                "Batch {$batch->batch_id} switched to Auto Mode",
                "Manual mode disabled for batch {$batch->batch_id} by " . auth()->user()->name,
                auth()->user(),
                $batch
            );

            return redirect()->back()->with('success', 'Batch switched to automatic mode.');
        } else {
            // Switch to manual mode
            $request->validate([
                'reason' => 'required|string|max:500',
            ]);

            $batch->is_manual_mode = true;
            $batch->manual_mode_reason = $request->reason;
            $batch->manual_mode_enabled_by_id = auth()->id();
            $batch->manual_mode_enabled_at = now();
            $batch->save();

            // Create notification
            \App\Models\Notification::createGlobal(
                'manual_mode',
                "Batch {$batch->batch_id} switched to Manual Mode",
                "Manual mode enabled for batch {$batch->batch_id} by " . auth()->user()->name . ". Reason: {$request->reason}",
                auth()->user(),
                $batch
            );

            return redirect()->back()->with('warning', 'Batch switched to manual mode.');
        }
    }
}