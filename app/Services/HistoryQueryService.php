<?php

namespace App\Services;

use App\Models\Poultry\Batch;
use App\Models\User;
use App\Models\Expense;
use App\Models\Poultry\FeedRecord;
use App\Models\Poultry\WeightRecord;
use App\Models\Poultry\FlockRecord;
use App\Models\ObservationReport;
use App\Models\Poultry\InventoryItem;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class HistoryQueryService
{
    public static function execute(array $filters): array
    {
        $start = microtime(true);

        $queryType = $filters['query_type'];
        $results = [];

        switch ($queryType) {
            case 'expenses':
                $results = self::queryExpenses($filters);
                break;
            case 'feed':
                $results = self::queryFeed($filters);
                break;
            case 'weight':
                $results = self::queryWeights($filters);
                break;
            case 'flock':
                $results = self::queryFlock($filters);
                break;
            case 'observations':
                $results = self::queryObservations($filters);
                break;
            case 'inventory':
                $results = self::queryInventory($filters);
                break;
            case 'all':
                $results = self::queryAll($filters);
                break;
            default:
                $results = [];
        }

        $executionTime = (microtime(true) - $start) * 1000;

        return [
            'results' => $results,
            'count' => count($results),
            'execution_time_ms' => (int) $executionTime,
            'summary' => self::generateSummary($results),
        ];
    }

    private static function queryExpenses(array $filters): array
    {
        $query = \App\Models\Poultry\Expense::with('batch', 'recordedBy');

        if (!empty($filters['date_from'])) {
            $query->whereDate('date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('date', '<=', $filters['date_to']);
        }
        if (!empty($filters['user_filter'])) {
            $query->where('recorded_by_id', $filters['user_filter']);
        }
        if (!empty($filters['batch_filter'])) {
            $query->where('poultry_batch_id', $filters['batch_filter']);
        }
        if (!empty($filters['category_filter'])) {
            $query->where('category', $filters['category_filter']);
        }
        if (!empty($filters['min_amount'])) {
            $query->where('amount', '>=', $filters['min_amount']);
        }
        if (!empty($filters['max_amount'])) {
            $query->where('amount', '<=', $filters['max_amount']);
        }

        return $query->latest('date')->get()->map(function ($exp) {
            return [
                'type' => 'expense',
                'date' => $exp->date->toDateString(),
                'description' => "Expense: {$exp->description}",
                'amount' => $exp->amount,
                'user' => $exp->recordedBy?->name ?? 'Unknown',
                'batch' => $exp->batch?->batch_id ?? null,
                'category' => $exp->category,
                'receipt_number' => $exp->receipt_number,
                'vendor' => $exp->vendor,
            ];
        })->toArray();
    }

    private static function queryFeed(array $filters): array
    {
        $query = FeedRecord::with('batch', 'recordedBy', 'inventoryItem');

        if (!empty($filters['date_from'])) {
            $query->whereDate('date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('date', '<=', $filters['date_to']);
        }
        if (!empty($filters['user_filter'])) {
            $query->where('recorded_by_id', $filters['user_filter']);
        }
        if (!empty($filters['batch_filter'])) {
            $query->where('poultry_batch_id', $filters['batch_filter']);
        }

        return $query->latest('date')->get()->map(function ($record) {
            return [
                'type' => 'feed',
                'date' => $record->date->toDateString(),
                'description' => "Feed: {$record->feed_used}kg of " . ($record->inventoryItem?->name ?? 'Unknown'),
                'amount' => $record->total_feed_cost,
                'user' => $record->recordedBy?->name ?? 'Unknown',
                'batch' => $record->batch?->batch_id ?? null,
            ];
        })->toArray();
    }

    private static function queryWeights(array $filters): array
    {
        $query = WeightRecord::with('batch', 'recordedBy');

        if (!empty($filters['date_from'])) {
            $query->whereDate('date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('date', '<=', $filters['date_to']);
        }
        if (!empty($filters['user_filter'])) {
            $query->where('recorded_by_id', $filters['user_filter']);
        }
        if (!empty($filters['batch_filter'])) {
            $query->where('poultry_batch_id', $filters['batch_filter']);
        }

        return $query->latest('date')->get()->map(function ($record) {
            return [
                'type' => 'weight',
                'date' => $record->date->toDateString(),
                'description' => "Weight: {$record->average_weight}kg (CV: {$record->coefficient_variation}%)",
                'amount' => null,
                'user' => $record->recordedBy?->name ?? 'Unknown',
                'batch' => $record->batch?->batch_id ?? null,
            ];
        })->toArray();
    }

    private static function queryFlock(array $filters): array
    {
        $query = FlockRecord::with('batch', 'recordedBy');

        if (!empty($filters['date_from'])) {
            $query->whereDate('date', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('date', '<=', $filters['date_to']);
        }
        if (!empty($filters['user_filter'])) {
            $query->where('recorded_by_id', $filters['user_filter']);
        }
        if (!empty($filters['batch_filter'])) {
            $query->where('poultry_batch_id', $filters['batch_filter']);
        }

        return $query->latest('date')->get()->map(function ($record) {
            return [
                'type' => 'flock',
                'date' => $record->date->toDateString(),
                'description' => "Flock: M:{$record->mortality}, C:{$record->culls}, S:{$record->slaughter}",
                'amount' => null,
                'user' => $record->recordedBy?->name ?? 'Unknown',
                'batch' => $record->batch?->batch_id ?? null,
            ];
        })->toArray();
    }

    private static function queryObservations(array $filters): array
    {
        $query = ObservationReport::with('reportedBy', 'category');

        if (!empty($filters['date_from'])) {
            $query->whereDate('reported_at', '>=', $filters['date_from']);
        }
        if (!empty($filters['date_to'])) {
            $query->whereDate('reported_at', '<=', $filters['date_to']);
        }
        if (!empty($filters['user_filter'])) {
            $query->where('reported_by_id', $filters['user_filter']);
        }
        if (!empty($filters['category_filter'])) {
            $query->whereHas('category', fn($q) => $q->where('name', 'like', "%{$filters['category_filter']}%"));
        }

        return $query->latest('reported_at')->get()->map(function ($report) {
            return [
                'type' => 'observation',
                'date' => $report->reported_at->toDateString(),
                'description' => "Observation: {$report->title}",
                'amount' => null,
                'user' => $report->reportedBy?->name ?? 'Unknown',
                'category' => $report->other_category ?? $report->category?->name,
                'status' => $report->status,
                'priority' => $report->priority,
            ];
        })->toArray();
    }

    private static function queryInventory(array $filters): array
    {
        $query = InventoryItem::with('createdBy');

        if (!empty($filters['category_filter'])) {
            $query->where('category', $filters['category_filter']);
        }
        if (!empty($filters['min_amount'])) {
            $query->where('cost_per_unit', '>=', $filters['min_amount']);
        }
        if (!empty($filters['max_amount'])) {
            $query->where('cost_per_unit', '<=', $filters['max_amount']);
        }

        return $query->latest('created_at')->get()->map(function ($item) {
            return [
                'type' => 'inventory',
                'date' => $item->updated_at?->toDateString() ?? $item->created_at->toDateString(),
                'description' => "Inventory: {$item->name} ({$item->category})",
                'amount' => $item->getTotalValue(),
                'user' => $item->createdBy?->name ?? 'System',
                'category' => $item->category,
                'quantity' => $item->quantity_in_stock,
                'unit' => $item->unit,
                'status' => $item->isLowStock() ? 'Low Stock' : ($item->isOutOfStock() ? 'Out of Stock' : 'In Stock'),
            ];
        })->toArray();
    }

    private static function queryAll(array $filters): array
    {
        $results = [];
        $expenses = self::queryExpenses($filters);
        $feed = self::queryFeed($filters);
        $weights = self::queryWeights($filters);
        $flock = self::queryFlock($filters);

        $results = array_merge($expenses, $feed, $weights, $flock);

        // Sort by date descending
        usort($results, fn($a, $b) => strtotime($b['date']) - strtotime($a['date']));

        return $results;
    }

    private static function generateSummary(array $results): array
    {
        $summary = [
            'total_amount' => 0,
            'avg_amount' => 0,
            'type_counts' => [],
            'date_range' => ['min' => null, 'max' => null],
        ];

        $amounts = [];
        $dates = [];

        foreach ($results as $row) {
            $type = $row['type'] ?? 'unknown';
            $summary['type_counts'][$type] = ($summary['type_counts'][$type] ?? 0) + 1;

            if (!empty($row['date'])) {
                $dates[] = $row['date'];
            }

            if (isset($row['amount']) && is_numeric($row['amount'])) {
                $amounts[] = (float) $row['amount'];
                $summary['total_amount'] += $row['amount'];
            }
        }

        if (!empty($amounts)) {
            $summary['avg_amount'] = $summary['total_amount'] / count($amounts);
        }

        if (!empty($dates)) {
            $summary['date_range']['min'] = min($dates);
            $summary['date_range']['max'] = max($dates);
        }

        return $summary;
    }
}