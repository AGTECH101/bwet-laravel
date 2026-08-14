<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Models\HistoryQuery;
use App\Models\Batch;
use App\Models\User;
use App\Services\HistoryQueryService;
use Illuminate\Http\Request;

class HistoryQueryController extends Controller
{
    public function index()
    {
        $queries = HistoryQuery::where('created_by_id', auth()->id())
            ->latest('last_executed')
            ->take(10)
            ->get();

        $batches = Batch::where('status', 'active')->get();
        $users = User::where('is_approved', true)->get();

        return view('general.history.index', compact('queries', 'batches', 'users'));
    }

    public function execute(Request $request)
    {
        $data = $request->validate([
            'name' => 'nullable|string|max:200',
            'query_type' => 'required|string|in:expenses,feed,weight,flock,observations,inventory,all',
            'date_from' => 'nullable|date',
            'date_to' => 'nullable|date|after_or_equal:date_from',
            'user_filter' => 'nullable|exists:users,id',
            'batch_filter' => 'nullable|exists:poultry_batches,id',
            'category_filter' => 'nullable|string|max:100',
            'min_amount' => 'nullable|numeric|min:0',
            'max_amount' => 'nullable|numeric|min:0|gt:min_amount',
        ]);

        $result = HistoryQueryService::execute($data);

        if (!empty($data['name'])) {
            $query = HistoryQuery::create([
                'name' => $data['name'],
                'query_type' => $data['query_type'],
                'date_from' => $data['date_from'] ?? null,
                'date_to' => $data['date_to'] ?? null,
                'user_filter_id' => $data['user_filter'] ?? null,
                'batch_filter_id' => $data['batch_filter'] ?? null,
                'category_filter' => $data['category_filter'] ?? null,
                'min_amount' => $data['min_amount'] ?? null,
                'max_amount' => $data['max_amount'] ?? null,
                'result_count' => $result['count'],
                'execution_time_ms' => $result['execution_time_ms'],
                'last_executed' => now(),
                'created_by_id' => auth()->id(),
            ]);
        }

        return view('general.history.results', [
            'results' => $result['results'],
            'summary' => $result['summary'] ?? null,
        ]);
    }

    public function show(HistoryQuery $historyQuery)
    {
        Gate::authorize('view', $historyQuery);
        // Re-run the query
        $data = $historyQuery->toArray();
        $result = HistoryQueryService::execute($data);
        return view('general.history.show', compact('historyQuery', 'result'));
    }
}