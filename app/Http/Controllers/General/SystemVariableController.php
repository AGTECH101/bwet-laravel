<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Models\SystemVariable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Gate;

class SystemVariableController extends Controller
{
    /**
     * Display a listing of system variables.
     * One row per key (guaranteed by unique constraint).
     */
    public function index()
    {
        Gate::authorize('manage-system-variables');

        $variables = SystemVariable::orderBy('category')->orderBy('name')->get();

        return view('general.system.variables.index', compact('variables'));
    }

    /**
     * Show the form for editing the specified variable.
     */
    public function edit(SystemVariable $variable)
    {
        Gate::authorize('manage-system-variables');

        return view('general.system.variables.edit', compact('variable'));
    }

    /**
     * Update the specified variable in place.
     *
     * NOTE: We update the existing row rather than creating a new version.
     * Historically this controller used ->replicate(), which produced a new
     * row on every edit and cluttered the settings page with duplicates of
     * the same key. Every calculation reads the "latest" row so numbers
     * were correct, but the UI became unmaintainable.
     *
     * The unique constraint on `key` (added in the deduplication migration)
     * enforces one row per key from the database side.
     */
    public function update(Request $request, SystemVariable $variable)
    {
        Gate::authorize('manage-system-variables');

        $validated = $request->validate([
            'value'       => 'required|string',
            'description' => 'nullable|string',
            'is_active'   => 'nullable|boolean',
        ]);

        $variable->value       = $validated['value'];
        $variable->description = $validated['description'] ?? $variable->description;
        $variable->is_active   = $request->has('is_active');
        $variable->updated_by_id = auth()->id();
        $variable->effective_from = $variable->effective_from ?? now();
        $variable->save();

        // Clear any cached value so the next read picks up the new number.
        Cache::forget("system_var_{$variable->key}");

        return redirect()->route('system.variables.index')
            ->with('success', "Variable '{$variable->name}' updated successfully.");
    }
}