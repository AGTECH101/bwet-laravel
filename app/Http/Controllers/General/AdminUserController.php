<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        Gate::authorize('manage-users');

        $query = User::with('roles')->orderBy('created_at', 'desc');

        if ($request->role) {
            $query->where('role', $request->role);
        }
        if ($request->approved === 'pending') {
            $query->where('is_approved', false);
        } elseif ($request->approved === 'approved') {
            $query->where('is_approved', true);
        }

        $users = $query->paginate(20);
        $pendingCount = User::where('is_approved', false)->count();

        return view('general.admin.users.index', compact('users', 'pendingCount'));
    }

    public function approve(User $user)
    {
        Gate::authorize('manage-users');

        $user->update([
            'is_approved' => true,
            'approved_by_id' => auth()->id(),
            'approved_at' => now(),
        ]);

        return redirect()->back()->with('success', "User {$user->name} approved.");
    }

    public function deactivate(User $user)
    {
        Gate::authorize('manage-users');

        $user->update(['is_active' => false]);
        return redirect()->back()->with('success', "User {$user->name} deactivated.");
    }
}