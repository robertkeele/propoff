<?php

namespace App\Http\Controllers;

use App\Models\Game;
use App\Models\Group;
use App\Models\Leaderboard;
use App\Models\Submission;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Display the user dashboard.
     */
    public function index(Request $request)
    {
        $user = $request->user();

        // Get active games (open status)
        $activeGames = Game::where('status', 'open')
            ->where('lock_date', '>', now())
            ->orderBy('lock_date', 'asc')
            ->with('creator')
            ->limit(5)
            ->get()
            ->map(function ($game) use ($user) {
                // Check if user has submitted
                $userSubmission = Submission::where('game_id', $game->id)
                    ->where('user_id', $user->id)
                    ->first();

                return [
                    'id' => $game->id,
                    'name' => $game->name,
                    'category' => $game->category,
                    'event_date' => $game->event_date,
                    'lock_date' => $game->lock_date,
                    'status' => $game->status,
                    'questions_count' => $game->questions()->count(),
                    'has_submitted' => $userSubmission !== null,
                    'submission_complete' => $userSubmission?->is_complete ?? false,
                ];
            });

        // Get user's groups
        $userGroups = $user->groups()
            ->withCount('users')
            ->limit(5)
            ->get()
            ->map(function ($group) {
                return [
                    'id' => $group->id,
                    'name' => $group->name,
                    'code' => $group->code,
                    'is_public' => $group->is_public,
                    'members_count' => $group->users_count,
                ];
            });

        // Get recent results (completed games with user submissions)
        $recentResults = Leaderboard::where('user_id', $user->id)
            ->whereHas('game', function ($query) {
                $query->where('status', 'completed');
            })
            ->with(['game', 'group'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($leaderboard) {
                return [
                    'game_name' => $leaderboard->game->name,
                    'group_name' => $leaderboard->group?->name ?? 'Global',
                    'rank' => $leaderboard->rank,
                    'total_score' => $leaderboard->total_score,
                    'possible_points' => $leaderboard->possible_points,
                    'percentage' => $leaderboard->percentage,
                ];
            });

        // Get participation statistics
        $stats = [
            'total_games' => Submission::where('user_id', $user->id)->distinct('game_id')->count('game_id'),
            'total_submissions' => Submission::where('user_id', $user->id)->count(),
            'groups_count' => $user->groups()->count(),
            'average_score' => (float) (Submission::where('user_id', $user->id)
                ->where('is_complete', true)
                ->avg('percentage') ?? 0),
        ];

        return Inertia::render('Dashboard', [
            'activeGames' => $activeGames,
            'userGroups' => $userGroups,
            'recentResults' => $recentResults,
            'stats' => $stats,
        ]);
    }
}
