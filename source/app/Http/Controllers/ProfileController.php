<?php

namespace App\Http\Controllers;

use App\Http\Requests\ProfileUpdateRequest;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Inertia\Response;

class ProfileController extends Controller
{
    /**
     * Display the user's profile with statistics.
     */
    public function show(Request $request): Response
    {
        $user = $request->user();

        // Get user statistics
        $stats = [
            'total_events' => \App\Models\Submission::where('user_id', $user->id)
                ->distinct('event_id')
                ->count('event_id'),
            'completed_submissions' => \App\Models\Submission::where('user_id', $user->id)
                ->where('is_complete', true)
                ->count(),
            'groups_joined' => $user->groups()->count(),
            'average_score' => \App\Models\Submission::where('user_id', $user->id)
                ->where('is_complete', true)
                ->avg('percentage') ?? 0,
            'best_score' => \App\Models\Submission::where('user_id', $user->id)
                ->where('is_complete', true)
                ->max('percentage') ?? 0,
            'total_points' => \App\Models\Submission::where('user_id', $user->id)
                ->where('is_complete', true)
                ->sum('total_score'),
        ];

        // Get recent activity
        $recentActivity = \App\Models\Leaderboard::where('user_id', $user->id)
            ->with(['event', 'group'])
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return Inertia::render('Profile/Show', [
            'user' => $user,
            'stats' => $stats,
            'recentActivity' => $recentActivity,
        ]);
    }

    /**
     * Display the user's profile form.
     */
    public function edit(Request $request): Response
    {
        return Inertia::render('Profile/Edit', [
            'mustVerifyEmail' => $request->user() instanceof MustVerifyEmail,
            'status' => session('status'),
        ]);
    }

    /**
     * Update the user's profile information.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $request->user()->fill($request->validated());

        if ($request->user()->isDirty('email')) {
            $request->user()->email_verified_at = null;
        }

        $request->user()->save();

        return Redirect::route('profile.edit');
    }

    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validate([
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
