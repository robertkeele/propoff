<?php

namespace App\Http\Controllers;

use App\Models\EventInvitation;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;

class GuestController extends Controller
{
    /**
     * Show the guest registration page.
     */
    public function show($token)
    {
        $invitation = EventInvitation::where('token', $token)
            ->with(['event', 'group'])
            ->firstOrFail();

        if (!$invitation->isValid()) {
            return Inertia::render('Guest/InvitationExpired', [
                'message' => 'This invitation is no longer valid.',
            ]);
        }

        return Inertia::render('Guest/Join', [
            'invitation' => [
                'token' => $invitation->token,
                'event' => [
                    'id' => $invitation->event->id,
                    'name' => $invitation->event->name,
                    'category' => $invitation->event->category,
                    'event_date' => $invitation->event->event_date,
                    'status' => $invitation->event->status,
                ],
                'group' => [
                    'id' => $invitation->group->id,
                    'name' => $invitation->group->name,
                ],
            ],
        ]);
    }

    /**
     * Register guest and auto-login.
     */
    public function register(Request $request, $token)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255',
        ]);

        $invitation = EventInvitation::where('token', $token)
            ->with(['event', 'group'])
            ->firstOrFail();

        if (!$invitation->isValid()) {
            return back()->withErrors(['token' => 'This invitation is no longer valid.']);
        }

        // Create guest user
        $guestToken = Str::random(32);
        $user = User::create([
            'name' => $request->name,
            'email' => $request->email, // Store email for Phase 2
            'password' => null,
            'role' => 'guest',
            'guest_token' => $guestToken,
        ]);

        // Add user to group with proper pivot data
        $invitation->group->users()->attach($user->id, [
            'joined_at' => now(),
            'is_captain' => false,
        ]);

        // Increment invitation usage
        $invitation->incrementUsage();

        // Auto-login
        Auth::login($user);

        // TODO Phase 2: Send email with personal link if email provided
        // if ($request->email) {
        //     Mail::to($request->email)->send(new GuestWelcome($user, $invitation->event));
        // }

        // Redirect to events play page
        return redirect()->route('events.play', $invitation->event->id)
            ->with('success', 'Welcome! You can now play the event.');
    }

    /**
     * Show guest results page.
     */
    public function results($guestToken)
    {
        $user = User::where('guest_token', $guestToken)
            ->where('role', 'guest')
            ->firstOrFail();

        // Get user's submissions
        $submissions = $user->submissions()
            ->with(['event', 'group'])
            ->latest()
            ->get()
            ->map(function ($submission) use ($user) {
                return [
                    'id' => $submission->id,
                    'event_id' => $submission->event_id,
                    'group_id' => $submission->group_id,
                    'event_name' => $submission->event->name,
                    'group_name' => $submission->group->name,
                    'total_score' => $submission->total_score,
                    'possible_points' => $submission->possible_points,
                    'percentage' => $submission->percentage,
                    'is_complete' => $submission->is_complete,
                    'submitted_at' => $submission->submitted_at,
                    'can_edit' => $user->canEditSubmission($submission),
                ];
            });

        return Inertia::render('Guest/MyResults', [
            'user' => [
                'name' => $user->name,
                'guest_token' => $user->guest_token,
            ],
            'submissions' => $submissions,
        ]);
    }
}
