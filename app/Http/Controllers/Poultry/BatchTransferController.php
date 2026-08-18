<?php

namespace App\Http\Controllers\Poultry;

use App\Http\Controllers\Controller;
use App\Models\Poultry\Batch;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class BatchTransferController extends Controller
{
    public function create(Request $request)
    {
        Gate::authorize('viewAny', Batch::class);

        $batches = Batch::where('status', 'active')->orderBy('created_at', 'desc')->get();
        $selectedFrom = $request->filled('from_batch') ? Batch::where('batch_id', $request->from_batch)->first() : null;

        return view('sectors.poultry.forms.batch-transfer', compact('batches', 'selectedFrom'));
    }

    public function store(Request $request)
    {
        Gate::authorize('update', Batch::class);

        $validated = $request->validate([
            'from_batch' => ['required', 'exists:poultry_batches,id'],
            'to_batch' => ['required', 'exists:poultry_batches,id', 'different:from_batch'],
            'birds_to_transfer' => ['required', 'integer', 'min:1'],
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $source = Batch::findOrFail($validated['from_batch']);
        $destination = Batch::findOrFail($validated['to_batch']);

        if ($validated['birds_to_transfer'] > $source->remaining_flock) {
            return back()->withInput()->withErrors([
                'birds_to_transfer' => 'Transfer quantity cannot exceed the remaining flock in the source batch.',
            ]);
        }

        $source->remaining_flock -= $validated['birds_to_transfer'];
        $destination->remaining_flock += $validated['birds_to_transfer'];
        $source->save();
        $destination->save();

        $source->updateCachedMetrics();
        $destination->updateCachedMetrics();

        return redirect()->route('poultry.forms.hub')
            ->with('success', 'Batch transfer completed successfully.');
    }
}
