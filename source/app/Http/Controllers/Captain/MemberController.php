<?php

namespace App\Http\Controllers\Captain;

use App\Http\Controllers\Controller;
use App\Models\Group;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class MemberController extends Controller
{
    /**
     * Display a listing of group members.
     */
    public function index(Request $request, Group $group)
    {
        $members = $group->members()
            ->withPivot('is_captain', 'joined_at')
            ->orderByPivot('is_captain', 'desc')
            ->orderByPivot('joined_at', 'asc')
            ->get()
            ->map(function ($member) {
                return [
                    'id' => $member->id,
                    'name' => $member->name,
                    'email' => $member->email,
                    'is_captain' => $member->pivot->is_captain,
                    'joined_at' => $member->pivot->joined_at,
                    'submissions_count' => $member->submissions()->where('group_id', $member->pivot->group_id)->count(),
                ];
            });

        return Inertia::render('Captain/Members/Index', [
            'group' => [
                'id' => $group->id,
                'name' => $group->name,
                'join_code' => $group->join_code,
                'event' => [
                    'id' => $group->event->id,
                    'name' => $group->event->name,
                ],
            ],
            'members' => $members,
        ]);
    }

    /**
     * Promote a member to captain.
     */
    public function promoteToCaptain(Request $request, Group $group, User $user)
    {
        // Check if user is a member of the group
        if (!$group->members()->where('user_id', $user->id)->exists()) {
            return back()->with('error', 'User is not a member of this group.');
        }

        // Check if already a captain
        if ($group->isCaptain($user->id)) {
            return back()->with('error', 'User is already a captain of this group.');
        }

        // Promote to captain
        $group->promoteToCapta($user->id);

        return back()->with('success', "{$user->name} has been promoted to captain!");
    }

    /**
     * Demote a captain to regular member.
     */
    public function demoteFromCaptain(Request $request, Group $group, User $user)
    {
        // Check if user is a captain
        if (!$group->isCaptain($user->id)) {
            return back()->with('error', 'User is not a captain of this group.');
        }

        // Check if this is the last captain
        if ($group->captains()->count() <= 1) {
            return back()->with('error', 'Cannot demote the last captain. Promote someone else first.');
        }

        // Demote from captain
        $group->demoteFromCaptain($user->id);

        return back()->with('success', "{$user->name} has been demoted to regular member.");
    }

    /**
     * Remove a member from the group.
     */
    public function remove(Request $request, Group $group, User $user)
    {
        // Check if user is a member of the group
        if (!$group->members()->where('user_id', $user->id)->exists()) {
            return back()->with('error', 'User is not a member of this group.');
        }

        // Check if user is a captain
        $isCaptain = $group->isCaptain($user->id);

        // If captain, check if they're the last one
        if ($isCaptain && $group->captains()->count() <= 1) {
            return back()->with('error', 'Cannot remove the last captain. Promote someone else first or delete the group.');
        }

        // Check if user has submissions
        $hasSubmissions = $group->submissions()->where('user_id', $user->id)->exists();
        if ($hasSubmissions) {
            return back()->with('error', 'Cannot remove member with existing submissions.');
        }

        // Remove from group
        $group->members()->detach($user->id);

        return back()->with('success', "{$user->name} has been removed from the group.");
    }

    /**
     * Regenerate the join code for the group.
     */
    public function regenerateJoinCode(Request $request, Group $group)
    {
        $group->update([
            'join_code' => \Illuminate\Support\Str::upper(\Illuminate\Support\Str::random(8)),
        ]);

        return back()->with('success', 'Join code regenerated successfully!');
    }
}
