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

        // Filter by batch if provided
        if ($request->has('batch')) {
            $activeBatches = $activeBatches->filter(function ($batch) use ($request) {
                return $batch->batch_id == $request->batch;
            });
        }

        // Get recent entries for the logged-in user (if staff)
        $user = auth()->user();
        $recentFlock = $user->role == 'staff' ? FlockRecord::where('recorded_by_id', $user->id)->latest()->limit(5)->get() : collect();
        $recentWeight = $user->role == 'staff' ? WeightRecord::where('recorded_by_id', $user->id)->latest()->limit(5)->get() : collect();
        $recentFeed = $user->role == 'staff' ? FeedRecord::where('recorded_by_id', $user->id)->latest()->limit(5)->get() : collect();

        return view('sectors.poultry.forms.hub', compact('activeBatches', 'recentFlock', 'recentWeight', 'recentFeed'));
    }
}