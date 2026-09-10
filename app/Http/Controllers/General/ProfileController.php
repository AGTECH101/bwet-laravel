<?php

namespace App\Http\Controllers\General;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class ProfileController extends Controller
{
    /**
     * Show the user's profile.
     */
    public function show()
    {
        $user = auth()->user();

        // For staff, get some stats
        $stats = [];
        if ($user->role === 'staff') {
            $stats = [
                'flock_records' => \App\Models\Poultry\FlockRecord::where('recorded_by_id', $user->id)->count(),
                'weight_records' => \App\Models\Poultry\WeightRecord::where('recorded_by_id', $user->id)->count(),
                'feed_records' => \App\Models\Poultry\FeedRecord::where('recorded_by_id', $user->id)->count(),
                'batches_created' => \App\Models\Poultry\Batch::where('created_by_id', $user->id)->count(),
                'last_flock_date' => \App\Models\Poultry\FlockRecord::where('recorded_by_id', $user->id)->latest('date')->first()?->date?->format('M d, Y'),
                'last_weight_date' => \App\Models\Poultry\WeightRecord::where('recorded_by_id', $user->id)->latest('date')->first()?->date?->format('M d, Y'),
                'last_feed_date' => \App\Models\Poultry\FeedRecord::where('recorded_by_id', $user->id)->latest('date')->first()?->date?->format('M d, Y'),
            ];
        }

        return view('general.profile.show', compact('user', 'stats'));
    }

    /**
     * Show the profile edit form.
     */
    public function edit()
    {
        return view('general.profile.edit', ['user' => auth()->user()]);
    }

    /**
     * Update the user's profile.
     */
    public function update(Request $request)
    {
        $user = auth()->user();

        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', 'max:255', Rule::unique('users')->ignore($user->id)],
            'phone' => 'nullable|string|max:20',
            'farm_location' => 'nullable|string|max:255',
            'current_password' => 'nullable|string|min:8',
            'password' => 'nullable|string|min:8|confirmed',
        ]);

        // Update basic info
        $user->name = $request->name;
        $user->email = $request->email;
        $user->phone = $request->phone;
        $user->farm_location = $request->farm_location;

        // Change password if provided
        if ($request->filled('current_password') && $request->filled('password')) {
            if (!Hash::check($request->current_password, $user->password)) {
                return back()->withErrors(['current_password' => 'The current password is incorrect.']);
            }
            $user->password = Hash::make($request->password);
        }

        $user->save();

        return redirect()->route('profile.show')->with('success', 'Profile updated successfully.');
    }
}