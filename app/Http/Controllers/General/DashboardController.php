<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Models\Sector;
use App\Services\Poultry\DashboardService as PoultryDashboardService;
use Illuminate\Support\Facades\Auth;

class DashboardController extends Controller
{
    public function quickRefresh()
    {
        return redirect()->route('dashboard')->with('success', 'Dashboard refreshed.');
    }

    public function index()
    {
        $user = Auth::user();
        $role = $user->role;

        if (! $user->is_approved) {
            return view('general.dashboard.pending-approval', compact('user', 'role'));
        }

        // Get selected sector from session
        $sectorId = session('selected_sector_id');
        $sector = Sector::find($sectorId);

        if (! $sector) {
            return redirect()->route('sectors.index');
        }

        // Determine which dashboard service to use
        $data = [];
        $view = "general.dashboard.{$role}";

        if ($sector->slug === 'poultry') {
            // Poultry sector uses the dedicated service
            $data = PoultryDashboardService::getDashboardData($user, $role);
        } else {
            // For other sectors, provide a generic fallback
            $data = [
                'sector' => $sector,
                'message' => "Dashboard for {$sector->name} is under development.",
            ];
            $view = 'general.dashboard.generic';
        }

        // Add common data
        $data['sector'] = $sector;
        $data['user'] = $user;
        $data['role'] = $role;

        return view($view, $data);
    }
}