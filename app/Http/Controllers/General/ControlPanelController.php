<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Models\Poultry\Batch;
use App\Models\Poultry\FlockRecord;
use App\Models\Poultry\WeightRecord;
use App\Models\Poultry\FeedRecord;
use App\Models\Poultry\Expense;
use App\Models\Poultry\InventoryConsumption;
use App\Models\Poultry\InventoryItem;
use App\Services\Poultry\BatchRecalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;

class ControlPanelController extends Controller
{
    private const EDITABLE_FIELDS = [
        'poultry_batches' => [
            'name', 'hatchery', 'start_date', 'starting_flock',
            'initial_chicken_cost', 'status', 'phase', 'pen_id',
        ],
        'flock_records' => [
            'date', 'mortality', 'culls', 'slaughter',
            'slaughter_avg_weight', 'notes',
        ],
        'weight_records' => [
            'date', 'individual_weights', 'notes',
        ],
        'feed_records' => [
            'date', 'feed_used', 'inventory_item_id',
        ],
        'expenses' => [
            'date', 'category', 'description', 'amount',
            'receipt_number', 'vendor',
        ],
    ];

    private const LOCKED_FIELDS = [
        'poultry_batches' => [
            'current_count', 'current_weight_kg', 'current_cost',
            'current_average_weight', 'current_average_cost',
            'total_mortality', 'historical_mortality', 'pen_mortality',
            'mortality_rate', 'total_culls', 'total_slaughter',
            'total_feed_used', 'total_expenses', 'total_weight_gain',
            'current_ifcr', 'current_cfcr', 'current_marginal_profit_percent',
            'remaining_flock', 'cost_allocated_so_far', 'peak_profit',
        ],
        'weight_records' => [
            'average_weight', 'total_weight', 'birds_weighed',
            'coefficient_variation', 'cv_status', 'expected_weight',
        ],
        'feed_records' => [
            'feed_cost_per_kg', 'total_feed_cost', 'feed_per_bird',
        ],
        'flock_records' => [],
        'expenses'      => [],
    ];

    public function index(Request $request)
    {
        Gate::authorize('admin');

        $allBatches = Batch::orderBy('created_at', 'desc')->get();

        $selectedBatch = null;
        if ($request->filled('batch')) {
            $selectedBatch = Batch::with([
                'flockRecords',
                'weightRecords',
                'feedRecords',
                'expenses',
            ])->find($request->batch);
        }

        return view('general.control-panel.index', compact('allBatches', 'selectedBatch'));
    }

    public function getRecord(Request $request)
    {
        if (!Gate::allows('admin')) {
            return response()->json(['error' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'table' => 'required|in:poultry_batches,flock_records,weight_records,feed_records,expenses',
            'id'    => 'required|integer',
        ]);

        $modelMap = $this->getModelMap();
        $table = $validated['table'];

        $record = $modelMap[$table]::find($validated['id']);
        if (!$record) {
            return response()->json(['error' => 'Record not found.'], 404);
        }

        return response()->json([
            'record'         => $record->toArray(),
            'primaryKey'     => $record->getKeyName(),
            'editableFields' => self::EDITABLE_FIELDS[$table] ?? [],
            'lockedFields'   => self::LOCKED_FIELDS[$table] ?? [],
        ]);
    }

    public function updateRecord(Request $request)
    {
        if (!Gate::allows('admin')) {
            return response()->json(['error' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'table'    => 'required|in:poultry_batches,flock_records,weight_records,feed_records,expenses',
            'id'       => 'required|integer',
            'data'     => 'required|array',
            'batch_id' => 'nullable|integer',
        ]);

        $modelMap = $this->getModelMap();
        $table = $validated['table'];
        $data  = $validated['data'];

        $record = $modelMap[$table]::find($validated['id']);
        if (!$record) {
            return response()->json(['error' => 'Record not found.'], 404);
        }

        $editable = self::EDITABLE_FIELDS[$table] ?? [];
        $filtered = [];
        foreach ($editable as $field) {
            if (array_key_exists($field, $data)) {
                $filtered[$field] = $data[$field];
            }
        }

        if (empty($filtered)) {
            return response()->json(['error' => 'No editable fields submitted.'], 422);
        }

        try {
            DB::transaction(function () use ($record, $filtered, $table) {
                $batchToRecalc = ($table === 'poultry_batches') ? $record : $record->batch;

                switch ($table) {
                    case 'feed_records':
                        $this->updateFeedRecord($record, $filtered);
                        break;
                    case 'weight_records':
                        $this->updateWeightRecord($record, $filtered);
                        break;
                    default:
                        $record->update($filtered);
                        break;
                }

                if ($batchToRecalc && $batchToRecalc->exists) {
                    BatchRecalculationService::recalculateAll($batchToRecalc);
                }
            });

            return response()->json([
                'success'  => true,
                'message'  => 'Record updated and all metrics recalculated.',
                'batch_id' => $validated['batch_id'],
            ]);
        } catch (\Exception $e) {
            Log::error('Control Panel update failed: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function deleteRecord(Request $request)
    {
        if (!Gate::allows('admin')) {
            return response()->json(['error' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'table'    => 'required|in:poultry_batches,flock_records,weight_records,feed_records,expenses',
            'id'       => 'required|integer',
            'batch_id' => 'nullable|integer',
        ]);

        $modelMap = $this->getModelMap();
        $table = $validated['table'];
        $id    = $validated['id'];

        $record = $modelMap[$table]::find($id);
        if (!$record) {
            return response()->json(['error' => 'Record not found.'], 404);
        }

        $batchToRecalc = ($table === 'poultry_batches') ? $record : $record->batch;

        try {
            DB::transaction(function () use ($record, $batchToRecalc) {
                // Deleting the record fires the appropriate observer.
                // Feed records → FeedRecordObserver::deleted deletes the
                // linked InventoryConsumption → InventoryConsumptionObserver
                // restores stock exactly once.
                $record->delete();

                if ($batchToRecalc && $batchToRecalc->exists) {
                    BatchRecalculationService::recalculateAll($batchToRecalc);
                }
            });

            return response()->json([
                'success'  => true,
                'message'  => 'Record deleted and metrics recalculated.',
                'batch_id' => $validated['batch_id'],
            ]);
        } catch (\Exception $e) {
            Log::error('Control Panel delete failed: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update a feed record and let the observer chain rebuild the linked
     * consumption row. Do not touch inventory stock here.
     */
    private function updateFeedRecord(FeedRecord $record, array $data): void
    {
        foreach (['date', 'feed_used', 'inventory_item_id'] as $f) {
            if (array_key_exists($f, $data)) {
                $record->$f = $data[$f];
            }
        }

        $newItem = InventoryItem::find($record->inventory_item_id);
        if ($newItem) {
            $record->feed_cost_per_kg = $newItem->cost_per_unit;
            $record->total_feed_cost  = (float) $record->feed_used * (float) $newItem->cost_per_unit;
        }
        $record->feed_per_bird = 0;

        // Saving fires FeedRecordObserver::updated, which rebuilds the
        // consumption row. The InventoryConsumptionObserver adjusts stock
        // exactly once on the delta.
        $record->save();
    }

    private function updateWeightRecord(WeightRecord $record, array $data): void
    {
        foreach (['date', 'individual_weights', 'notes'] as $f) {
            if (array_key_exists($f, $data)) {
                $value = $data[$f];

                if ($f === 'individual_weights') {
                    if (is_string($value)) {
                        $decoded = json_decode($value, true);
                        if (is_array($decoded)) {
                            $value = array_values(array_map('floatval', $decoded));
                        } else {
                            $value = array_values(array_filter(
                                array_map('floatval', preg_split('/[\s,]+/', $value)),
                                fn ($v) => $v > 0
                            ));
                        }
                    }
                    if (is_array($value)) {
                        $value = array_values(array_filter($value, fn ($v) => is_numeric($v) && $v > 0));
                    }
                }

                $record->$f = $value;
            }
        }

        $record->calculateMetrics();
        $record->save();
    }

    private function getModelMap(): array
    {
        return [
            'poultry_batches' => Batch::class,
            'flock_records'   => FlockRecord::class,
            'weight_records'  => WeightRecord::class,
            'feed_records'    => FeedRecord::class,
            'expenses'        => Expense::class,
        ];
    }
}