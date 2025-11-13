<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Game;
use App\Models\Group;
use App\Models\Submission;
use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class DashboardController extends Controller
{
    /**
     * Display the admin dashboard.
     */
    public function index(Request $request)
    {
        // Verify user is admin
        if ($request->user()->role !== 'admin') {
            abort(403, 'Unauthorized. Admin access required.');
        }

        // Get statistics
        $stats = [
            'total_users' => User::count(),
            'total_games' => Game::count(),
            'total_groups' => Group::count(),
            'total_submissions' => Submission::count(),
            'active_games' => Game::where('status', 'open')->count(),
            'completed_games' => Game::where('status', 'completed')->count(),
            'draft_games' => Game::where('status', 'draft')->count(),
            'completed_submissions' => Submission::where('is_complete', true)->count(),
        ];

        // Get recent games
        $recentGames = Game::with('creator')
            ->withCount(['questions', 'submissions'])
            ->latest()
            ->limit(10)
            ->get()
            ->map(function ($game) {
                return [
                    'id' => $game->id,
                    'name' => $game->name,
                    'event_type' => $game->event_type,
                    'status' => $game->status,
                    'event_date' => $game->event_date,
                    'lock_date' => $game->lock_date,
                    'creator_name' => $game->creator->name,
                    'questions_count' => $game->questions_count,
                    'submissions_count' => $game->submissions_count,
                    'created_at' => $game->created_at,
                ];
            });

        // Get recent submissions
        $recentSubmissions = Submission::with(['user', 'game', 'group'])
            ->where('is_complete', true)
            ->latest('submitted_at')
            ->limit(10)
            ->get()
            ->map(function ($submission) {
                return [
                    'id' => $submission->id,
                    'user_name' => $submission->user->name,
                    'game_name' => $submission->game->name,
                    'group_name' => $submission->group->name,
                    'total_score' => $submission->total_score,
                    'possible_points' => $submission->possible_points,
                    'percentage' => $submission->percentage,
                    'submitted_at' => $submission->submitted_at,
                ];
            });

        // Get recent users
        $recentUsers = User::latest()
            ->limit(10)
            ->get()
            ->map(function ($user) {
                return [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role,
                    'created_at' => $user->created_at,
                ];
            });

        // Games by status
        $gamesByStatus = [
            'draft' => Game::where('status', 'draft')->count(),
            'open' => Game::where('status', 'open')->count(),
            'locked' => Game::where('status', 'locked')->count(),
            'in_progress' => Game::where('status', 'in_progress')->count(),
            'completed' => Game::where('status', 'completed')->count(),
        ];

        return Inertia::render('Admin/Dashboard', [
            'stats' => $stats,
            'recentGames' => $recentGames,
            'recentSubmissions' => $recentSubmissions,
            'recentUsers' => $recentUsers,
            'gamesByStatus' => $gamesByStatus,
        ]);
    }
}
