<?php

namespace App\Http\Controllers;

use App\Models\Game;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GameController extends Controller
{
    /**
     * Display a listing of games.
     */
    public function index()
    {
        $games = Game::with('creator')
            ->withCount('questions', 'submissions')
            ->latest()
            ->paginate(15);

        return Inertia::render('Games/Index', [
            'games' => $games,
        ]);
    }

    /**
     * Show the form for creating a new game.
     */
    public function create()
    {
        return Inertia::render('Games/Create');
    }

    /**
     * Store a newly created game in storage.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'event_type' => 'required|string|max:100',
            'event_date' => 'required|date',
            'status' => 'required|in:draft,active,locked,completed',
            'lock_date' => 'nullable|date|after:now',
        ]);

        $game = Game::create([
            ...$validated,
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('games.show', $game)
            ->with('success', 'Game created successfully!');
    }

    /**
     * Display the specified game.
     */
    public function show(Game $game)
    {
        $game->load([
            'creator',
            'questions' => function ($query) {
                $query->orderBy('display_order');
            },
            'questions.groupQuestionAnswers',
        ]);

        $game->loadCount('submissions');

        return Inertia::render('Games/Show', [
            'game' => $game,
        ]);
    }

    /**
     * Show the form for editing the specified game.
     */
    public function edit(Game $game)
    {
        $this->authorize('update', $game);

        return Inertia::render('Games/Edit', [
            'game' => $game,
        ]);
    }

    /**
     * Update the specified game in storage.
     */
    public function update(Request $request, Game $game)
    {
        $this->authorize('update', $game);

        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'event_type' => 'required|string|max:100',
            'event_date' => 'required|date',
            'status' => 'required|in:draft,active,locked,completed',
            'lock_date' => 'nullable|date|after:now',
        ]);

        $game->update($validated);

        return redirect()->route('games.show', $game)
            ->with('success', 'Game updated successfully!');
    }

    /**
     * Remove the specified game from storage.
     */
    public function destroy(Game $game)
    {
        $this->authorize('delete', $game);

        $game->delete();

        return redirect()->route('games.index')
            ->with('success', 'Game deleted successfully!');
    }

    /**
     * Display games available for play.
     */
    public function available()
    {
        $games = Game::where('status', 'active')
            ->where(function ($query) {
                $query->whereNull('lock_date')
                    ->orWhere('lock_date', '>', now());
            })
            ->with('creator')
            ->withCount('questions')
            ->latest('event_date')
            ->paginate(15);

        return Inertia::render('Games/Available', [
            'games' => $games,
        ]);
    }

    /**
     * Play a specific game.
     */
    public function play(Game $game)
    {
        // Check if game is playable
        if ($game->status !== 'active') {
            return redirect()->route('games.available')
                ->with('error', 'This game is not currently available.');
        }

        // Check if lock date has passed
        if ($game->lock_date && $game->lock_date->isPast()) {
            return redirect()->route('games.available')
                ->with('error', 'This game is locked.');
        }

        $game->load(['questions' => function ($query) {
            $query->orderBy('display_order');
        }]);

        return Inertia::render('Games/Play', [
            'game' => $game,
        ]);
    }

    /**
     * Join a game (create submission).
     */
    public function join(Request $request, Game $game)
    {
        $this->authorize('submit', $game);

        $validated = $request->validate([
            'group_id' => 'required|exists:groups,id',
        ]);

        // Check if user is a member of the group
        if (!$request->user()->groups()->where('groups.id', $validated['group_id'])->exists()) {
            return back()->with('error', 'You are not a member of this group.');
        }

        // Check if user already has a submission for this game and group
        $existingSubmission = \App\Models\Submission::where('game_id', $game->id)
            ->where('user_id', $request->user()->id)
            ->where('group_id', $validated['group_id'])
            ->first();

        if ($existingSubmission) {
            return redirect()->route('games.play', $game)
                ->with('info', 'You have already joined this game for this group.');
        }

        // Create new submission
        $submission = \App\Models\Submission::create([
            'game_id' => $game->id,
            'user_id' => $request->user()->id,
            'group_id' => $validated['group_id'],
            'total_score' => 0,
            'possible_points' => 0,
            'percentage' => 0,
            'is_complete' => false,
            'submitted_at' => null,
        ]);

        return redirect()->route('games.play', $game)
            ->with('success', 'Successfully joined the game!');
    }

    /**
     * Submit answers for a game.
     */
    public function submitAnswers(Request $request, Game $game)
    {
        $this->authorize('submit', $game);

        $validated = $request->validate([
            'group_id' => 'required|exists:groups,id',
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|exists:questions,id',
            'answers.*.answer_text' => 'required|string',
        ]);

        // Get or create submission
        $submission = \App\Models\Submission::firstOrCreate(
            [
                'game_id' => $game->id,
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
                    'question_id' => $answerData['question_id'],
                ],
                [
                    'answer_text' => $answerData['answer_text'],
                    'points_earned' => 0, // Will be calculated when admin sets correct answers
                    'is_correct' => false, // Will be determined when admin sets correct answers
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
     * Update answers before game is locked.
     */
    public function updateAnswers(Request $request, Game $game)
    {
        $this->authorize('submit', $game);

        $validated = $request->validate([
            'group_id' => 'required|exists:groups,id',
            'answers' => 'required|array',
            'answers.*.question_id' => 'required|exists:questions,id',
            'answers.*.answer_text' => 'required|string',
        ]);

        // Get submission
        $submission = \App\Models\Submission::where('game_id', $game->id)
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
                    'question_id' => $answerData['question_id'],
                ],
                [
                    'answer_text' => $answerData['answer_text'],
                ]
            );
        }

        return back()->with('success', 'Your answers have been updated successfully!');
    }
}
