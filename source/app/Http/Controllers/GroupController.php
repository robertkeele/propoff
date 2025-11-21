<?php

namespace App\Http\Controllers;

use App\Models\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Inertia\Inertia;

class GroupController extends Controller
{
    /**
     * Display a listing of groups.
     */
    public function index()
    {
        $userGroups = auth()->user()->groups()
            ->withCount('users')
            ->latest()
            ->get();

        $publicGroups = Group::where('is_public', true)
            ->whereDoesntHave('users', function ($query) {
                $query->where('user_id', auth()->id());
            })
            ->withCount('users')
            ->latest()
            ->limit(20)
            ->get();

        return Inertia::render('Groups/Index', [
            'userGroups' => $userGroups,
            'publicGroups' => $publicGroups,
        ]);
    }

    /**
     * Show the form for creating a new group.
     */
    public function create()
    {
        return Inertia::render('Groups/Create');
    }

    /**
     * Store a newly created group in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_public' => 'required|boolean',
        ]);

        // Generate unique group code
        $code = $this->generateUniqueCode();

        $group = Group::create([
            ...$validated,
            'code' => $code,
            'created_by' => auth()->id(),
        ]);

        // Automatically add creator to the group
        $group->users()->attach(auth()->id(), [
            'joined_at' => now(),
        ]);

        return redirect()->route('groups.show', $group)
            ->with('success', 'Group created successfully!');
    }

    /**
     * Display the specified group.
     */
    public function show(Group $group)
    {
        $this->authorize('view', $group);

        $group->load(['creator', 'users' => function ($query) {
            $query->orderBy('user_groups.joined_at', 'desc');
        }]);

        $group->loadCount('submissions');

        // Get recent submissions for this group
        $recentSubmissions = $group->submissions()
            ->with(['user', 'event'])
            ->where('is_complete', true)
            ->latest('submitted_at')
            ->limit(10)
            ->get();

        return Inertia::render('Groups/Show', [
            'group' => $group,
            'recentSubmissions' => $recentSubmissions,
            'isMember' => $group->users->contains(auth()->id()),
        ]);
    }

    /**
     * Show the form for editing the specified group.
     */
    public function edit(Group $group)
    {
        $this->authorize('update', $group);

        return Inertia::render('Groups/Edit', [
            'group' => $group,
        ]);
    }

    /**
     * Update the specified group in storage.
     */
    public function update(Request $request, Group $group)
    {
        $this->authorize('update', $group);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_public' => 'required|boolean',
        ]);

        $group->update($validated);

        return redirect()->route('groups.show', $group)
            ->with('success', 'Group updated successfully!');
    }

    /**
     * Remove the specified group from storage.
     */
    public function destroy(Group $group)
    {
        $this->authorize('delete', $group);

        $group->delete();

        return redirect()->route('groups.index')
            ->with('success', 'Group deleted successfully!');
    }

    /**
     * Join a group using its code.
     */
    public function join(Request $request)
    {
        $validated = $request->validate([
            'code' => 'required|string|exists:groups,code',
        ]);

        $group = Group::where('code', $validated['code'])->firstOrFail();

        // Check if user is already a member
        if ($group->users->contains(auth()->id())) {
            return back()->with('error', 'You are already a member of this group.');
        }

        // Add user to group
        $group->users()->attach(auth()->id(), [
            'joined_at' => now(),
        ]);

        return redirect()->route('groups.show', $group)
            ->with('success', 'Successfully joined the group!');
    }

    /**
     * Leave a group.
     */
    public function leave(Group $group)
    {
        // Check if user is a member
        if (!$group->users->contains(auth()->id())) {
            return back()->with('error', 'You are not a member of this group.');
        }

        // Don't allow creator to leave
        if ($group->created_by === auth()->id()) {
            return back()->with('error', 'Group creator cannot leave. Delete the group instead.');
        }

        $group->users()->detach(auth()->id());

        return redirect()->route('groups.index')
            ->with('success', 'You have left the group.');
    }

    /**
     * Remove a user from the group.
     */
    public function removeMember(Group $group, $userId)
    {
        $this->authorize('update', $group);

        if ($group->created_by == $userId) {
            return back()->with('error', 'Cannot remove the group creator.');
        }

        $group->users()->detach($userId);

        return back()->with('success', 'Member removed from group.');
    }

    /**
     * Generate a unique group code.
     */
    protected function generateUniqueCode()
    {
        do {
            $code = strtoupper(Str::random(8));
        } while (Group::where('code', $code)->exists());

        return $code;
    }

    /**
     * Regenerate group code.
     */
    public function regenerateCode(Group $group)
    {
        $this->authorize('update', $group);

        $group->update([
            'code' => $this->generateUniqueCode(),
        ]);

        return back()->with('success', 'Group code regenerated successfully!');
    }
}
