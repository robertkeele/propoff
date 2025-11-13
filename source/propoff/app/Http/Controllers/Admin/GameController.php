<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreGameRequest;
use App\Http\Requests\UpdateGameRequest;
use App\Models\Game;
use Illuminate\Http\Request;
use Inertia\Inertia;

class GameController extends Controller
{
    /**
     * Display a listing of games.
     */
    public function index(Request $request)
    {
        $query = Game::with('creator')
            ->withCount(['questions', 'submissions']);

        // Filter by status
        if ($request->has('status') && $request->status !== 'all') {
            $query->where('status', $request->status);
        }

        // Search
        if ($request->has('search') && $request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('event_type', 'like', "%{$request->search}%");
            });
        }

        $games = $query->latest()->paginate(15);

        return Inertia::render('Admin/Games/Index', [
            'games' => $games,
            'filters' => $request->only(['status', 'search']),
        ]);
    }

    /**
     * Show the form for creating a new game.
     */
    public function create()
    {
        return Inertia::render('Admin/Games/Create');
    }

    /**
     * Store a newly created game in storage.
     */
    public function store(StoreGameRequest $request)
    {
        $game = Game::create([
            ...$request->validated(),
            'created_by' => auth()->id(),
        ]);

        return redirect()->route('admin.games.show', $game)
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
            'questions.groupQuestionAnswers.group',
        ]);

        $game->loadCount(['submissions', 'questions']);

        // Get all groups that have answers for this game
        $groupsWithAnswers = $game->questions()
            ->with('groupQuestionAnswers.group')
            ->get()
            ->pluck('groupQuestionAnswers')
            ->flatten()
            ->pluck('group')
            ->unique('id')
            ->values();

        return Inertia::render('Admin/Games/Show', [
            'game' => $game,
            'groupsWithAnswers' => $groupsWithAnswers,
        ]);
    }

    /**
     * Show the form for editing the specified game.
     */
    public function edit(Game $game)
    {
        return Inertia::render('Admin/Games/Edit', [
            'game' => $game,
        ]);
    }

    /**
     * Update the specified game in storage.
     */
    public function update(UpdateGameRequest $request, Game $game)
    {
        $game->update($request->validated());

        return redirect()->route('admin.games.show', $game)
            ->with('success', 'Game updated successfully!');
    }

    /**
     * Remove the specified game from storage.
     */
    public function destroy(Game $game)
    {
        $gameName = $game->name;
        $game->delete();

        return redirect()->route('admin.games.index')
            ->with('success', "Game '{$gameName}' deleted successfully!");
    }

    /**
     * Update game status.
     */
    public function updateStatus(Request $request, Game $game)
    {
        $request->validate([
            'status' => 'required|in:draft,open,locked,in_progress,completed',
        ]);

        $game->update(['status' => $request->status]);

        return back()->with('success', 'Game status updated successfully!');
    }

    /**
     * Duplicate a game.
     */
    public function duplicate(Game $game)
    {
        $newGame = $game->replicate();
        $newGame->name = $game->name . ' (Copy)';
        $newGame->status = 'draft';
        $newGame->created_by = auth()->id();
        $newGame->save();

        // Duplicate questions
        foreach ($game->questions as $question) {
            $newQuestion = $question->replicate();
            $newQuestion->game_id = $newGame->id;
            $newQuestion->save();
        }

        return redirect()->route('admin.games.show', $newGame)
            ->with('success', 'Game duplicated successfully!');
    }

    /**
     * Get game statistics.
     */
    public function statistics(Game $game)
    {
        $stats = [
            'total_submissions' => $game->submissions()->count(),
            'completed_submissions' => $game->submissions()->where('is_complete', true)->count(),
            'pending_submissions' => $game->submissions()->where('is_complete', false)->count(),
            'average_score' => $game->submissions()->where('is_complete', true)->avg('percentage') ?? 0,
            'highest_score' => $game->submissions()->where('is_complete', true)->max('percentage') ?? 0,
            'lowest_score' => $game->submissions()->where('is_complete', true)->min('percentage') ?? 0,
            'total_participants' => $game->submissions()->distinct('user_id')->count('user_id'),
            'questions_count' => $game->questions()->count(),
        ];

        // Submissions by group
        $submissionsByGroup = $game->submissions()
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

        return Inertia::render('Admin/Games/Statistics', [
            'game' => $game,
            'stats' => $stats,
            'submissionsByGroup' => $submissionsByGroup,
        ]);
    }
}
