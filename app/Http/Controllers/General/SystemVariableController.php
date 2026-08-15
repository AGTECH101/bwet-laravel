<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Models\SystemVariable;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class SystemVariableController extends Controller
{
    /**
     * Display a listing of system variables.
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
     * Update the specified variable in storage.
     */
    public function update(Request $request, SystemVariable $variable)
    {
        Gate::authorize('manage-system-variables');

        $request->validate([
            'value' => 'required|string',
            'description' => 'nullable|string',
            'is_active' => 'nullable|boolean',
        ]);

        $newVariable = $variable->replicate();
        $newVariable->value = $request->value;
        $newVariable->description = $request->description;
        $newVariable->is_active = $request->has('is_active');
        $newVariable->updated_by_id = auth()->id();
        $newVariable->effective_from = now();
        $newVariable->save();

        \Illuminate\Support\Facades\Cache::forget("system_var_{$variable->key}");

        return redirect()->route('system.variables.index')
            ->with('success', "Variable '{$variable->name}' updated successfully.");
    }
}