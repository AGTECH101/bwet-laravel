<?php

namespace App\Http\Controllers\Poultry;

use App\Http\Controllers\Controller;
use App\Http\Requests\Poultry\FlockRecordRequest;
use App\Models\Poultry\Batch;
use App\Models\Poultry\FlockRecord;
use App\Services\Poultry\BatchRecalculationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\DB;

class FlockRecordController extends Controller
{
    public function index(Batch $batch)
    {
        Gate::authorize('view', $batch);
        $records = $batch->flockRecords()
            ->with('recordedBy')
            ->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);
        return view('sectors.poultry.flock-records.index', compact('batch', 'records'));
    }

    public function create(?Batch $batch = null)
    {
        Gate::authorize('create', FlockRecord::class);

        $batches = Batch::query()
            ->where('status', 'active')
            ->orderBy('start_date', 'desc')
            ->get();

        return view('sectors.poultry.flock-records.create', compact('batch', 'batches'));
    }

    /**
     * Store a new flock record.
     * Multiple records per day are allowed (no unique constraint).
     * Every submission triggers a full recalculation.
     */
    public function store(FlockRecordRequest $request)
    {
        Gate::authorize('create', FlockRecord::class);

        $data = $request->validated();
        $data['recorded_by_id'] = auth()->id();

        $batch = Batch::findOrFail($data['poultry_batch_id']);
        if ($batch->status !== 'active') {
            return redirect()->back()->with('error', 'Cannot add records to a closed or completed batch.');
        }

        DB::transaction(function () use ($data, $batch) {
            FlockRecord::create($data);
            BatchRecalculationService::recalculateAll($batch);
        });

        return redirect()->route('poultry.batches.show', $batch)
            ->with('success', 'Flock record saved and metrics recalculated.');
    }

    public function edit(FlockRecord $flockRecord)
    {
        Gate::authorize('update', $flockRecord);
        return view('sectors.poultry.flock-records.edit', compact('flockRecord'));
    }

    public function update(FlockRecordRequest $request, FlockRecord $flockRecord)
    {
        Gate::authorize('update', $flockRecord);

        $data = $request->validated();

        DB::transaction(function () use ($flockRecord, $data) {
            $batch = $flockRecord->batch;
            $flockRecord->update($data);
            BatchRecalculationService::recalculateAll($batch);
        });

        return redirect()->route('poultry.batches.show', $flockRecord->batch)
            ->with('success', 'Flock record updated and metrics recalculated.');
    }

    public function destroy(FlockRecord $flockRecord)
    {
        Gate::authorize('delete', $flockRecord);

        DB::transaction(function () use ($flockRecord) {
            $batch = $flockRecord->batch;
            $flockRecord->delete();
            BatchRecalculationService::recalculateAll($batch);
        });

        return redirect()->back()->with('success', 'Flock record deleted and metrics recalculated.');
    }
}