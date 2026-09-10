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
    /**
     * Fields that can be edited per table.
     * Fields NOT in this list are silently ignored on update.
     */
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

    /**
     * Fields that must be displayed but locked (read-only).
     * These are computed or derived fields.
     */
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
        'expenses' => [],
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
            'id' => 'required|integer',
        ]);

        $modelMap = $this->getModelMap();
        $table = $validated['table'];

        $record = $modelMap[$table]::find($validated['id']);
        if (!$record) {
            return response()->json(['error' => 'Record not found.'], 404);
        }

        return response()->json([
            'record' => $record->toArray(),
            'primaryKey' => $record->getKeyName(),
            'editableFields' => self::EDITABLE_FIELDS[$table] ?? [],
            'lockedFields' => self::LOCKED_FIELDS[$table] ?? [],
        ]);
    }

    public function updateRecord(Request $request)
    {
        if (!Gate::allows('admin')) {
            return response()->json(['error' => 'Unauthorized.'], 403);
        }

        $validated = $request->validate([
            'table' => 'required|in:poultry_batches,flock_records,weight_records,feed_records,expenses',
            'id' => 'required|integer',
            'data' => 'required|array',
            'batch_id' => 'nullable|integer',
        ]);

        $modelMap = $this->getModelMap();
        $table = $validated['table'];
        $data = $validated['data'];

        $record = $modelMap[$table]::find($validated['id']);
        if (!$record) {
            return response()->json(['error' => 'Record not found.'], 404);
        }

        // ── Whitelist enforcement ──
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
                'success' => true,
                'message' => 'Record updated and all metrics recalculated.',
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
            'table' => 'required|in:poultry_batches,flock_records,weight_records,feed_records,expenses',
            'id' => 'required|integer',
            'batch_id' => 'nullable|integer',
        ]);

        $modelMap = $this->getModelMap();
        $table = $validated['table'];
        $id = $validated['id'];

        $record = $modelMap[$table]::find($id);
        if (!$record) {
            return response()->json(['error' => 'Record not found.'], 404);
        }

        $batchToRecalc = ($table === 'poultry_batches') ? $record : $record->batch;

        try {
            DB::transaction(function () use ($record, $batchToRecalc, $table) {
                // Feed records: restore inventory + delete consumption entry
                if ($table === 'feed_records' && $record->inventory_item_id) {
                    $item = InventoryItem::find($record->inventory_item_id);
                    if ($item) {
                        $item->quantity_in_stock += $record->feed_used;
                        $item->quantity_used = max(0, $item->quantity_used - $record->feed_used);
                        $item->save();
                    }

                    InventoryConsumption::where('source_type', 'feed')
                        ->where('source_id', $record->id)
                        ->delete();
                }

                $record->delete();

                if ($batchToRecalc && $batchToRecalc->exists) {
                    BatchRecalculationService::recalculateAll($batchToRecalc);
                }
            });

            return response()->json([
                'success' => true,
                'message' => 'Record deleted and metrics recalculated.',
                'batch_id' => $validated['batch_id'],
            ]);
        } catch (\Exception $e) {
            Log::error('Control Panel delete failed: ' . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Update a feed record and recalculate all derived fields + inventory.
     */
    private function updateFeedRecord(FeedRecord $record, array $data): void
    {
        $oldFeedUsed = (float) $record->feed_used;
        $oldItemId = $record->inventory_item_id;

        // Apply editable fields manually (so we can track changes)
        foreach (['date', 'feed_used', 'inventory_item_id'] as $f) {
            if (array_key_exists($f, $data)) {
                $record->$f = $data[$f];
            }
        }

        // ── Recalculate derived cost fields ──
        $newItem = InventoryItem::find($record->inventory_item_id);
        if ($newItem) {
            $record->feed_cost_per_kg = $newItem->cost_per_unit;
            $record->total_feed_cost = (float) $record->feed_used * (float) $newItem->cost_per_unit;
        }
        $record->feed_per_bird = 0;
        $record->save();

        // ── Adjust inventory if quantity or item changed ──
        $quantityChanged = ((float) $oldFeedUsed !== (float) $record->feed_used);
        $itemChanged = ($oldItemId != $record->inventory_item_id);

        if ($quantityChanged || $itemChanged) {
            // 1. Restore stock to the old item
            if ($oldItemId) {
                $oldItem = InventoryItem::find($oldItemId);
                if ($oldItem) {
                    $oldItem->quantity_in_stock += $oldFeedUsed;
                    $oldItem->quantity_used = max(0, (float) $oldItem->quantity_used - $oldFeedUsed);
                    $oldItem->save();
                }
            }

            // 2. Deduct stock from the new item
            if ($newItem) {
                $newItem->quantity_in_stock = max(0, (float) $newItem->quantity_in_stock - (float) $record->feed_used);
                $newItem->quantity_used = (float) $newItem->quantity_used + (float) $record->feed_used;
                $newItem->save();
            }

            // 3. Update or create the consumption record
            $consumption = InventoryConsumption::where('source_type', 'feed')
                ->where('source_id', $record->id)
                ->first();

            if ($consumption) {
                $consumption->update([
                    'inventory_item_id' => $record->inventory_item_id,
                    'quantity_used' => $record->feed_used,
                    'unit_cost_at_time' => $newItem?->cost_per_unit ?? 0,
                    'total_cost' => $record->total_feed_cost,
                    'date' => $record->date,
                ]);
            } elseif ($newItem) {
                InventoryConsumption::create([
                    'inventory_item_id' => $newItem->id,
                    'poultry_batch_id' => $record->poultry_batch_id,
                    'quantity_used' => $record->feed_used,
                    'date' => $record->date,
                    'recorded_by_id' => auth()->id(),
                    'source_type' => 'feed',
                    'source_id' => $record->id,
                    'unit_cost_at_time' => $newItem->cost_per_unit,
                    'total_cost' => $record->total_feed_cost,
                ]);
            }
        }
    }

    /**
     * Update a weight record and recalculate its derived metrics.
     */
    private function updateWeightRecord(WeightRecord $record, array $data): void
    {
        foreach (['date', 'individual_weights', 'notes'] as $f) {
            if (array_key_exists($f, $data)) {
                $value = $data[$f];

                if ($f === 'individual_weights') {
                    // Handle JSON string (from textarea) or comma-separated
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
                    // Filter valid numbers
                    if (is_array($value)) {
                        $value = array_values(array_filter($value, fn ($v) => is_numeric($v) && $v > 0));
                    }
                }

                $record->$f = $value;
            }
        }

        // Recalculates average_weight, cv, cv_status, etc.
        $record->calculateMetrics();
        $record->save();
    }

    private function getModelMap(): array
    {
        return [
            'poultry_batches' => Batch::class,
            'flock_records' => FlockRecord::class,
            'weight_records' => WeightRecord::class,
            'feed_records' => FeedRecord::class,
            'expenses' => Expense::class,
        ];
    }
}