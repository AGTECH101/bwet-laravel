<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SectorSelected
{
    public function handle(Request $request, Closure $next)
    {
        if (!session()->has('selected_sector_id')) {
            return redirect()->route('sectors.index');
        }

        // Optionally, verify sector exists
        $sector = \App\Models\Sector::find(session('selected_sector_id'));
        if (!$sector) {
            session()->forget(['selected_sector_id', 'selected_sector_slug']);
            return redirect()->route('sectors.index');
        }

        return $next($request);
    }
}