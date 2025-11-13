<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Group;
use App\Models\Leaderboard;
use Illuminate\Http\Request;
use Inertia\Inertia;

class LeaderboardController extends Controller
{
    /**
     * Display the global leaderboard for a game.
     */
    public function game(Game $game)
    {
        $leaderboard = Leaderboard::with('user')
            ->where('game_id', $game->id)
            ->whereNull('group_id')
            ->orderBy('total_score', 'desc')
            ->orderBy('answered_count', 'desc')
            ->paginate(50);

        // Update ranks
        $this->updateRanks($game->id, null);

        return Inertia::render('Leaderboards/Game', [
            'game' => $game,
            'leaderboard' => $leaderboard,
        ]);
    }

    /**
     * Display the leaderboard for a specific group and game.
     */
    public function group(Game $game, Group $group)
    {
        $leaderboard = Leaderboard::with('user')
            ->where('game_id', $game->id)
            ->where('group_id', $group->id)
            ->orderBy('total_score', 'desc')
            ->orderBy('answered_count', 'desc')
            ->paginate(50);

        // Update ranks
        $this->updateRanks($game->id, $group->id);

        return Inertia::render('Leaderboards/Group', [
            'game' => $game,
            'group' => $group,
            'leaderboard' => $leaderboard,
        ]);
    }

    /**
     * Display all leaderboards for all games.
     */
    public function index()
    {
        $games = Game::where('status', 'active')
            ->orWhere('status', 'completed')
            ->with(['leaderboards' => function ($query) {
                $query->whereNull('group_id')
                    ->orderBy('rank')
                    ->limit(10);
            }, 'leaderboards.user'])
            ->withCount('submissions')
            ->latest('event_date')
            ->paginate(10);

        return Inertia::render('Leaderboards/Index', [
            'games' => $games,
        ]);
    }

    /**
     * Display user's position across all leaderboards.
     */
    public function user()
    {
        $userLeaderboards = Leaderboard::with(['game', 'group'])
            ->where('user_id', auth()->id())
            ->orderBy('percentage', 'desc')
            ->paginate(15);

        return Inertia::render('Leaderboards/User', [
            'leaderboards' => $userLeaderboards,
        ]);
    }

    /**
     * Update ranks for a specific game/group leaderboard.
     */
    protected function updateRanks($gameId, $groupId = null)
    {
        $query = Leaderboard::where('game_id', $gameId);

        if ($groupId) {
            $query->where('group_id', $groupId);
        } else {
            $query->whereNull('group_id');
        }

        $entries = $query->orderBy('total_score', 'desc')
            ->orderBy('answered_count', 'desc')
            ->get();

        $rank = 1;
        foreach ($entries as $entry) {
            $entry->update(['rank' => $rank]);
            $rank++;
        }
    }

    /**
     * Recalculate leaderboard for a game.
     */
    public function recalculate(Game $game)
    {
        $this->authorize('update', $game);

        // Recalculate from submissions
        $submissions = $game->submissions()
            ->where('is_complete', true)
            ->with('userAnswers')
            ->get();

        foreach ($submissions as $submission) {
            $totalScore = $submission->userAnswers->sum('points_earned');
            $answeredCount = $submission->userAnswers->count();
            $percentage = $submission->possible_points > 0
                ? ($totalScore / $submission->possible_points) * 100
                : 0;

            Leaderboard::updateOrCreate(
                [
                    'game_id' => $submission->game_id,
                    'user_id' => $submission->user_id,
                    'group_id' => $submission->group_id,
                ],
                [
                    'total_score' => $totalScore,
                    'possible_points' => $submission->possible_points,
                    'percentage' => $percentage,
                    'answered_count' => $answeredCount,
                ]
            );
        }

        // Update ranks
        $this->updateRanks($game->id, null);

        // Update ranks for all groups
        $groups = $game->submissions()
            ->whereNotNull('group_id')
            ->distinct()
            ->pluck('group_id');

        foreach ($groups as $groupId) {
            $this->updateRanks($game->id, $groupId);
        }

        return back()->with('success', 'Leaderboard recalculated successfully!');
    }
}
