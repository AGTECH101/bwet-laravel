<?php

namespace App\Http\Controllers\Poultry;

use App\Http\Controllers\Controller;
use App\Http\Requests\Poultry\BatchRequest;
use App\Models\BatchStateMigration;
use App\Models\Poultry\Batch;
use App\Models\Poultry\Pen;
use App\Services\Poultry\BatchRecalculationService;
use App\Services\Poultry\ExportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BatchController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Batch::class);

        $query = Batch::with('pen', 'createdBy')
            ->where('sector_id', sector_id('poultry'))
            ->orderBy('created_at', 'desc');

        if (!$request->boolean('show_closed')) {
            $query->where('status', 'active');
        } else {
            $query->whereIn('status', ['closed', 'completed']);
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('batch_id', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%");
            });
        }

        $batches = $query->paginate(20);

        $activeBatches = Batch::where('status', 'active')->where('sector_id', sector_id('poultry'));
        $totalStarting = $activeBatches->sum('starting_flock');
        $totalRemaining = $activeBatches->sum('current_count');

        return view('sectors.poultry.batches.index', compact('batches', 'totalStarting', 'totalRemaining'));
    }

    public function create()
    {
        Gate::authorize('create', Batch::class);
        $availablePens = Pen::available()->get();
        return view('sectors.poultry.batches.create', compact('availablePens'));
    }

    public function store(BatchRequest $request)
    {
        Gate::authorize('create', Batch::class);

        $validated = $request->validated();

        $batch = DB::transaction(function () use ($validated) {
            $batch = Batch::create([
                'batch_id' => $validated['batch_id'] ?? null,
                'name' => $validated['name'],
                'hatchery' => $validated['hatchery'] ?? null,
                'start_date' => $validated['start_date'],
                'starting_flock' => $validated['starting_flock'],
                'remaining_flock' => $validated['starting_flock'],
                'current_count' => $validated['starting_flock'],
                'phase' => $validated['phase'],
                'initial_chicken_cost' => $validated['initial_chicken_cost'] ?? 0,
                'current_cost' => $validated['initial_chicken_cost'] ?? 0,
                'sector_id' => sector_id('poultry'),
                'created_by_id' => auth()->id(),
                'status' => 'active',
            ]);

            if (empty($batch->batch_id)) {
                $batch->batch_id = 'B' . str_pad($batch->id, 4, '0', STR_PAD_LEFT);
                $batch->save();
            }

            if ($batch->phase === 'batch' && $batch->starting_flock > 0) {
                $pen = Pen::available()->first();
                if ($pen) {
                    if ($pen->capacity >= $batch->starting_flock) {
                        $pen->occupy($batch);
                        $batch->pen_id = $pen->id;
                        $batch->save();
                    } else {
                        session()->flash('warning', 'Pen capacity (' . $pen->capacity . ') is less than starting flock (' . $batch->starting_flock . ').');
                    }
                } else {
                    session()->flash('warning', 'No available pen for batch phase. Batch created without pen assignment.');
                }
            }

            BatchRecalculationService::recalculateAll($batch);

            return $batch;
        });

        return redirect()->route('poultry.batches.show', $batch)
            ->with('success', "Batch {$batch->batch_id} created successfully.");
    }

    public function show(Batch $batch)
    {
        Gate::authorize('view', $batch);

        // Ensure metrics are fresh
        BatchRecalculationService::recalculateAll($batch);
        $batch->refresh();

        $financialMetrics = $batch->getFinancialMetrics();
        $slaughterTriggers = $batch->checkSlaughterTriggers();

        $recentWeight = $batch->weightRecords()->with('recordedBy')->latest('date')->limit(10)->get();
        $recentFeed = $batch->feedRecords()->with('recordedBy', 'inventoryItem')->latest('date')->limit(10)->get();
        $recentExpenses = $batch->expenses()->with('recordedBy')->latest('date')->limit(10)->get();
        $recentFlock = $batch->flockRecords()->with('recordedBy')->latest('date')->limit(10)->get();

        $chartData = \App\Services\Poultry\BatchAnalyticsService::getBatchChartData($batch, 30);

        // ── Feed breakdown ──────────────────────────────────────────────
        // Splits total_feed_used into its three contributors so the batch
        // page can show the same kind of detail as mortality. Read-only —
        // no stored fields, no recalculation, no side effects.
        $feedBreakdown = $this->buildFeedBreakdown($batch);

        return view('sectors.poultry.batches.show', compact(
            'batch', 'financialMetrics', 'slaughterTriggers',
            'recentWeight', 'recentFeed', 'recentExpenses', 'recentFlock', 'chartData',
            'feedBreakdown'
        ));
    }

    public function edit(Batch $batch)
    {
        Gate::authorize('update', $batch);
        $availablePens = Pen::available()->get();
        return view('sectors.poultry.batches.edit', compact('batch', 'availablePens'));
    }

    public function update(BatchRequest $request, Batch $batch)
    {
        Gate::authorize('update', $batch);

        $validated = $request->validated();

        DB::transaction(function () use ($batch, $validated) {
            $batch->fill($validated);

            if ($batch->phase === 'batch' && is_null($batch->pen_id)) {
                $pen = Pen::available()->first();
                if ($pen && $pen->capacity >= $batch->starting_flock) {
                    $pen->occupy($batch);
                    $batch->pen_id = $pen->id;
                }
            }

            $batch->save();

            BatchRecalculationService::recalculateAll($batch);
        });

        return redirect()->route('poultry.batches.show', $batch)
            ->with('success', 'Batch updated and metrics recalculated.');
    }

    public function destroy(Batch $batch)
    {
        Gate::authorize('delete', $batch);

        if ($batch->pen) {
            $batch->pen->vacate();
        }

        $batchId = $batch->batch_id;
        $batch->delete();

        return redirect()->route('poultry.batches.index')
            ->with("success", "Batch {$batchId} deleted.");
    }

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

    public function toggleManualMode(Request $request, Batch $batch)
    {
        Gate::authorize('update', $batch);

        if ($batch->is_manual_mode) {
            $batch->is_manual_mode = false;
            $batch->manual_mode_reason = null;
            $batch->manual_mode_enabled_by_id = null;
            $batch->manual_mode_enabled_at = null;
            $batch->save();

            \App\Models\Notification::createGlobal(
                'system',
                "Batch {$batch->batch_id} switched to Auto Mode",
                "Manual mode disabled for batch {$batch->batch_id} by " . auth()->user()->name,
                auth()->user(),
                $batch
            );

            return redirect()->back()->with('success', 'Batch switched to automatic mode.');
        }

        $request->validate(['reason' => 'required|string|max:500']);

        $batch->is_manual_mode = true;
        $batch->manual_mode_reason = $request->reason;
        $batch->manual_mode_enabled_by_id = auth()->id();
        $batch->manual_mode_enabled_at = now();
        $batch->save();

        \App\Models\Notification::createGlobal(
            'manual_mode',
            "Batch {$batch->batch_id} switched to Manual Mode",
            "Manual mode enabled for batch {$batch->batch_id} by " . auth()->user()->name . ". Reason: {$request->reason}",
            auth()->user(),
            $batch
        );

        return redirect()->back()->with('warning', 'Batch switched to manual mode.');
    }

    /**
     * Break the batch's cumulative feed into its three contributors.
     *
     *   own_records     — SUM(feed_records.feed_used) for this batch
     *   transferred_in  — SUM(migration.feed_moved) where this batch is destination (positive)
     *   transferred_out — SUM(migration.feed_moved) where this batch is source (negative)
     *   total           — batch.total_feed_used (the transfer-adjusted figure)
     *
     * Own + In + Out should equal Total, except in the edge case where the
     * recalc service clamped the total at 0 (more feed transferred out than
     * the batch ever had). The display still reflects the true stored value.
     */
    private function buildFeedBreakdown(Batch $batch): array
    {
        $ownRecords = (float) $batch->feedRecords()->sum('feed_used');

        $transferredIn = (float) BatchStateMigration::where('destination_batch_id', $batch->id)
            ->where('migration_type', 'transfer_in')
            ->sum('feed_moved');

        $transferredOut = (float) BatchStateMigration::where('source_batch_id', $batch->id)
            ->where('migration_type', 'transfer_out')
            ->sum('feed_moved');

        return [
            'own_records'     => $ownRecords,
            'transferred_in'  => $transferredIn,
            'transferred_out' => $transferredOut,
            'total'           => (float) $batch->total_feed_used,
            'total_bags'      => round(((float) $batch->total_feed_used) / 25, 2),
        ];
    }
}