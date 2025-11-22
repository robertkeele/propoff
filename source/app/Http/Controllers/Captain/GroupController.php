<?php

namespace App\Http\Controllers\Captain;

use App\Http\Controllers\Controller;
use App\Http\Requests\Captain\CreateGroupRequest;
use App\Models\CaptainInvitation;
use App\Models\Event;
use App\Models\EventInvitation;
use App\Models\Group;
use App\Models\GroupQuestion;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Inertia\Inertia;

class GroupController extends Controller
{
    /**
     * Join via captain invitation token (redirects to event-specific create page).
     */
    public function join(Request $request, string $token)
    {
        // Find the invitation by token
        $invitation = CaptainInvitation::where('token', $token)->firstOrFail();

        // Redirect to the event-specific create group page
        return redirect()->route('captain.groups.create', [
            'event' => $invitation->event_id,
            'token' => $token,
        ]);
    }

    /**
     * Show the form for creating a new group from captain invitation.
     */
    public function create(Request $request, Event $event, string $token)
    {
        // Find and validate the captain invitation
        $invitation = CaptainInvitation::where('event_id', $event->id)
            ->where('token', $token)
            ->firstOrFail();

        // Check if invitation can be used
        if (!$invitation->canBeUsed()) {
            return Inertia::render('Captain/InvitationExpired', [
                'event' => [
                    'id' => $event->id,
                    'name' => $event->name,
                    'category' => $event->category,
                    'event_date' => $event->event_date,
                ],
                'invitation' => [
                    'is_active' => $invitation->is_active,
                    'expires_at' => $invitation->expires_at,
                    'max_uses' => $invitation->max_uses,
                    'times_used' => $invitation->times_used,
                ],
            ]);
        }

        // Show create group form (works for both guests and authenticated users)
        return Inertia::render('Captain/CreateGroup', [
            'event' => [
                'id' => $event->id,
                'name' => $event->name,
                'category' => $event->category,
                'description' => $event->description,
                'event_date' => $event->event_date,
                'status' => $event->status,
            ],
            'invitation' => [
                'id' => $invitation->id,
                'token' => $invitation->token,
                'max_uses' => $invitation->max_uses,
                'times_used' => $invitation->times_used,
                'expires_at' => $invitation->expires_at,
            ],
            'isGuest' => !$request->user(),
        ]);
    }

    /**
     * Store a newly created group and make the user a captain.
     */
    public function store(CreateGroupRequest $request, Event $event, string $token)
    {
        // Find and validate the captain invitation
        $invitation = CaptainInvitation::where('event_id', $event->id)
            ->where('token', $token)
            ->firstOrFail();

        // Check if invitation can be used
        if (!$invitation->canBeUsed()) {
            return back()->with('error', 'This invitation has expired or reached its usage limit.');
        }

        // Get or create user
        $user = $request->user();

        if (!$user) {
            // Create guest user and auto-login
            $guestToken = Str::random(32);
            $user = User::create([
                'name' => $request->captain_name,
                'email' => $request->captain_email, // Optional email
                'password' => null,
                'role' => 'guest',
                'guest_token' => $guestToken,
            ]);

            // Auto-login
            \Auth::login($user);
        }

        // Create the group
        $group = Group::create([
            'event_id' => $event->id,
            'name' => $request->name,
            'description' => $request->description,
            'grading_source' => $request->grading_source,
            'code' => Str::upper(Str::random(8)),
            'created_by' => $user->id,
        ]);

        // Add user to group as captain
        $group->members()->attach($user->id, [
            'is_captain' => true,
            'joined_at' => now(),
        ]);

        // Create group questions from event questions
        $eventQuestions = $event->eventQuestions()->orderBy('display_order')->get();

        foreach ($eventQuestions as $eventQuestion) {
            GroupQuestion::create([
                'group_id' => $group->id,
                'event_question_id' => $eventQuestion->id,
                'question_text' => $eventQuestion->question_text,
                'question_type' => $eventQuestion->question_type,
                'options' => $eventQuestion->options,
                'points' => $eventQuestion->points,
                'display_order' => $eventQuestion->display_order,
                'is_active' => true,
                'is_custom' => false,
            ]);
        }

        // Increment invitation usage
        $invitation->incrementUsage();

        // Auto-generate EventInvitation for this group so captain can invite members
        EventInvitation::create([
            'event_id' => $event->id,
            'group_id' => $group->id,
            'token' => EventInvitation::generateToken(),
            'max_uses' => null, // Unlimited uses
            'times_used' => 0,
            'expires_at' => null, // No expiration
            'is_active' => true,
        ]);

        return redirect()->route('captain.dashboard')
            ->with('success', 'Group created successfully! You are now a captain of this group.');
    }

    /**
     * Display the specified group (captain view).
     */
    public function show(Request $request, Group $group)
    {
        // Check if user is captain of this group
        if (!$request->user()->isCaptainOf($group->id) && $request->user()->role !== 'admin') {
            abort(403, 'You must be a captain of this group to view it.');
        }

        $group->load([
            'event',
            'members' => function ($query) {
                $query->withPivot('is_captain', 'joined_at')
                    ->orderByPivot('is_captain', 'desc')
                    ->orderByPivot('joined_at', 'asc');
            },
        ]);

        // Get statistics
        $stats = [
            'total_members' => $group->members()->count(),
            'total_captains' => $group->captains()->count(),
            'total_questions' => $group->groupQuestions()->active()->count(),
            'total_submissions' => $group->submissions()->where('is_complete', true)->count(),
            'answered_questions' => $group->groupQuestionAnswers()->count(),
        ];

        return Inertia::render('Captain/Groups/Show', [
            'group' => [
                'id' => $group->id,
                'name' => $group->name,
                'description' => $group->description,
                'grading_source' => $group->grading_source,
                'join_code' => $group->code,
                'event' => [
                    'id' => $group->event->id,
                    'name' => $group->event->name,
                    'status' => $group->event->status,
                    'event_date' => $group->event->event_date,
                    'lock_date' => $group->event->lock_date,
                ],
                'members' => $group->members->map(function ($member) {
                    return [
                        'id' => $member->id,
                        'name' => $member->name,
                        'email' => $member->email,
                        'is_captain' => $member->pivot->is_captain,
                        'joined_at' => $member->pivot->joined_at,
                    ];
                }),
                'created_at' => $group->created_at,
            ],
            'stats' => $stats,
        ]);
    }

    /**
     * Update the specified group.
     */
    public function update(Request $request, Group $group)
    {
        // Check if user is captain of this group
        if (!$request->user()->isCaptainOf($group->id) && $request->user()->role !== 'admin') {
            abort(403, 'You must be a captain of this group to update it.');
        }

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string|max:1000',
            'grading_source' => 'required|in:captain,admin',
        ]);

        $group->update($validated);

        return back()->with('success', 'Group updated successfully!');
    }

    /**
     * Remove the specified group.
     */
    public function destroy(Request $request, Group $group)
    {
        // Only allow if user is captain and group has no submissions
        if (!$request->user()->isCaptainOf($group->id) && $request->user()->role !== 'admin') {
            abort(403, 'You must be a captain of this group to delete it.');
        }

        if ($group->submissions()->exists()) {
            return back()->with('error', 'Cannot delete group with existing submissions.');
        }

        $group->delete();

        return redirect()->route('captain.dashboard')
            ->with('success', 'Group deleted successfully!');
    }
}
