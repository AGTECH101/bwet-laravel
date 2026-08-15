<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class AdminUserController extends Controller
{
    public function create()
    {
        Gate::authorize('manage-users');

        return view('general.admin.users.create');
    }

    public function store(Request $request)
    {
        Gate::authorize('manage-users');

        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users'],
            'phone' => ['required', 'string', 'max:20', 'regex:/^\+?[0-9\s\-()]+$/'],
            'role' => ['required', 'string', 'in:admin,manager,staff,investor'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'phone' => trim((string) $request->phone),
            'role' => $request->role,
            'password' => bcrypt($request->password),
            'is_approved' => true,
            'approved_by_id' => auth()->id(),
            'approved_at' => now(),
        ]);

        return redirect()->route('admin.users.index')->with('success', "User {$user->name} created successfully.");
    }

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