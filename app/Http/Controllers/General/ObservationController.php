<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Models\ObservationReport;
use App\Models\ObservationCategory;
use App\Models\Poultry\Batch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class ObservationController extends Controller
{
    public function index(Request $request)
    {
        $query = ObservationReport::with('category', 'reportedBy');

        if ($request->status) {
            $query->where('status', $request->status);
        }
        if ($request->priority) {
            $query->where('priority', $request->priority);
        }
        if (auth()->user()->role === 'manager') {
            $query->where('reported_by_id', auth()->id());
        }

        $observations = $query->latest('reported_at')->paginate(20);
        return view('general.observations.index', compact('observations'));
    }

    public function create()
    {
        Gate::authorize('create', ObservationReport::class);
        $categories = ObservationCategory::where('is_active', true)->get();
        $batches = Batch::where('status', 'active')->get();
        return view('general.observations.create', compact('categories', 'batches'));
    }

    public function store(Request $request)
    {
        Gate::authorize('create', ObservationReport::class);

        $data = $request->validate([
            'title' => 'required|string|max:200',
            'category_id' => 'nullable|exists:observation_categories,id',
            'other_category' => 'nullable|string|max:100',
            'description' => 'required|string',
            'priority' => 'required|in:low,medium,high,critical',
            'affected_batches' => 'nullable|array',
            'affected_batches.*' => 'exists:poultry_batches,id',
        ]);

        $report = ObservationReport::create([
            'title' => $data['title'],
            'category_id' => $data['category_id'] ?? null,
            'other_category' => $data['other_category'] ?? null,
            'description' => $data['description'],
            'priority' => $data['priority'],
            'reported_by_id' => auth()->id(),
            'reported_at' => now(),
            'status' => 'pending',
        ]);

        if (!empty($data['affected_batches'])) {
            $report->affected_batch_ids = $data['affected_batches'];
            $report->save();
        }

        // Create notification for admins
        \App\Models\Notification::createGlobal(
            'observation_report',
            "New Observation: {$report->title}",
            "{$report->reportedBy->name} submitted a new observation.",
            auth()->user(),
            null,
            $report
        );

        return redirect()->route('observations.index')->with('success', 'Observation submitted.');
    }

    public function show(ObservationReport $observation)
    {
        Gate::authorize('view', $observation);
        return view('general.observations.show', compact('observation'));
    }

    public function review(Request $request, ObservationReport $observation)
    {
        Gate::authorize('review', $observation);

        $request->validate([
            'action' => 'required|in:review,resolve,close',
            'admin_response' => 'nullable|string',
            'actions_taken' => 'nullable|string',
        ]);

        $observation->admin_response = $request->admin_response;
        $observation->actions_taken = $request->actions_taken;

        switch ($request->action) {
            case 'review':
                $observation->status = 'reviewed';
                $observation->reviewed_by_id = auth()->id();
                $observation->reviewed_at = now();
                break;
            case 'resolve':
                $observation->status = 'resolved';
                $observation->resolved_by_id = auth()->id();
                $observation->resolved_at = now();
                break;
            case 'close':
                $observation->status = 'closed';
                break;
        }
        $observation->save();

        return redirect()->route('observations.show', $observation)->with('success', 'Observation updated.');
    }
}