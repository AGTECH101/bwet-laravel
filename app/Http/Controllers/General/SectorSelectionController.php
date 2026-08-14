<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Models\Sector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SectorSelectionController extends Controller
{
    /**
     * Show the sector selection page.
     */
    public function index()
    {
        $sectors = Sector::where('is_live', true)->get();
        return view('sectors.index', compact('sectors'));
    }

    /**
     * Store the selected sector in session and redirect to dashboard.
     */
    public function select(Request $request)
    {
        $request->validate([
            'sector_id' => 'required|exists:sectors,id',
        ]);

        $sector = Sector::findOrFail($request->sector_id);

        // Store in session
        session(['selected_sector_id' => $sector->id]);
        session(['selected_sector_slug' => $sector->slug]);

        return redirect()->route('dashboard');
    }
}