<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\Request;
use Inertia\Inertia;

class EventController extends Controller
{
    /**
     * Display a listing of events.
     */
    public function index()
    {
        $events = Event::with('creator')
            ->withCount(['questions', 'submissions'])
            ->latest()
            ->paginate(15);

        return Inertia::render('Events/Index', [
            'events' => $events,
        ]);
    }

    /**
     * Show the form for creating a new event.
     */
    public function create()
    {
        return Inertia::render('Events/Create');
    }

    /**
     * Store a newly created event in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|string|max:100',
            'event_date' => 'required|date',
            'status' => 'required|in:draft,open,locked,in_progress,completed',
            'lock_date' => 'nullable|date|after:now',
        ]);

        $event = Event::create([
            ...$validated,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('events.show', $event)
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
                $query->orderBy('display_order');
            },
        ]);

        $event->loadCount('submissions');

        return Inertia::render('Events/Show', [
            'event' => $event,
        ]);
    }

    /**
     * Show the form for editing the specified event.
     */
    public function edit(Event $event)
    {
        $this->authorize('update', $event);

        return Inertia::render('Events/Edit', [
            'event' => $event,
        ]);
    }

    /**
     * Update the specified event in storage.
     */
    public function update(Request $request, Event $event)
    {
        $this->authorize('update', $event);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'category' => 'required|string|max:100',
            'event_date' => 'required|date',
            'status' => 'required|in:draft,open,locked,in_progress,completed',
            'lock_date' => 'nullable|date|after:now',
        ]);

        $event->update($validated);

        return redirect()->route('events.show', $event)
            ->with('success', 'Event updated successfully!');
    }

    /**
     * Remove the specified event from storage.
     */
    public function destroy(Event $event)
    {
        $this->authorize('delete', $event);

        $event->delete();

        return redirect()->route('events.index')
            ->with('success', 'Event deleted successfully!');
    }

    /**
     * Display events available for play.
     */
    public function available()
    {
        $user = auth()->user();

        $events = Event::where('status', 'open')
            ->where(function ($query) {
                $query->whereNull('lock_date')
                    ->orWhere('lock_date', '>', now());
            })
            ->with([
                'creator',
                'groups' => function ($query) use ($user) {
                    $query->whereHas('users', function ($q) use ($user) {
                        $q->where('users.id', $user->id);
                    })
                    ->select('groups.id', 'groups.name', 'groups.code', 'groups.event_id');
                }
            ])
            ->withCount('questions')
            ->latest('event_date')
            ->paginate(15);

        return Inertia::render('Events/Available', [
            'events' => $events,
        ]);
    }

    /**
     * Play a specific event.
     */
    public function play(Event $event)
    {
        // Check if event is playable
        if ($event->status !== 'open') {
            return redirect()->route('events.available')
                ->with('error', 'This event is not currently available.');
        }

        // Check if lock date has passed
        if ($event->lock_date && $event->lock_date->isPast()) {
            return redirect()->route('events.available')
                ->with('error', 'This event is locked.');
        }

        // Load event questions
        $event->load(['eventQuestions' => function ($query) {
            $query->orderBy('display_order');
        }]);

        // Get user's groups for this event
        $userGroups = auth()->user()->groups()
            ->where('event_id', $event->id)
            ->select('groups.id', 'groups.name', 'groups.code')
            ->get();

        return Inertia::render('Events/Play', [
            'event' => $event,
            'userGroups' => $userGroups,
        ]);
    }

    /**
     * Join an event (create submission).
     */
    public function join(Request $request, Event $event)
    {
        $this->authorize('submit', $event);

        $validated = $request->validate([
            'group_id' => 'required|exists:groups,id',
        ]);

        // Check if user is a member of the group
        if (!$request->user()->groups()->where('groups.id', $validated['group_id'])->exists()) {
            return back()->with('error', 'You are not a member of this group.');
        }

        // Check if group belongs to this event
        $group = \App\Models\Group::find($validated['group_id']);
        if ($group->event_id !== $event->id) {
            return back()->with('error', 'This group is not participating in this event.');
        }

        // Check if user already has a submission for this event and group
        $existingSubmission = \App\Models\Submission::where('event_id', $event->id)
            ->where('user_id', $request->user()->id)
            ->where('group_id', $validated['group_id'])
            ->first();

        if ($existingSubmission) {
            return redirect()->route('submissions.continue', $existingSubmission)
                ->with('info', 'You have already joined this event for this group.');
        }

        // Create new submission
        $submission = \App\Models\Submission::create([
            'event_id' => $event->id,
            'user_id' => $request->user()->id,
            'group_id' => $validated['group_id'],
            'total_score' => 0,
            'possible_points' => 0,
            'percentage' => 0,
            'is_complete' => false,
            'submitted_at' => null,
        ]);

        return redirect()->route('submissions.continue', $submission)
            ->with('success', 'Successfully joined the event!');
    }

    /**
     * Submit answers for an event.
     */
    public function submitAnswers(Request $request, Event $event)
    {
        $this->authorize('submit', $event);

        $validated = $request->validate([
            'group_id' => 'required|exists:groups,id',
            'answers' => 'required|array',
            'answers.*.group_question_id' => 'required|exists:group_questions,id',
            'answers.*.answer_text' => 'required|string',
        ]);

        // Get or create submission
        $submission = \App\Models\Submission::firstOrCreate(
            [
                'event_id' => $event->id,
                'user_id' => $request->user()->id,
                'group_id' => $validated['group_id'],
            ],
            [
                'total_score' => 0,
                'possible_points' => 0,
                'percentage' => 0,
                'is_complete' => false,
            ]
        );

        // Check if submission can be edited
        if (!$request->user()->can('update', $submission)) {
            return back()->with('error', 'This submission can no longer be edited.');
        }

        // Save or update answers
        foreach ($validated['answers'] as $answerData) {
            \App\Models\UserAnswer::updateOrCreate(
                [
                    'submission_id' => $submission->id,
                    'group_question_id' => $answerData['group_question_id'],
                ],
                [
                    'answer_text' => $answerData['answer_text'],
                    'points_earned' => 0, // Will be calculated when captain/admin sets correct answers
                    'is_correct' => false, // Will be determined when captain/admin sets correct answers
                ]
            );
        }

        // Mark submission as complete
        $submission->update([
            'is_complete' => true,
            'submitted_at' => now(),
        ]);

        return redirect()->route('dashboard')
            ->with('success', 'Your answers have been submitted successfully!');
    }

    /**
     * Update answers before event is locked.
     */
    public function updateAnswers(Request $request, Event $event)
    {
        $this->authorize('submit', $event);

        $validated = $request->validate([
            'group_id' => 'required|exists:groups,id',
            'answers' => 'required|array',
            'answers.*.group_question_id' => 'required|exists:group_questions,id',
            'answers.*.answer_text' => 'required|string',
        ]);

        // Get submission
        $submission = \App\Models\Submission::where('event_id', $event->id)
            ->where('user_id', $request->user()->id)
            ->where('group_id', $validated['group_id'])
            ->firstOrFail();

        // Check if submission can be edited
        if (!$request->user()->can('update', $submission)) {
            return back()->with('error', 'This submission can no longer be edited.');
        }

        // Update answers
        foreach ($validated['answers'] as $answerData) {
            \App\Models\UserAnswer::updateOrCreate(
                [
                    'submission_id' => $submission->id,
                    'group_question_id' => $answerData['group_question_id'],
                ],
                [
                    'answer_text' => $answerData['answer_text'],
                ]
            );
        }

        return back()->with('success', 'Your answers have been updated successfully!');
    }
}
