<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEventRequest;
use App\Http\Requests\UpdateEventRequest;
use App\Models\CaptainInvitation;
use App\Models\Event;
use App\Models\EventInvitation;
use App\Models\Group;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EventController extends Controller
{
    /**
     * Display a listing of events.
     */
    public function index(Request $request)
    {
        $query = Event::with('creator')
            ->withCount(['eventQuestions', 'submissions']);

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Search
        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('category', 'like', "%{$request->search}%");
            });
        }

        $events = $query->latest()->paginate(15);

        return Inertia::render('Admin/Events/Index', [
            'events' => $events,
            'filters' => $request->only(['status', 'search']),
        ]);
    }

    /**
     * Show the form for creating a new event.
     */
    public function create()
    {
        return Inertia::render('Admin/Events/Create');
    }

    /**
     * Store a newly created event in storage.
     */
    public function store(StoreEventRequest $request)
    {
        $event = Event::create([
            ...$request->validated(),
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('admin.events.show', $event)
            ->with('success', 'Event created successfully!');
    }

    /**
     * Display the specified event.
     */
    public function show(Event $event)
    {
        $event->load([
            'creator',
            'eventQuestions' => function ($query) {
                $query->orderBy('order');
            },
            'invitations.group',
            'groups',
        ]);

        $event->loadCount(['submissions', 'eventQuestions']);

        // Calculate statistics
        $stats = [
            'total_questions' => $event->event_questions_count,
            'total_submissions' => $event->submissions_count,
            'completed_submissions' => $event->submissions()->where('is_complete', true)->count(),
            'average_score' => (float) ($event->submissions()
                ->where('is_complete', true)
                ->avg('percentage') ?? 0),
            'participating_groups' => $event->groups()->count(),
        ];

        // Get all groups for invitation generation
        $availableGroups = Group::all();

        return Inertia::render('Admin/Events/Show', [
            'event' => $event,
            'stats' => $stats,
            'invitations' => $event->invitations,
            'availableGroups' => $availableGroups,
        ]);
    }

    /**
     * Show the form for editing the specified event.
     */
    public function edit(Event $event)
    {
        return Inertia::render('Admin/Events/Edit', [
            'event' => $event,
        ]);
    }

    /**
     * Update the specified event in storage.
     */
    public function update(UpdateEventRequest $request, Event $event)
    {
        $event->update($request->validated());

        return redirect()->route('admin.events.show', $event)
            ->with('success', 'Event updated successfully!');
    }

    /**
     * Remove the specified event from storage.
     */
    public function destroy(Event $event)
    {
        $eventName = $event->name;
        $event->delete();

        return redirect()->route('admin.events.index')
            ->with('success', "Event '{$eventName}' deleted successfully!");
    }

    /**
     * Update event status.
     */
    public function updateStatus(Request $request, Event $event)
    {
        $request->validate([
            'status' => 'required|in:draft,open,locked,in_progress,completed',
        ]);

        $event->update(['status' => $request->status]);

        return back()->with('success', 'Event status updated successfully!');
    }

    /**
     * Duplicate an event.
     */
    public function duplicate(Event $event)
    {
        $newEvent = $event->replicate();
        $newEvent->name = $event->name . ' (Copy)';
        $newEvent->status = 'draft';
        $newEvent->created_by = auth()->id();
        $newEvent->save();

        // Duplicate event questions
        foreach ($event->eventQuestions as $eventQuestion) {
            $newEventQuestion = $eventQuestion->replicate();
            $newEventQuestion->event_id = $newEvent->id;
            $newEventQuestion->save();
        }

        return redirect()->route('admin.events.show', $newEvent)
            ->with('success', 'Event duplicated successfully!');
    }

    /**
     * Get event statistics.
     */
    public function statistics(Event $event)
    {
        $stats = [
            'total_submissions' => $event->submissions()->count(),
            'completed_submissions' => $event->submissions()->where('is_complete', true)->count(),
            'pending_submissions' => $event->submissions()->where('is_complete', false)->count(),
            'average_score' => $event->submissions()->where('is_complete', true)->avg('percentage') ?? 0,
            'highest_score' => $event->submissions()->where('is_complete', true)->max('percentage') ?? 0,
            'lowest_score' => $event->submissions()->where('is_complete', true)->min('percentage') ?? 0,
            'total_participants' => $event->submissions()->distinct('user_id')->count('user_id'),
            'questions_count' => $event->eventQuestions()->count(),
        ];

        // Submissions by group
        $submissionsByGroup = $event->submissions()
            ->with('group')
            ->where('is_complete', true)
            ->get()
            ->groupBy('group_id')
            ->map(function ($submissions, $groupId) {
                $group = $submissions->first()->group;
                return [
                    'group_name' => $group->name,
                    'count' => $submissions->count(),
                    'average_score' => $submissions->avg('percentage'),
                ];
            })
            ->values();

        return Inertia::render('Admin/Events/Statistics', [
            'event' => $event,
            'stats' => $stats,
            'submissionsByGroup' => $submissionsByGroup,
        ]);
    }

    /**
     * Generate invitation for event-group combination.
     */
    public function generateInvitation(Request $request, Event $event)
    {
        $request->validate([
            'group_id' => 'required|exists:groups,id',
        ]);

        // Associate group with event if not already
        $group = Group::find($request->group_id);
        if (!$group->event_id) {
            $group->update(['event_id' => $event->id]);
        }

        // Check if invitation already exists
        $invitation = EventInvitation::where('event_id', $event->id)
            ->where('group_id', $request->group_id)
            ->first();

        if ($invitation) {
            // Reactivate if exists
            $invitation->update(['is_active' => true]);
        } else {
            // Create new invitation
            $invitation = EventInvitation::create([
                'event_id' => $event->id,
                'group_id' => $request->group_id,
                'token' => EventInvitation::generateToken(),
                'is_active' => true,
            ]);
        }

        return back()->with('success', 'Invitation link generated!');
    }

    /**
     * Deactivate invitation.
     */
    public function deactivateInvitation(Event $event, EventInvitation $invitation)
    {
        $invitation->update(['is_active' => false]);

        return back()->with('success', 'Invitation deactivated.');
    }

    /**
     * Generate captain invitation (quick method from event page).
     */
    public function generateCaptainInvitation(Request $request, Event $event)
    {
        $validated = $request->validate([
            'max_uses' => 'nullable|integer|min:1',
            'expires_at' => 'nullable|date|after:now',
        ]);

        $invitation = CaptainInvitation::create([
            'event_id' => $event->id,
            'token' => CaptainInvitation::generateToken(),
            'max_uses' => $validated['max_uses'] ?? null,
            'times_used' => 0,
            'expires_at' => $validated['expires_at'] ?? null,
            'is_active' => true,
            'created_by' => $request->user()->id,
        ]);

        return back()->with('success', 'Captain invitation link generated!');
    }
}
