<?php

namespace App\Http\Controllers\Poultry;

use App\Http\Controllers\Controller;
use App\Models\Poultry\Batch;
use App\Models\Poultry\FlockRecord;
use App\Models\Poultry\WeightRecord;
use App\Models\Poultry\FeedRecord;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class FormHubController extends Controller
{
    /**
     * Display the form hub.
     */
    public function index(Request $request)
    {
        Gate::authorize('viewAny', Batch::class);

        $activeBatches = Batch::where('status', 'active')->orderBy('created_at', 'desc')->get();

        // Filter by batch if provided, but keep a safe fallback so users do not get a dead-end when a batch ID is not found.
        if ($request->filled('batch')) {
            $requestedBatch = trim((string) $request->batch);
            $activeBatches = $activeBatches->filter(function ($batch) use ($requestedBatch) {
                return $batch->batch_id == $requestedBatch;
            });

            if ($activeBatches->isEmpty()) {
                $activeBatches = Batch::where('status', 'active')->orderBy('created_at', 'desc')->get();
                $request->session()->flash('warning', 'No active batch matched "' . e($requestedBatch) . '". Showing all active batches instead.');
            }
        }

        // Get recent entries for the logged-in user (if staff)
        $user = auth()->user();
        $recentFlock = $user->role == 'staff' ? FlockRecord::where('recorded_by_id', $user->id)->latest()->limit(5)->get() : collect();
        $recentWeight = $user->role == 'staff' ? WeightRecord::where('recorded_by_id', $user->id)->latest()->limit(5)->get() : collect();
        $recentFeed = $user->role == 'staff' ? FeedRecord::where('recorded_by_id', $user->id)->latest()->limit(5)->get() : collect();

        return view('sectors.poultry.forms.hub', compact('activeBatches', 'recentFlock', 'recentWeight', 'recentFeed'));
    }
}